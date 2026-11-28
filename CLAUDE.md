## Agent skills

### Issue tracker

Issues are tracked in GitHub Issues for `michalstaniecko/pwarzecha_wp` (via the `gh` CLI). See `docs/agents/issue-tracker.md`.

### Triage labels

Uses the default triage labels: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `GLOSSARY.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.

ADR 0010–0014 opisują CI w GitHub Actions, trzy środowiska WordPress, migracje atrybutów bloków, Sekcje i Szablon oferty oraz menu.

## Definicja ukończenia zadania

Zadanie jest skończone dopiero wtedy, gdy ma testy napisane metodą TDD (ADR 0005) i `npm run check` przechodzi lokalnie. Zmiana dotykająca UI (bloki, szablony, style) wymaga przed PR także `npm run check:full`. Na koniec zadania uruchom `npm run env:status`; jeśli poza instancją dev zostały kontenery lub wolumeny projektu, uruchom `npm run env:clean` (ADR 0015).

## Komendy

Node 26 (`.nvmrc`: `nvm use` / `fnm use`), PHP 8.4. Pierwsze uruchomienie: `npm ci && composer install` (`npm ci` instaluje też hooki lefthook).

| Komenda | Co robi | Kiedy |
|---|---|---|
| `npm run check` | `lint` + `test:tools` + `test:js` + `test:php` (zakres `ci.yml`) | po każdej zmianie; pre-push |
| `npm run check:full` | `check` + `test:php:mariadb` + `test:e2e:all` + `test:visual` + `test:lighthouse` (zakres `full.yml`) | przed PR z UI |
| `npm run lint` | `lint:js` (ESLint), `lint:css` (Stylelint, tylko tokeny – ADR 0003), `lint:php` (PHPCS), `lint:phpstan`, `lint:theme-json` (schemat w `tools/schemas/`) | pojedynczo przy poprawianiu błędów |
| `npm run test:tools` | `node --test tools/` | zmiany w `tools/` |
| `npm run test:js` | Vitest (`wp-scripts test-unit-js`) | logika edytora bloków |
| `npm run test:php:unit` / `test:php` | PHPUnit: unit (Brain Monkey) / unit + integracja (Playground) | kod PHP motywu |
| `npm run test:php:mariadb` | PHPUnit na MariaDB (wp-env tests) | w `check:full` |
| `npm run test:e2e` / `test:e2e:all` | Playwright na Playground (blueprint `tests/e2e/blueprint.json`) / razem z `@plugins` | front i edytor |
| `npm run test:visual` / `test:visual:update` | zrzuty sekcji styleguide w kontenerze Playwright (Docker) / nadpisanie baseline'ów | zmiana wizualna; w `check:full` |
| `npm run test:lighthouse` | Lighthouse CI | w `check:full` |
| `npm run wp:playground` | podgląd strony z blueprintu e2e (http://127.0.0.1:9400, `admin`/`password`), nic nie zostaje | ręczne sprawdzenie |
| `npm run build` / `start` | `wp-scripts` build / watch | budowanie motywu |
| `npm run env:status` / `env:clean` | pokazuje / usuwa pozostałości testowe Dockera (`pw-test*`, instancja testów wp-env), nigdy instancji dev | na koniec zadania |

**Zmiana wizualna? Sprawdź `/?pw_styleguide=1` (np. `npm run wp:playground`) i uruchom `npm run test:visual`.** Nowy blok dodaje swój partial do `theme/inc/styleguide/partials/` (szablon `_template.php`) – spec `tests/e2e/styleguide.spec.ts` obejmie go sam (axe, zrzut, konsola).

Jak pisać testy (`BlockTestCase`, Brain Monkey, `@group mariadb`, tagi e2e): `tests/README.md`.

Hooki (lefthook): pre-commit – ESLint, Stylelint, PHPCS na zmienionych plikach; pre-push – `npm run check`.

## Środowiska

Trzy środowiska WordPress (Playground dla agenta i testów, wp-env dla dev, Coolify dla stage'a): ADR 0011. Sprzątanie zasobów testowych: ADR 0015.

**Kiedy które.** (a) Playground (`test:php`, `test:e2e`): pętla TDD agenta, czysty WP przy każdym przebiegu, bez Dockera. (b) wp-env: praca Michała w panelu z danymi, które przetrwają restart, oraz testy na MariaDB przed PR (`check:full`) i awaryjnie e2e, gdy Playground zawiedzie. Agent nie uruchamia instancji dev bez prośby.

**wp-env** (Docker, WP 7.1.5, PHP 8.4, MariaDB 11.8, motyw jako `pw-theme`, wtyczki z `.wp/plugins`):

| Komenda | Co robi |
|---|---|
| `npm run env:start` / `env:stop` | instancja dev `.wp-env.json`, http://localhost:8888; `stop` zachowuje bazę |
| `npm run env:cli -- <args>` | `wp <args>` w instancji dev |
| `npm run db:export` | baza dev → `.data/pw-<data>-<czas>.sql` i `.data/latest.sql` (`.data/` poza git) |
| `npm run db:import -- [plik] [--yes]` | `.data/<plik>` (domyślnie `latest.sql`) → baza dev, pyta przed nadpisaniem |
| `npm run seed -- [--force]` | `wp pw seed` w instancji dev (#54) |
| `npm run test:php:mariadb` | instancja testów `.wp-env.tests.json` (8889): start → PHPUnit `integration` (z `@group mariadb`) → `destroy`, także przy błędzie i Ctrl+C |
| `npm run test:e2e:wp-env` | jak wyżej, Playwright z `PW_E2E_BASE_URL=http://localhost:8889` |
| `npm run env:reset` | `destroy` instancji testów; dev nietknięty |

Skrypty testowe nigdy nie dotykają instancji dev. Gdy start instancji testów się nie uda (np. MariaDB nie wstanie), wp-env 12 odmawia `destroy` („Environment not initialized”); `test:*`, `env:reset` i `env:clean` robią wtedy `docker compose down --volumes --remove-orphans --rmi local` na pliku z `~/.wp-env/<instancja>/` (`tools/env/tests-instance.mjs`). Zajęty port: `WP_ENV_PORT=<port> npm run env:start` (działa też dla `test:php:mariadb` i `test:e2e:wp-env`).

**Kopia przed eksperymentem:** `npm run db:export` → eksperyment → w razie potrzeby `npm run db:import`. Instancję dev usuwa się (`npx wp-env destroy`) tylko po `db:export`.
