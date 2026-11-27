# tools/ds

Kompilacja `tokens.json` design systemu (artefakt DS w Claude Design) do `tokens.css` dla makiet (ADR 0009, #32). Node bez zależności.

```sh
node tools/ds/tokens-css.mjs <tokens.json> [tokens.css]          # kompilacja (bez ścieżki wyjścia: stdout)
node tools/ds/tokens-css.mjs --check <tokens.json> <tokens.css>  # 0 = zgodne, 1 = rozjazd z listą różnic, 2 = złe argumenty albo nieczytelny plik
node --test tools/ds/                                            # testy
```

- `compile.mjs`: czysta funkcja `compileTokensCss(tokens, { name })`. Odtwarza kompilację strony DS (reguły z `artifact-type/reference/format.md` typu „Design System”): komentarze `usage`, zmienne `--text-<styl>`, klasy stylów tekstu, `@font-face`. Nazwa w nagłówku domyślnie z `tokens.json` `name`.
- `tokens-css.mjs`: CLI (pliki, kody wyjścia, diff).
- `fixtures/ds-tokens.json` i `fixtures/ds-bundle.css`: kopie z DS do testu referencyjnego (każda `var(--…)` z `bundle.css` ma definicję). Po zmianie tokenów lub komponentów w DS odśwież je.

Procedura po zmianie tokenów i odświeżanie makiet: sekcja „Użycie poza stroną DS” w README artefaktu DS.

Gdy wygląd makiet rozjedzie się z podglądem w DS, najpierw porównaj `compile.mjs` z aktualnym `format.md` typu artefaktu.
