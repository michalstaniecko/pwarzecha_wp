# Sekcja to kontener `pw/section`, Szablon oferty to starter pattern, motyw ma stałą strukturę katalogów

Rozstrzyga #9 (architektura bloków i mechanizm Szablonu oferty) oraz brak decyzji o strukturze plików motywu i testów.

**Sekcja.** Sekcję buduje się z trzech warstw: kontener `pw/section` (tło `base`/`light`/`navy`, kotwica, szerokość `wide`/`content`, rytm odstępów i linia hairline między Sekcjami o tym samym tle), nagłówek `pw/section-heading` (nadtytuł, H2, lead, wyrównanie) i blok komponentu w InnerBlocks (np. `pw/faq`, `pw/tiles`). Gotowe Sekcje dostarczamy jako wzorce (Biblioteka Sekcji). Dzięki temu logika tła jest w jednym miejscu, a komponenty takie jak Film, Galeria czy CheckList działają też w treści Aktualności bez własnego tła. Wyjątki: Hero i CtaBar mają własne tło i są samodzielnymi blokami najwyższego poziomu. Kotwicę `#kontakt` nadaje sam `pw/contact` (tylko pierwszy na stronie), pozostałe kotwice (`#grafik`) ustawia `pw/section`.

**Szablon oferty.** Wszystkie Strony oferty używają szablonu `page.html` (header → okruszki → `core/post-content` → footer). Szablon oferty to **starter pattern** (`blockTypes: ["core/post-content"]`, `postTypes: ["page"]`) z 21 Sekcjami w kolejności i z tłami z #31, pokazywany przy tworzeniu nowej strony. Bez `templateLock`, żeby Sekcje dało się kopiować i przesuwać; jeśli WP wstawi wzorzec jako `contentOnly`, włączamy `disableContentOnlyForUnsyncedPatterns`. Lokalizacje strony wskazuje meta `pw_lokalizacja` (tablica ID Lokalizacji, bez wartości specjalnej „wszystkie”) w panelu dokumentu; czytają ją Grafik, Lokalizacja i moduł schemy, a atrybut bloku może ją nadpisać. Bio Trenera to synced pattern „Trener” (`wp_block`), którego plik motywu nie dostarczy, więc tworzą go idempotentnie seed i migracja przy aktywacji motywu; starter pattern wstawia `core/block` z ID rozwiązywanym w PHP.

**Struktura repo i motywu.**

```
theme/                    # motyw, w ZIP jako katalog pw-theme/
  style.css functions.php theme.json
  templates/ parts/ patterns/
  src/blocks/<slug>/      # block.json, render.php, edit.js, style.css, *.test.js
  src/facade/ src/editor/ # moduł fasad (front), wtyczki edytora
  src/styles/ src/admin/  # style bloków core i block styles, skrypty ekranów admina
  build/                  # wynik wp-scripts (poza git, w ZIP)
  inc/helpers/ inc/data/ inc/schema/ inc/styleguide/ inc/editor/ inc/migrations/ inc/cli/
  assets/icons/ assets/fonts/ assets/images/
tests/php/unit/           # Brain Monkey, bez WP
tests/php/integration/    # WP test suite (Playground lub wp-env)
tests/e2e/                # Playwright (+ blueprint.json)
tools/                    # narzędzia Node (node:test), m.in. tools/ds, tools/icons, tools/release
docker/coolify/           # entrypoint, mu-plugins stage'a
```

Bloki rejestruje `wp_register_block_types_from_metadata_collection()` z manifestu `wp-scripts build --blocks-manifest`. Bloki bez logiki edytora używają `supports.autoRegister` (WP 7.0+). Prefiksy: PHP `pw_`, przestrzeń nazw `PW\`, bloki `pw/`, hooki `pw/…`. Kod danych w `inc/data/` (ADR 0007). Dostęp do danych globalnych tylko przez funkcje repozytoriów, które przepuszczają wynik przez filtr `pw/data/<nazwa>`.

Odrzuciliśmy: osobny szablon strony z Sekcjami wpisanymi w plik (strony nie mogłyby się różnić Sekcjami), Sekcję jako jeden blok z wbudowanym nagłówkiem (komponenty nie działałyby w treści wpisu bez tła), duplikowanie strony jako mechanizm Szablonu oferty (kopiuje stare treści i SEO).

## Konsekwencje

- E2E sprawdza, że całą Sekcję (`pw/section` z zawartością) da się wstawić, zduplikować i przesunąć.
- Ostrzeżenia w edytorze (navy więcej niż raz, ten sam H2 w dwóch CtaBar, ponad 6 Sekcji między punktami kontaktu, strona nie kończy się punktem kontaktu) są podpowiedziami, nie blokadami.
- Zmiana slugu strony nie psuje linków z kart ofert ani z Lokalizacji, bo zapisujemy ID strony, a URL liczymy w `render.php`.
