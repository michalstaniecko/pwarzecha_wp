# Motyw blokowy (FSE): szablony w HTML, wygląd wyłącznie z tokenów `theme.json`

Motyw jest motywem blokowym (Full Site Editing). Szablony (`templates/*.html`) i części szablonów (`parts/*.html`, np. nagłówek i stopka) składają się z bloków, a nie z klasycznych plików PHP (`page.php`, `header.php` itd.). Cały wygląd wynika z tokenów w `theme.json`: kolory, typografia, odstępy, szerokości layoutu i style bloków. CSS bloków korzysta wyłącznie z custom properties generowanych z tokenów (`var(--wp--preset--…)`, `var(--wp--custom--…)`) i nie zawiera literalnych wartości kolorów, rozmiarów ani odstępów. `theme.json` jest źródłem prawdy dla design systemu (ADR 0002).

Wybieramy FSE, bo Zleceniodawca edytuje wszystko w jednym edytorze, a zmiana tokenu zmienia cały serwis. Do tego Claude Design czyta `theme.json`. Klasyczny motyw z szablonami PHP i ręcznym CSS byłby bardziej elastyczny dla dewelopera, ale rozjechałby się z design systemem i odebrał Zleceniodawcy kontrolę nad układem.

## Konsekwencje

- Zmiany w szablonach wprowadzone w Edytorze witryny zapisują się w bazie i nadpisują pliki motywu. Zmiany szablonów robimy w plikach, a przed wdrożeniem nowej wersji motywu sprawdzamy, czy w bazie nie ma nadpisanych szablonów.
- Brakujący token dodajemy do `theme.json`. Nie wpisujemy wartości lokalnie w CSS bloku.
