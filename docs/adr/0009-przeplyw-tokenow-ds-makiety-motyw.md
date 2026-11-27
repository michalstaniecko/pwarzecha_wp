# Tokeny płyną z DS do makiet przez skompilowany `tokens.css`; motyw dostaje je tylko przez `theme.json`

Design system „Samoobrona Przemek” w Claude Design trzyma tokeny w `tokens.json`, a `tokens.css` generuje tylko w runtime własnej strony, więc konsumenci DS nie mają go skąd pobrać (#32). Jedynym konsumentem `tokens.css` są makiety (artefakt Layout). Motyw FSE nie ładuje ani `tokens.css`, ani `bundle.css` z DS: `bundle.css` używa nazw DS (`var(--primary)`), a ADR 0003 wymaga w CSS bloków `var(--wp--preset--…)`. Motyw dostaje wartości tokenów wyłącznie przez `theme.json`.

Dopóki nie ma `theme.json`, źródłem prawdy jest DS `tokens.json`. Skrypt w repo (`tools/ds/tokens-css.mjs`, Node bez zależności, testy `node:test`) kompiluje z niego `tokens.css` według reguł `format.md` typu artefaktu. DS publikuje wynik jako zwykły plik `project/tokens.css`, a makiety kopiują go razem z resztą DS (`bundle.css`, `tokens.json`, fonty, README) po stronie serwera (`{artifact, path}`). Tryb `--check` wykrywa rozjazd `tokens.css` z `tokens.json` i jest krokiem procedury przed każdą publikacją DS i odświeżeniem makiet. Tokenów nie edytujemy w UI strony DS, tylko przez Claude Code ze skryptem.

Po napisaniu `theme.json` (zgodnie z ADR 0003 i rekomendacją z #4) źródło się odwraca: `theme.json` → wartości w DS `tokens.json` (tryb `theme-to-tokens`, mapowanie po slugu, opisy `usage` i próbki zostają w DS) → `tokens.css` → makiety.

Odrzuciliśmy: samą opisaną w README procedurę ręcznego generowania (rozjeżdża się bez narzędzia), generowanie `tokens.css` u każdego konsumenta osobno (wiele kopii kompilacji) i przejście na `theme.json` od razu (trzeba by pisać `theme.json` przed motywem tylko po to, żeby karmić makiety).

## Konsekwencje

- Każda zmiana tokenów w DS wymaga uruchomienia skryptu przed publikacją. Edycja w UI strony DS zostawia nieaktualny `tokens.css`; `--check` to wyłapie, ale tylko jeśli ktoś go uruchomi (CI nie ma dostępu do artefaktów).
- Sekcje „Fragment theme.json” i „Tabela tokenów” w README DS są ręczną kopią `tokens.json`. Przy sprzeczności wygrywa `tokens.json`. Usuwamy je przy przejściu na `theme.json`.
- README DS ma ręcznie pisaną sekcję „Użycie poza stroną DS” (inny tytuł niż generowana „Consuming this system”), która opisuje namespace, pliki do skopiowania i procedurę odświeżania.
