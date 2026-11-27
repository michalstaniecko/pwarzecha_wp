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
| `npm run check:full` | `check` + `test:php:mariadb` + `test:e2e` + `test:visual` + `test:lighthouse` (zakres `full.yml`) | przed PR z UI |
| `npm run lint` | `lint:js` (ESLint), `lint:css` (Stylelint, tylko tokeny – ADR 0003), `lint:php` (PHPCS), `lint:phpstan`, `lint:theme-json` (schemat w `tools/schemas/`) | pojedynczo przy poprawianiu błędów |
| `npm run test:tools` | `node --test tools/` | zmiany w `tools/` |
| `npm run test:js` | Vitest (`wp-scripts test-unit-js`) | logika edytora bloków |
| `npm run test:php:unit` / `test:php` | PHPUnit: unit (Brain Monkey) / unit + integracja (Playground) | kod PHP motywu |
| `npm run test:php:mariadb` | PHPUnit na MariaDB (wp-env tests) | w `check:full` |
| `npm run test:e2e`, `test:visual`, `test:visual:update`, `test:lighthouse` | Playwright, zrzuty styleguide, Lighthouse CI | w `check:full` |
| `npm run build` / `start` | `wp-scripts` build / watch | budowanie motywu |
| `npm run env:status` / `env:clean` | pokazuje / usuwa pozostałości testowe Dockera (`pw-test*`, instancja testów wp-env), nigdy instancji dev | na koniec zadania |

Hooki (lefthook): pre-commit – ESLint, Stylelint, PHPCS na zmienionych plikach; pre-push – `npm run check`.

## Środowiska

Trzy środowiska WordPress (Playground dla agenta i testów, wp-env dla dev, Coolify dla stage'a): ADR 0011. Sprzątanie zasobów testowych: ADR 0015.
