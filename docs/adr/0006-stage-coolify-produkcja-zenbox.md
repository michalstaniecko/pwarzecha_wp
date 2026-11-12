# Stage na Coolify z treściami klienta, produkcja na Zenboxie z ręcznym zipem

Mamy dwa różne cele wdrożenia. **Produkcja** to współdzielony hosting Zenbox bez deployu z Git: motyw wgrywamy ręcznie jako zip zbudowany z tagu `vX.Y.Z` (GitHub Release). **Stage** to Coolify: obraz Dockera budowany z `Dockerfile` i `compose.yml` z repo, auto-deploy z gałęzi `develop`. Stage nie jest tylko podglądem. To na nim Zleceniodawca wprowadza docelowe treści przed premierą, a na Zenbox przenosimy je wtyczką migracyjną (UpdraftPlus lub podobną), bez własnych skryptów migracji.

Zenbox to hosting wybrany przez klienta i tam zostaje produkcja. Coolify daje stały, odtwarzalny z repo stage, który nie zużywa minut GitHub Actions (obraz buduje się na serwerze Coolify). Odrzuciliśmy wariant „produkcja w trybie wkrótce jako staging”, bo po premierze nie byłoby gdzie bezpiecznie pokazywać zmian.

## Konsekwencje

- Stage jest chroniony bramką tokenu podglądu (mu-plugin `pw-preview-mode.php` obecny tylko w obrazie Coolify), nie basic auth. Noindex i wyłączenie analityki wynikają z `WP_ENVIRONMENT_TYPE`, nigdy z opcji `blog_public`, bo opcje z bazy przechodzą migracją na produkcję.
- Seed treści przykładowych uruchamia się tylko na pustej bazie, a redeploy nie dotyka wolumenów bazy i uploads.
- Od chwili, gdy na stage'u są treści klienta, każda zmiana atrybutów bloku musi być wstecznie zgodna (migracja w kodzie i test PHPUnit na starym kształcie).
- Coolify nie robi backupów. Jedyną kopią treści przed premierą jest to, co wykona wtyczka migracyjna.
- Cały kod projektu, łącznie z modelem danych, jest w motywie (ADR 0007), więc wdrożenie to jeden zip.
