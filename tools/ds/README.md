# tools/ds

Tokeny design systemu (artefakt DS w Claude Design) i ich przepływ (ADR 0009). Node bez zależności.

**Źródło prawdy to `theme/theme.json`** (od #33). Zmiana tokenu zaczyna się w `theme.json` → `theme-to-tokens` (wartości do DS `tokens.json`) → kompilacja `tokens.css` → publikacja DS → odświeżenie makiet. Tokenów nie edytujemy w UI strony DS ani ręcznie w DS `tokens.json`.

```sh
node tools/ds/theme-to-tokens.mjs theme/theme.json <tokens.json> [tokens.css]  # theme.json → wartości w tokens.json (+ kompilacja tokens.css)
node tools/ds/theme-to-tokens.mjs --check theme/theme.json <tokens.json>       # 0 = zgodne, 1 = różnice (lista), 2 = złe argumenty albo nieczytelny plik
node tools/ds/tokens-css.mjs <tokens.json> [tokens.css]                       # sama kompilacja (bez ścieżki wyjścia: stdout)
node tools/ds/tokens-css.mjs --check <tokens.json> <tokens.css>               # tokens.css zgodny z tokens.json? (kody jak wyżej)
npm run tokens:check   # theme.json ↔ fixtures/ds-tokens.json, część `npm run check`
npm run tokens:map     # odświeża theme-map.json po zmianie theme-map.mjs
node --test tools/ds/  # testy
```

- `theme-map.mjs`: jedyne mapowanie wartość DS ↔ ścieżka w `theme.json` (czysta tablica). Format `px` (górna granica `clamp()`, presetu rozmiaru fontu albo `rem` → `px`) i `fluid-clamp` (rozmiar fontu z `fluid: { min, max }` → `clamp()` w zapisie DS dla zakresu `settings.typography.fluid`). Nowy token = wpis tu, preset w `theme.json`, token w DS, `npm run tokens:map`. `theme-map.json` to eksport dla testu PHPUnit (`tests/php/integration/ThemeJsonPresetsTest.php`); test `node:test` pilnuje, że jest aktualny.
- `theme-tokens.mjs`: czyste funkcje `themeToTokens(theme, tokens, map)` (zmienia tylko wartości; `usage`, próbki, grupy i kolejność kluczy zostają; slug bez tokenu w DS → ostrzeżenie, nie nowy token) i `diffThemeTokens(theme, tokens, map)`.
- `theme-to-tokens.mjs`: CLI (pliki, kody wyjścia). Zapisuje `tokens.json` z wcięciem 2 spacji, jak DS.
- `compile.mjs`: czysta funkcja `compileTokensCss(tokens, { name })`. Odtwarza kompilację strony DS (reguły z `artifact-type/reference/format.md` typu „Design System”): komentarze `usage`, zmienne `--text-<styl>`, klasy stylów tekstu, `@font-face`. Nazwa w nagłówku domyślnie z `tokens.json` `name`.
- `tokens-css.mjs`: CLI kompilacji (pliki, kody wyjścia, diff).
- `fixtures/ds-tokens.json` i `fixtures/ds-bundle.css`: kopie z DS do testów (`tokens:check`, test referencyjny: każda `var(--…)` z `bundle.css` ma definicję). Po zmianie tokenów uruchom `theme-to-tokens` także na `fixtures/ds-tokens.json`; po zmianie komponentów w DS odśwież `ds-bundle.css`.

Wartości, których DS nie ma (np. krój w stylach tekstu `settings.custom.typography.<styl>.fontFamily`, ustawienia `default*`), żyją tylko w `theme.json` i nie trafiają do DS. Fonty (`type.fonts`, pliki) nie są mapowane.

## Procedura po zmianie tokenu

1. Zmień wartość w `theme/theme.json`; `npm run lint:theme-json`.
2. `node tools/ds/theme-to-tokens.mjs theme/theme.json tools/ds/fixtures/ds-tokens.json`, potem `npm run check`.
3. W sesji z dostępem do artefaktu DS: pobierz `project/tokens.json`, `node tools/ds/theme-to-tokens.mjs theme/theme.json tokens.json tokens.css`, `--check` obu plików, zapisz oba do DS i opublikuj DS.
4. Odśwież makiety (Layout kopiuje cały DS po stronie serwera, sekcja „Użycie poza stroną DS” w README artefaktu DS).

Gdy wygląd makiet rozjedzie się z podglądem w DS, najpierw porównaj `compile.mjs` z aktualnym `format.md` typu artefaktu.
