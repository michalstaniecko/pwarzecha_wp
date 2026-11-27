# Testy – środowisko (a) agenta

Trzy warstwy bez Dockera i bez trwałego stanu (ADR 0005, ADR 0011, #37).

| Komenda | Co robi | Czas (M-series, cache WP) |
| --- | --- | --- |
| `npm run test:php:unit` | PHPUnit `unit`: Brain Monkey, bez WordPressa | ~0,2 s |
| `npm run test:php` | `test:php:unit` + PHPUnit `integration` na czystym WP 7.1.5 / PHP 8.4 / SQLite w Playground CLI | ~8 s (pierwszy przebieg ~25 s) |
| `npm run test:e2e` | `npm run build` + Playwright (Chromium) na serwerze Playground z `tests/e2e/blueprint.json` | ~16 s |
| `npm run test:e2e:all` | j.w. razem z testami `@plugins` (w `check:full`) | |
| `npm run wp:playground` | ten sam serwer do podglądu: http://127.0.0.1:9400, `admin` / `password`; Ctrl+C kończy | |

Argumenty po `--` trafiają do PHPUnit / Playwrighta: `npm run test:php -- --filter BlockTestCase`, `npx playwright test tests/e2e/smoke.spec.ts`. Sam `vendor/bin/phpunit` uruchamia tylko suite `unit` (domyślna); `integration` wymaga WordPressa, więc zawsze przez `npm run test:php`.

Pierwsze uruchomienie pobiera WordPress do `~/.wordpress-playground/` (~37 MB, zostaje w cache) i wymaga `npx playwright install chromium`.

## Wynik spike'u (#37)

1. **PHPUnit: `^9.6`** (zainstalowane 9.6.39, przypięte w `composer.json`). Suite WP 7.1 (`wp-phpunit/wp-phpunit` 7.1.3) w `WP_UnitTestCase::expectDeprecated()` woła `PHPUnit\Util\Test::parseTestMethodAnnotations()` i `getName( false )`, których nie ma od PHPUnit 10, więc PHPUnit 10–12 odpada, mimo że `yoast/phpunit-polyfills` 4 dopuszcza `^12`. Bez przypięcia Composer instalował 12.5.
2. **Suite WP przechodzi na Playground (SQLite)** z aktywnym motywem, Yoast SEO 28.9 i CookieYes 3.5, łącznie z transakcjami między testami. Dwa obejścia, oba w repo:
   - `install.php` suite'u uruchamia się w podprocesie (`system()`), a podprocesy Playground nie widzą zamontowanych katalogów i zawsze zwracają kod 1. Dlatego `wp-tests-config-playground.php` ustawia `WP_TESTS_SKIP_INSTALL=1` i używa świeżej instalacji, którą Playground robi przy każdym `php` (prefiks `wp_`, użytkownik `admin`).
   - Playground definiuje `WP_DEBUG_DISPLAY=false` i loguje błędy do pamięci, przez co fatal error kończył przebieg bez słowa. Bootstrap integracji włącza `display_errors`.
   ADR 0011 zostaje: integracja na Playground, wp-env (#38) tylko dla `@group mariadb` i pełnego przebiegu na MariaDB.
3. **Czas:** `test:php:unit` ~0,2 s, `test:php` ~8 s na ciepło, ~25 s przy pierwszym przebiegu (cel < 60 s), start serwera e2e ~10 s.

Zamiast `--auto-mount` montujemy jawnie (`tools/playground/site.mjs`): korzeń repo nie jest ani motywem, ani wtyczką, więc auto-mount nie wie, gdzie go umieścić. Motyw trafia do `wp-content/themes/pw-theme`, wtyczki z `.wp/plugins` do `wp-content/plugins`, całe repo do `/pw` (dla `vendor/` i `tests/`).

## PHP unit – `tests/php/unit/`

- Klasa bazowa `PW\Tests\Unit\TestCase` (Brain Monkey przed i po każdym teście, Mockery liczy się jako asercja). Metody `set_up()` / `tear_down()` (polyfills), nie `setUp()`.
- Plik motywu ładujemy w `set_up_before_class()`: `require_once self::theme_path( 'inc/…' )`. Tylko pliki bez efektów ubocznych przy ładowaniu (same funkcje), np. `inc/migrations/runner.php`, helpery. `ABSPATH` jest zdefiniowane w bootstrapie.
- Funkcje WP: `Brain\Monkey\Functions\when( 'get_option' )->justReturn( … )` albo `expect()`.

## PHP integracja – `tests/php/integration/`

- `\WP_UnitTestCase` – każdy test w transakcji, hooki przywracane po teście.
- `PW\Tests\BlockTestCase` dla bloków:
  - `render_block_markup( 'pw/hero', [ 'title' => '…' ], $inner )` – markup bloku przez `do_blocks()`, czyli jak na froncie (z `render.php`);
  - `assertHtmlHasSelector( $html, 'section.wp-block-pw-hero > h2' )` – selektor CSS (`Dom\HTMLDocument` z PHP 8.4);
  - `assertNoUnescaped( $html )` – brak `<script>` i atrybutów `on*`; renderuj z `self::XSS` w atrybutach/danych;
  - `with_data( 'kontakt', [ … ] )` – filtr `pw/data/kontakt` na czas testu (ADR 0007).
- Blok testowy `pw/test` (`tests/fixtures/blocks/pw-test/`) rejestruje bootstrap; nie trafia do motywu.
- Aktywne wtyczki: `PW_TESTS_PLUGINS` w `bootstrap.php`. Motyw aktywowany przez `switch_theme()` w `setup_theme`.
- Migracje: filtr `pw/migrations/dir` kieruje runner na katalog z fiksturami (`tests/fixtures/migrations*`).
- Nazwy plików `*Test.php` (PSR-4); PHPCS nie wymaga docblocków metod testowych.

### Baza: SQLite vs MariaDB

Kod motywu nie używa surowego SQL (PHPCS ostrzega o `$wpdb->…` poza `theme/inc/migrations/`). Test, który musi dotknąć `$wpdb` albo zależy od zachowania MySQL/MariaDB, dostaje `@group mariadb`: `npm run test:php` go pomija (`--exclude-group mariadb`), `npm run test:php:mariadb` (#38) uruchamia całość na MariaDB. `DatabaseTest` to kanarek: na SQLite nie przechodzi, więc pokazuje, że grupa jest pomijana na Playground i naprawdę biegnie na MariaDB.

## E2E – `tests/e2e/`

- `import { test, expect } from '@wordpress/e2e-test-utils-playwright'` (fikstury `admin`, `editor`, `requestUtils`, `page`).
- Stan startowy: `tests/e2e/blueprint.json` (motyw, Yoast, CookieYes, `WP_ENVIRONMENT_TYPE=local`, `WP_DEBUG`). Krok `wp pw seed` dochodzi razem z #54.
- Serwer startuje raz na przebieg (`global-setup.ts`, port 9410, `PW_E2E_PORT`), testy idą sekwencyjnie (`workers: 1`) i współdzielą stan bazy – test, który zmienia dane, sprząta po sobie (`requestUtils.delete*`).
- `PW_E2E_BASE_URL` (ustawia `npm run test:e2e:wp-env`, #38) kieruje testy na istniejącą stronę i wyłącza start Playground.
- Testy domyślnie jako zalogowany admin: `global-setup.ts` loguje się przy każdym przebiegu (`.cache/e2e/storage-states/admin.json`) i wyłącza „Welcome to the editor”, który inaczej zasłania edytor mimo `admin.createNewPost()`. Testy frontu jako gość: `test.use( { storageState: { cookies: [], origins: [] } } )` (jak `smoke.spec.ts`).
- Tagi w tytule: `@plugins` (wymaga skonfigurowanych wtyczek, np. banera CookieYes – tylko `test:e2e:all`), `@visual` (tylko w kontenerze Playwright z `PW_VISUAL=1`, #42).
- Wklejanie: `pageUtils.setClipboardData( { plainText } )` + `pressKeys( 'primary+v' )` daje schowek tylko z `text/plain` – tak jak URL skopiowany z paska adresu. Yoast SEO 28.9 rejestruje własny `core/link` (nofollow/sponsored) ze starą regułą wklejania, która przy samym kursorze nic nie robi; goły URL wklejony do pustego akapitu w Aktualności (bez `core/embed`) znikał bez śladu. Motyw dokłada regułę `pw/url-paste` (`theme/src/editor/url-paste.js`), więc w edytorze z Yoastem wklejony URL zostaje linkiem – pilnuje tego `editor-config.spec.ts`. Wyłączenie Yoasta w teście: `requestUtils.deactivatePlugin( 'yoast-seo' )` (slug z nazwy wtyczki, nie z katalogu).
- Raporty: `list` lokalnie, `blob` + `html` w CI; artefakty w ignorowanych `test-results/`, `playwright-report/`.

## Styleguide i testy wizualne (#42, ADR 0010)

- Strona `/?pw_styleguide=1` (`&group=<slug>` = jedna grupa) renderuje każdy prymityw DS i blok przez produkcyjne `render.php` i helpery `pw_*`. Włączona w `local`/`development`, poza produkcją także z `WP_DEBUG`; filtr `pw/styleguide/enabled`. Wyłączona = zwykłe 404. Zawsze `noindex, nofollow` i no-cache.
- Grupa = jeden plik `theme/inc/styleguide/partials/<slug>.php` (szablon `_template.php`), rejestrowany filtrem `pw/styleguide/groups`. Wiersz stanu: `pw_sg_row( etykieta, stan[, 'navy'] )`; dane globalne na czas wiersza: `pw_sg_with_data( 'kontakt', $dane, $render )`; zdjęcia testowe: `pw_sg_photo( 'wide'|'photo'|'square' )` (import do mediów przy pierwszym wejściu, meta `_pw_sg_fixture`).
- `tests/e2e/styleguide.spec.ts` czyta sekcje `[data-sg-section]` z DOM: axe WCAG 2.2 AA na sekcję, zero błędów konsoli i żądań do Google/YouTube (`npm run test:e2e`), zrzut `<slug>.png` na sekcję (`@visual`).
- `npm run test:visual` = `npm run build` + `tools/visual/visual.mjs`: `docker run` obrazu `mcr.microsoft.com/playwright:v1.65.1-noble` (Node 26, Linux) z repo zamontowanym w `/work`, jako bieżący użytkownik, z cache WP Playground z hosta; w kontenerze startuje Playground jak w `test:e2e`. `npm run test:visual:update` nadpisuje baseline'y. Argumenty po `--` trafiają do Playwrighta.
- Baseline'y: `tests/e2e/__screenshots__/styleguide.spec.ts/<slug>.png` w git, **bez sufiksu platformy** – powstają i są porównywane wyłącznie w kontenerze (Linux). Na hoście (macOS) `@visual` nie biega (projekt `visual` istnieje tylko z `PW_VISUAL=1`).
- Baner CookieYes jest na czas zrzutów ukryty stylem w specu (inaczej zasłania sekcję w widoku).

## Sprzątanie (ADR 0015)

Playground CLI nie używa Dockera. `php` trzyma wszystko w pamięci; `server` tworzy katalog `node-playground-cli-site-*` w katalogu tymczasowym systemu i usuwa go przy zatrzymaniu (teardown `globalSetup`, Ctrl+C w `wp:playground`). Zostaje tylko cache pobranych wersji WP.

## CI na PR – `.github/workflows/ci.yml` (#44, ADR 0010)

Ten sam zakres co `npm run check`, na każdym PR do `develop`/`main` (bez szkiców). Job `changes` wybiera resztę według filtrów ścieżek z `.github/ci-filters.yml`; zmiana tylko w `docs/**` lub `*.md` nie uruchamia nic poza `changes` i `ci-ok`. Logika (wybór bazy porównania, werdykt, klucz cache Playground) jest w `tools/ci/ci.mjs`; filtry i reguły budżetu sprawdza `npm run test:tools`. Nowy plik, który czyta suita PHPUnit, a nie pasuje do filtra `php`, trzeba dopisać do `ci-filters.yml` (i przykład do `tools/ci/filters.test.mjs`).

Lokalnie, poza `check` (wymaga sieci): `bash <(curl -fsSL https://raw.githubusercontent.com/rhysd/actionlint/v1.7.12/scripts/download-actionlint.bash) 1.7.12 /tmp && /tmp/actionlint`.

Kroki dla człowieka (jednorazowo):

- Ochrona gałęzi `develop` (i `main`): wymagany status **`ci-ok`**. Wystarczy ten jeden: job pominięty przez filtr raportuje sukces, więc gdyby wymagane były same `static` i `php-tests`, awaria `changes` przepuściłaby PR; `ci-ok` (`if: always()`) zamienia każdą porażkę lub anulowanie w `needs` na czerwony status.
- Ustawienia konta → Billing → limit wydatków na Actions = 0 USD.
