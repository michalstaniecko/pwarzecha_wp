# Lekki CI w GitHub Actions na każdym PR, pełny zestaw testów tylko na `develop`, etykietę lub tag

Zastępuje sekcję „Testy uruchamiamy lokalnie, nie w GitHub Actions” z ADR 0005 i punkt „GitHub Actions tylko na tag `v*` / `workflow_dispatch`” z rozwiązania #12. Reszta ADR 0005 (TDD, warstwy testów, `npm run check` jako jedna komenda, lefthook) obowiązuje.

ADR 0005 zakładał, że workflowy nie startują na push ani na PR, bo darmowa pula minut (repo prywatne, plan Free: ok. 2000 min Linux/mies.) skończyłaby się przy wielu commitach. Teraz motyw buduje agent w wielu równoległych gałęziach i PR-ach, a Michał chce widzieć w PR obiektywny wynik testów, zanim scali zmianę. Sam lokalny `npm run check` nie wystarcza jako bramka: agent może go pominąć, a wynik nie zostaje w PR.

Wybieramy podział według kosztu testu:

- **`ci.yml` na `pull_request` (do `develop` i `main`)**: tylko szybkie warstwy. Analiza statyczna (PHPCS, PHPStan, ESLint, Stylelint, walidacja `theme.json`), Vitest, `node --test tools/`, PHPUnit unit (Brain Monkey) i integracja (WP test suite) na WordPress Playground CLI, bez Dockera. Joby wybiera `dorny/paths-filter` (zmiana tylko w `docs/` nie uruchamia nic poza strażnikiem). Szkice PR są pomijane, `concurrency` anuluje poprzedni przebieg tego samego PR. Cel: ≤ 5 min rozliczanych na przebieg.
- **`full.yml` na push do `develop`, na etykietę `ci:full` w PR i ręcznie**: e2e Playwright, w tym jeden spec po stronie styleguide (wizualna regresja i axe dla wszystkich bloków naraz), edytor (wstawianie, duplikowanie i przesuwanie Sekcji, Szablon oferty) i Lighthouse CI (tylko push do `develop` i ręcznie). E2E biega w kontenerze `mcr.microsoft.com/playwright:v1.65.1-noble`, więc baseline'y zrzutów są stabilne i nie trzeba cache'ować przeglądarek.
- **`release.yml` na tag `v*`**: wszystko z powyższych przez `workflow_call`, dodatkowo PHPUnit na MariaDB w wp-env (zgodność z bazą produkcyjną, ADR 0011), Theme Check i budowa ZIP z walidacją i GitHub Release.
- **Stage** dalej buduje Coolify z `develop` (ADR 0006), 0 minut Actions.

Odrzuciliśmy: pełny zestaw na każdym PR (e2e i Lighthouse to 10+ min, pula skończyłaby się w 2–3 tygodnie intensywnej pracy), brak CI na PR (status quo ADR 0005, bez widocznej bramki) i self-hosted runner (kolejna maszyna do utrzymania).

## Budżet minut (szacunek miesięczny przy intensywnej budowie)

| Workflow | Przebiegi/mies. | Min/przebieg | Razem |
|---|---|---|---|
| `ci.yml` (PR) | 80 | 4 | 320 |
| `full.yml` (push `develop`) | 35 | 10 | 350 |
| `full.yml` (etykieta `ci:full`) | 10 | 9 | 90 |
| `release.yml` | 4 | 20 | 80 |
| **Razem** | | | **~840 z ~2000** |

Zapas pokrywa ponowne uruchomienia. Limit wydatków na Actions ustawiamy na 0 USD, więc przekroczenie zatrzymuje workflowy zamiast generować koszt; wtedy wracamy do samego `npm run check` lokalnie.

## Konsekwencje

- Wszystkie workflowy: `runs-on: ubuntu-26.04` (pin), Node z `.nvmrc` (26) przez `actions/setup-node@v7` z `cache: npm`, PHP 8.4 przez `shivammathur/setup-php@v2`, Composer przez `ramsey/composer-install@4`, `permissions: contents: read` (zapis tylko w jobie release), `timeout-minutes` na każdym jobie, artefakty (raport, trace, diff zrzutów) tylko przy porażce, `retention-days: 7`.
- `npm run check` nadal jest lokalną bramką przed pushem i odpowiada zakresowi `ci.yml`. `npm run check:full` odpowiada `full.yml`.
- Testy wizualne nie działają na macOS bez Dockera. Baseline'y aktualizuje się w tym samym kontenerze (`npm run test:visual:update`) albo przez `workflow_dispatch` z wejściem `update_snapshots`, które zwraca nowe zrzuty jako artefakt.
- Dodajemy etykietę `ci:full` w repo.
- Jedyne miejsce testów wizualnych i a11y wszystkich bloków to strona styleguide. Testy stron (Lighthouse, smoke szablonów) są celowo nieliczne.
