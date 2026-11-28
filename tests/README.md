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
- `tear_down()` wyrejestrowuje wszystkie klucze meta (`unregister_all_meta_keys()`), a `init` nie biegnie ponownie: test meta rejestrowanych przez motyw woła w `set_up()` funkcję rejestrującą (np. `pw_karnet_register()` w `KarnetTest`, `\PW\Data\Lokalizacja\register_meta()` w `Data/LokalizacjaTest`). Bez tego `get_registered_meta_keys()` jest puste od drugiego testu.
- `PW\Tests\BlockTestCase` dla bloków:
  - `render_block_markup( 'pw/hero', [ 'title' => '…' ], $inner )` – markup bloku przez `do_blocks()`, czyli jak na froncie (z `render.php`);
  - `assertHtmlHasSelector( $html, 'section.wp-block-pw-hero > h2' )` – selektor CSS (`Dom\HTMLDocument` z PHP 8.4);
  - `assertNoUnescaped( $html )` – brak `<script>` i atrybutów `on*`; renderuj z `self::XSS` w atrybutach/danych;
  - `with_data( 'kontakt', [ … ] )` – filtr `pw/data/kontakt` na czas testu (ADR 0007).
- Blok testowy `pw/test` (`tests/fixtures/blocks/pw-test/`) rejestruje bootstrap (`register_block_type( dir )`); nie trafia do motywu. Ścieżkę z manifestu (`PW\Blocks\register_collection()` z `inc/blocks.php`) sprawdza `BlocksManifestTest`: generuje manifest fikstury do katalogu tymczasowego, rejestruje z niego blok i renderuje go.
- Aktywne wtyczki: `PW_TESTS_PLUGINS` w `bootstrap.php`. Motyw aktywowany przez `switch_theme()` w `setup_theme`.
- Migracje: filtr `pw/migrations/dir` kieruje runner na katalog z fiksturami (`tests/fixtures/migrations*`).
- Nazwy plików `*Test.php` (PSR-4); PHPCS nie wymaga docblocków metod testowych.

### Schema w grafie Yoasta (`@group yoast`, #53)

- Yoast SEO jest w suite zawsze (`PW_TESTS_PLUGINS`; Playground montuje `.wp/plugins` z Composera, wp-env mapuje te same katalogi w `.wp-env.tests.json`), więc testy schemy biegną w `npm run test:php` i w `test:php:mariadb`.
- Klasa bazowa `PW\Tests\Integration\Schema\SchemaTestCase` (`@group yoast`, `markTestSkipped`, gdy brak `WPSEO_VERSION`): `graph( $url )` – `go_to()` + `wp_head`, parsuje `<script class="yoast-schema-graph">` i zwraca `@graph`; `nodes( $graph, 'Service' )`, `types( $graph )`; fikstury `lokalizacja()`, `page( $title, $meta )`, `set_front_page()`.
- Yoast memoizuje kontekst „bieżącej strony” na cały proces – `graph()` czyści `Meta_Tags_Context_Memoizer` po każdym `go_to()`. Bez tego drugi test w procesie dostaje graf pierwszego.
- Węzeł `Organization` (i referencje `parentOrganization` / `provider`) Yoast tworzy tylko dla firmy z nazwą i logo: `configure_organization()`. Bez niej węzły motywu nie mają referencji (żadnego wiszącego `@id`).

### Baza: SQLite vs MariaDB

Kod motywu nie używa surowego SQL (PHPCS ostrzega o `$wpdb->…` poza `theme/inc/migrations/`). Test, który musi dotknąć `$wpdb` albo zależy od zachowania MySQL/MariaDB, dostaje `@group mariadb`: `npm run test:php` go pomija (`--exclude-group mariadb`), `npm run test:php:mariadb` (#38) uruchamia całość na MariaDB. `DatabaseTest` to kanarek: na SQLite nie przechodzi, więc pokazuje, że grupa jest pomijana na Playground i naprawdę biegnie na MariaDB.

## E2E – `tests/e2e/`

- `import { test, expect } from '@wordpress/e2e-test-utils-playwright'` (fikstury `admin`, `editor`, `requestUtils`, `page`).
- Stan startowy: `tests/e2e/blueprint.json` (motyw, Yoast, CookieYes, `WP_ENVIRONMENT_TYPE=local`, `WP_DEBUG`) i ostatni krok `wp-cli`: `wp pw seed --force` (#54) – Dane strony, Grupy, Lokalizacje Chorzów i Katowice z Grafikiem, 3 Karnety, menu `wp_navigation`, ustawienia Yoasta; same placeholdery `[...]`. `test:e2e:wp-env` seeduje instancję testów tą samą komendą. `seed.spec.ts` sprawdza, że seed się wykonał. Seed w PHPUnit: `tests/php/integration/Cli/SeedTest.php` (`( new Seed_Command() )->run( $args, $assoc )`, bez runnera WP-CLI).
- Serwer startuje raz na przebieg (`global-setup.ts`, port 9410, `PW_E2E_PORT`), testy idą sekwencyjnie (`workers: 1`) i współdzielą stan bazy – test, który zmienia dane, sprząta po sobie (`requestUtils.delete*`).
- `PW_E2E_BASE_URL` (ustawia `npm run test:e2e:wp-env`, #38) kieruje testy na istniejącą stronę i wyłącza start Playground.
- Testy domyślnie jako zalogowany admin: `global-setup.ts` loguje się przy każdym przebiegu (`.cache/e2e/storage-states/admin.json`) i wyłącza „Welcome to the editor”, który inaczej zasłania edytor mimo `admin.createNewPost()`. Testy frontu jako gość: `test.use( { storageState: { cookies: [], origins: [] } } )` (jak `smoke.spec.ts`).
- Tagi w tytule: `@plugins` (wymaga skonfigurowanych wtyczek, np. banera CookieYes – tylko `test:e2e:all`), `@visual` (tylko w kontenerze Playwright z `PW_VISUAL=1`, #42).
- Wklejanie: `pageUtils.setClipboardData( { plainText } )` + `pressKeys( 'primary+v' )` daje schowek tylko z `text/plain` – tak jak URL skopiowany z paska adresu. Yoast SEO 28.9 rejestruje własny `core/link` (nofollow/sponsored) ze starą regułą wklejania, która przy samym kursorze nic nie robi; goły URL wklejony do pustego akapitu w Aktualności (bez `core/embed`) znikał bez śladu. Motyw dokłada regułę `pw/url-paste` (`theme/src/editor/url-paste.js`), więc w edytorze z Yoastem wklejony URL zostaje linkiem – pilnuje tego `editor-config.spec.ts`. Wyłączenie Yoasta w teście: `requestUtils.deactivatePlugin( 'yoast-seo' )` (slug z nazwy wtyczki, nie z katalogu).
- Raporty: `list` lokalnie, `blob` + `html` w CI (w GitHub Actions dodatkowo `github` – adnotacje w PR); artefakty w ignorowanych `test-results/`, `playwright-report/`, `blob-report/`.

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

Ten sam zakres co `npm run check`, na każdym PR do `develop`/`main` (bez szkiców – na szkicu wszystkie joby są pomijane; oznaczenie PR jako gotowego, `ready_for_review`, uruchamia sprawdzenia). Job `changes` wybiera resztę według filtrów ścieżek z `.github/ci-filters.yml`; zmiana tylko w `docs/**` lub `*.md` nie uruchamia nic poza `changes` i `ci-ok`. Logika (wybór bazy porównania, werdykt, klucz cache Playground) jest w `tools/ci/ci.mjs`; filtry i reguły budżetu sprawdza `npm run test:tools`. Nowy plik, który czyta suita PHPUnit, a nie pasuje do filtra `php`, trzeba dopisać do `ci-filters.yml` (i przykład do `tools/ci/filters.test.mjs`).

Lokalnie, poza `check` (wymaga sieci): `bash <(curl -fsSL https://raw.githubusercontent.com/rhysd/actionlint/v1.7.12/scripts/download-actionlint.bash) 1.7.12 /tmp && /tmp/actionlint`.

Kroki dla człowieka (jednorazowo):

- Ochrona gałęzi `develop` (i `main`): wymagany status **`ci-ok`**. Wystarczy ten jeden: job pominięty przez filtr raportuje sukces, więc gdyby wymagane były same `static` i `php-tests`, awaria `changes` przepuściłaby PR; `ci-ok` (`if: always()`) zamienia każdą porażkę lub anulowanie w `needs` na czerwony status.
- Ustawienia konta → Billing → limit wydatków na Actions = 0 USD.

## Pełny CI – `.github/workflows/full.yml` (#45, ADR 0010)

Odpowiednik `npm run check:full` bez MariaDB i Lighthouse (#94): job `fast` (= `ci.yml`, bramka), potem job `e2e` – `npx playwright test` z `PW_VISUAL=1` i `PW_E2E_PLUGINS=1`, czyli wszystkie specy z `tests/e2e/` (projekt `e2e` razem z `@plugins` i projekt `visual`). Nowy spec nie wymaga zmian w workflow.

- **Kiedy:** push do `develop` (tylko gdy zmienia się `theme/`, `tests/`, `tools/`, zależności, `.nvmrc`, `playwright.config.ts` albo workflowy), PR z etykietą **`ci:full`** (przy dodaniu etykiety, przy każdym pushu, dopóki ją ma, i przy oznaczeniu szkicu jako gotowego – `ready_for_review`), ale nigdy na szkicu (tak jak `ci.yml`; inaczej e2e biegłoby bez statyki i PHPUnit), ręcznie (Actions → full → Run workflow, wejście `ref`) i z `release.yml` (#96, `workflow_call`; `fast` już zawiera `ci.yml`, więc release nie musi wołać go osobno). Zwykły PR i inne etykiety dają tylko pominięte joby (0 minut); na PR `fast` jest pomijany, bo `ci.yml` i tak tam biegnie. Warunki i grupę `concurrency` sprawdza `tools/ci/full.test.mjs` silnikiem wyrażeń GitHuba (`@actions/expressions`).
- **Środowisko:** job w kontenerze `mcr.microsoft.com/playwright:v1.65.1-noble` – tym samym, co `npm run test:visual`, więc baseline'y z repo obowiązują; przeglądarki są w obrazie. Wtyczki z wpackagist (`.wp/plugins`) z cache po hashu `composer.lock`; PHP (`setup-php`) i `composer install --no-dev` tylko przy braku cache.
- **Porażka:** artefakt `playwright-report` (`playwright-report/` – raport HTML, `test-results/` – trace i diff zrzutów, `blob-report/`), 7 dni. Rzeczywiste PNG z CI leżą w `test-results/`.
- **Aktualizacja baseline'ów z CI:** Run workflow z `update_snapshots` = true (pomija `fast` i e2e, uruchamia `--project=visual --update-snapshots`) → artefakt `screenshots` z `tests/e2e/__screenshots__/`; pobrać (`gh run download <id> -n screenshots -D tests/e2e/__screenshots__`), przejrzeć diff i zacommitować. CI niczego nie commituje.
- **arm64 vs amd64:** baseline'y z #42 powstały w Dockerze na macOS (linux/arm64), runnery GitHuba to linux/amd64. Obraz i fonty są te same; lokalnie `docker run --platform linux/amd64 …` (emulacja) przeszedł na tych baseline'ach (#45), ale prawdziwy runner nie był jeszcze sprawdzony – jeśli pierwszy przebieg pokaże różnice w zrzutach, zaktualizować baseline'y przez `update_snapshots` (amd64) i zacommitować.
- **Sharding:** na razie bez. Gdy job `e2e` przekroczy **8 min**, włączyć macierz `--shard=i/2` i job `merge-reports` (`npx playwright merge-reports blob-report`; reporter `blob` już jest w CI).

Kroki dla człowieka (jednorazowo): `gh label create ci:full --description "Uruchom pełny CI (e2e, visual, a11y) na tym PR"`. Po pierwszym przebiegu na `develop` zapisać czasy (cel ≤ 10 min rozliczanych).
