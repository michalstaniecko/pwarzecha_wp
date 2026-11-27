# Kontenery i wolumeny testowe usuwamy od razu po użyciu; trwała zostaje tylko instancja dev

Uzupełnia ADR 0011 (trzy środowiska) i ADR 0010 (CI).

Agent i lokalne skrypty uruchamiają Dockera wiele razy dziennie: instancja testów wp-env na MariaDB (port 8889), kontener Playwright do testów wizualnych, lokalna próba obrazu stage'a przez `docker compose`. Każde takie uruchomienie zostawia kontenery, a przede wszystkim wolumeny z bazą i plikami WordPressa, które po zakończeniu pracy nie są nikomu potrzebne. Bez reguły zajmują dysk w nieskończoność, a wiszące wolumeny utrudniają też rozróżnienie, co jest trwałym środowiskiem, a co resztką po testach.

Decyzja: **każdy kontener, wolumen i sieć utworzone na potrzeby testu lub jednorazowego sprawdzenia usuwamy zaraz po zakończeniu użycia**, także gdy test się nie powiedzie albo zostanie przerwany. Jedynym wyjątkiem jest instancja dev wp-env (8888), bo jej trwała baza jest celem środowiska (b).

- **Instancja testów wp-env (MariaDB).** Skrypty, które ją uruchamiają (`npm run test:php:mariadb`, część `npm run check:full`), po przebiegu wykonują `destroy` tej konfiguracji (kontenery, wolumeny, sieć), w bloku sprzątającym działającym też przy błędzie i `Ctrl+C` (`trap`/`finally`). Nie zatrzymujemy jej samym `stop`, bo wolumeny zostają.
- **Playwright w kontenerze** (testy wizualne, aktualizacja baseline'ów) zawsze przez `docker run --rm`, bez nazwanych wolumenów. Wyniki trafiają do zamontowanego katalogu w repo (ignorowanego przez git), nie do wolumenu.
- **Lokalna próba obrazu stage'a** (`docker compose`) kończy się `docker compose down --volumes --remove-orphans`. Obraz zbudowany na próbę usuwamy razem z wiszącymi warstwami (`docker image prune` dla obrazów projektu), obrazy bazowe (`wordpress`, `mariadb`, `playwright`) mogą zostać w cache.
- **Playground CLI** nie używa Dockera; jego katalogi tymczasowe (mounty, SQLite) leżą w katalogu tymczasowym i są usuwane po przebiegu. Cache pobranych wersji WordPressa może zostać.
- **Rozpoznawalność.** Zasoby testowe mają stały prefiks projektu Compose / nazwy `pw-test`, żeby sprzątanie mogło je wybrać bez ryzyka usunięcia instancji dev albo zasobów innych projektów.
- **Komenda awaryjna.** `npm run env:clean` usuwa wszystkie pozostałości testowe tego projektu (instancja testów wp-env, zasoby `pw-test*`), nigdy nie dotyka instancji dev, i wypisuje `docker system df` przed i po. `npm run env:status` pokazuje, co z projektu działa i ile zajmuje.
- **Agent** po zakończeniu zadania sprawdza, że poza instancją dev nie zostały kontenery ani wolumeny projektu (`npm run env:status`), a jeśli zostały, uruchamia `npm run env:clean`. Reguła trafia do CLAUDE.md razem z opisem komend.

Odrzuciliśmy: zostawianie instancji testów między przebiegami dla szybszego startu (zysk kilkudziesięciu sekund nie równoważy rosnącego zajęcia dysku; szybką pętlę agenta zapewnia Playground, ADR 0011) oraz globalne `docker system prune --volumes` (usunęłoby trwałą bazę dev i zasoby innych projektów).

## Konsekwencje

- Pierwsze uruchomienie testów na MariaDB w każdej sesji trwa dłużej, bo instancja powstaje od zera; to akceptowalne, bo te testy biegną w `check:full`, nie w każdej pętli TDD.
- Na runnerach GitHub Actions sprzątanie nie jest potrzebne (runner jest efemeryczny), ale te same skrypty działają tam bez zmian.
- Stage na Coolify i jego wolumeny (baza, `uploads`) są trwałe i poza zakresem tej decyzji.
- Instancję dev usuwa się tylko świadomie, po `npm run db:export`.
