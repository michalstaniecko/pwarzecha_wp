# Trzy środowiska WordPress: Playground dla agenta, wp-env z trwałą bazą dla dev, Coolify dla stage'a

Uzupełnia ADR 0005 (wybór środowiska testów) i ADR 0006 (stage). Zastępuje w #12 punkt „PHPUnit (WP test suite w wp-env)” jako jedyny runner oraz „Jest” jako runner JS.

Agent pracuje w krótkich pętlach TDD i potrzebuje środowiska, które startuje w sekundy i za każdym razem jest czyste. Michał potrzebuje lokalnej instancji, w której dane przetrwają restart. Zleceniodawca potrzebuje stałego stage'a z treściami. To trzy różne wymagania, więc mamy trzy środowiska:

- **(a) Agent i testy: WordPress Playground CLI (`@wp-playground/cli`).** PHPUnit integracyjny przez `npx @wp-playground/cli php --auto-mount` (czysty WP przy każdym przebiegu), e2e przez `runCLI({ command: 'server', blueprint })`. Stan startowy to blueprint w repo, który uruchamia `wp pw seed`. Bez Dockera, bez trwałego stanu, reset = kolejne uruchomienie. Testy jednostkowe PHP (Brain Monkey) w ogóle nie potrzebują WordPressa.
- **(b) Dev z trwałą bazą: `wp-env` (runtime Docker).** Instancja dev (8888, MariaDB `lts`, wolumen przetrwa `stop/start`), `npm run db:export`/`db:import` do `.data/`, `npm run seed` tylko na żądanie. Druga konfiguracja (`.wp-env.tests.json`, port 8889; `testsEnvironment` jest w wp-env 12 przestarzałe) służy jako instancja testów na MariaDB.
- **(c) Stage: Coolify** z obrazu budowanego z `Dockerfile` w repo (ADR 0006). Obraz bazuje na `wordpress:7.1.5-php8.4-apache`, ale motyw i mu-pluginy leżą w obrazie poza wolumenem `/var/www/html`, a wrapper entrypointu synchronizuje je przy każdym starcie. Trwałe są tylko baza i `wp-content/uploads`. Bez tego oficjalny obraz nie nadpisze motywu przy redeployu, bo kopiuje pliki do `/var/www/html` tylko przy pustym wolumenie.

**Zgodność bazy.** Playground używa SQLite, a stage i Zenbox MySQL/MariaDB. Przyjmujemy trzy zasady: (1) kod motywu nie używa surowego SQL, tylko API WordPressa (`WP_Query`, meta, opcje); (2) test, który musi dotknąć `$wpdb`, ma `@group mariadb` i jest pomijany na Playground; (3) pełny PHPUnit na MariaDB (`npm run test:php:mariadb`, instancja tests z wp-env) uruchamiamy w `npm run check:full` i w workflow release. Jeśli suite WP nie przejdzie na Playground (spike w issue środowiska agenta), domyślnym runnerem integracji zostaje wp-env, a Playground tylko e2e.

**JS edytora: Vitest zamiast Jest.** `@wordpress/scripts` 37 uruchamia w `test-unit-js` Vitest, a Jest jest w trybie utrzymania. Narzędzia w `tools/` zostają na `node:test`.

Odrzuciliśmy: sam wp-env dla agenta (start i reset w dziesiątkach sekund, wymaga Dockera w każdej pętli), runtime `playground`/`php` w wp-env (eksperymentalne, bez `wp-env run`), jednoklikowy serwis WordPress w Coolify (nie buduje z gita, wolumen na całe `/var/www/html`).

## Konsekwencje

- Wersje wszędzie: WP 7.1.5, PHP 8.4 (do potwierdzenia na Zenboxie), Node 26 LTS.
- Blueprint Playground i seed WP-CLI to jedno źródło stanu startowego dla e2e, Lighthouse, wp-env (na żądanie) i stage'a (tylko pusta baza).
- Hooki lefthook: pre-commit statyka na zmienionych plikach, pre-push `npm run check` w wersji szybkiej (Playground, bez e2e).
- Stage ma `WP_ENVIRONMENT_TYPE=staging`, wp-env `local`, Playground `local`, Zenbox `production`.
