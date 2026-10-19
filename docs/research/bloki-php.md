# Bloki renderowane w PHP: opcje rejestracji i edycji w Gutenbergu

Research do issue #3. Stan na 2026-10-19. Kontekst: ADR 0001 (natywny Gutenberg, własny motyw `theme.json`, bloki renderowane w PHP, wygląd z tokenów, bez page buildera). Zleceniodawca (nietechniczny, SEO) ma kopiować i przesuwać Sekcje; frontend ma być bardzo lekki (Lighthouse mobile >= 95).

## Odpowiedź w skrócie

Najlepiej sprawdza się **model hybrydowy oparty wyłącznie na core**:

1. **Sekcje jako bloki dynamiczne `block.json` + `render.php`** z małym edytorem JS (`RichText` dla tekstów, `InnerBlocks` dla treści złożonej z bloków core). Edycja inline działa jak w Elementorze, a na froncie nie ma JS. Zmiana markupu w `render.php` obejmuje od razu wszystkie wystąpienia, bez deprecations. Wymaga builda (`@wordpress/scripts`), ale tylko dla edytora.
2. **Rejestracja wyłącznie w PHP (`supports.autoRegister`, WP 7.0+)** tylko dla prostych bloków „konfiguracyjnych” bez tekstu edytowanego inline: listy wpisów, mapa, dane kontaktowe z opcji. Edycja odbywa się w panelu bocznym, a podgląd przez ServerSideRender. Bez InnerBlocks i bez RichText.
3. **Biblioteka Sekcji jako niesynchronizowane wzorce (patterns) motywu**. Zleceniodawca wstawia gotową Sekcję z insertera, a potem ją kopiuje i przesuwa. Synchronizowane wzorce z Pattern Overrides stosujemy do elementów powtarzalnych (np. CTA), a **Block Bindings** do danych z meta/opcji.
4. **Wygląd wyłącznie z tokenów `theme.json`** (presety jako zmienne CSS, wyłączone wartości „custom”). Bloki korzystają z `supports` (kolor, odstępy, typografia), ograniczonych do presetów.
5. **ACF Blocks odrzucamy**: płatna zależność (ACF PRO) bez przewagi nad core w tym projekcie.

## Stan WordPressa (źródła pierwotne)

- WordPress 7.0 wyszedł 2026-05-20, a 7.1 wyszedł 2026-08-19 (bieżąca wersja to 7.1.3 z 2026-10-06). Wersja 7.2 jest planowana na 2026-12-10. [WordPress Versions](https://wordpress.org/documentation/article/wordpress-versions/)
- **Rejestracja bloków wyłącznie w PHP weszła do core w 7.0.** Wystarczy `register_block_type()` z `render_callback` i `'supports' => array( 'autoRegister' => true )`. [Dev note: PHP-only block registration](https://make.wordpress.org/core/2026/03/03/php-only-block-registration/), [Trac #64639](https://core.trac.wordpress.org/ticket/64639), [Block Supports: autoRegister](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- **Edytor w iframe:** w 7.0 iframe jest wymuszany, gdy wszystkie bloki we wpisie używają Block API v3. W 7.1 edytor wpisów działa w iframe zawsze. [Iframed Editor Changes 7.0](https://make.wordpress.org/core/2026/02/24/iframed-editor-changes-in-wordpress-7-0/), [Iframed Editor Changes 7.1](https://make.wordpress.org/core/2026/08/03/iframed-editor-changes-in-wordpress-7-1/)
- **Edycja wzorców w 7.0:** niesynchronizowane wzorce i części szablonu otwierają się domyślnie w trybie `contentOnly`. Można to wyłączyć ustawieniem `disableContentOnlyForUnsyncedPatterns`. Atrybuty treści oznacza się w `block.json` przez `"role": "content"`. [Pattern Editing in WordPress 7.0](https://make.wordpress.org/core/2026/03/15/pattern-editing-in-wordpress-7-0/)
- **Pattern Overrides dla własnych bloków (7.0)** włącza się przez filtr `block_bindings_supported_attributes`. [Pattern Overrides in WP 7.0](https://make.wordpress.org/core/2026/03/16/pattern-overrides-in-wp-7-0-support-for-custom-blocks/)
- **Block Bindings API** pojawiło się w 6.5. W 6.7 doszła rejestracja źródeł w JS z edycją (`setValues`, `canUserEditValue`), a w 6.9 źródła `core/post-data` i `core/term-data` oraz filtry obsługiwanych atrybutów. [Block Bindings reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/)
- **Rejestracja wielu bloków z manifestu** (`wp_register_block_types_from_metadata_collection`, 6.8). Manifest generuje `wp-scripts build --blocks-manifest`. [Dev note 6.8](https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/), [Registration of a block](https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/)

## Porównanie opcji

| Opcja | Edycja inline / kopiowanie i przesuwanie Sekcji | JS na froncie | Build | Płatne wtyczki | Trwałość treści przy zmianie markupu |
|---|---|---|---|---|---|
| A. `block.json` + `render.php` + edytor JS (`RichText`/`InnerBlocks`) | Najlepsza: pisanie bezpośrednio w kanwie, zagnieżdżone bloki core; kopiowanie i przesuwanie natywne | 0 (chyba że `viewScriptModule`) | Tak (`@wordpress/scripts`) | Nie | Wrapper w PHP, więc bez deprecations. Z `InnerBlocks` zapisuje się HTML bloków core (ich deprecations obsługuje core) |
| B. `block.json` + `render.php` + `ServerSideRender` w edytorze | Słaba: podgląd z serwera, edycja tylko w panelu bocznym, round-trip REST | 0 | Minimalny (lub bez JSX) | Nie | Pełna: zapisane są tylko atrybuty |
| C. PHP-only (`autoRegister`, 7.0+) | Jak B: autogenerowane kontrolki (string, number, integer, boolean, enum) w panelu, bez InnerBlocks i RichText | 0 | Nie | Nie | Pełna: zapisane są tylko atrybuty |
| D. ACF Blocks v3 | Dobra: inline dla pól Text i Textarea, popup dla reszty; Repeater i Flexible Content tylko w panelu | Nie zweryfikowano | Nie | **Tak (ACF PRO)** | Render PHP, dane w komentarzu bloku |
| E. Wzorce (unsynced/synced) + Pattern Overrides | Wzorzec to „szablon Sekcji” z bloków A/C/core. Unsynced w 7.0 domyślnie w `contentOnly` | 0 | Nie | Nie | Unsynced to kopia (zmiana wzorca nie aktualizuje wstawionych instancji); synced aktualizuje się globalnie |
| F. Block Bindings | Wartości z meta/opcji/własnego źródła; edycja w kanwie, jeśli źródło ma `setValues` + `canUserEditValue` | 0 | Tylko przy edycji (źródło JS) | Nie | Wartość pobierana dynamicznie |

### A. Blok dynamiczny z edytorem JS (rekomendowany dla Sekcji)

- Blok dynamiczny zapisuje tylko komentarz z atrybutami (`save` zwraca `null`), więc edytor **pomija walidację markupu**. Zmiana struktury w `render.php` obejmuje od razu wszystkie wystąpienia. Jeśli blok ma `InnerBlocks`, `save` musi zwracać `<InnerBlocks.Content/>`, a `render.php` dostaje je w `$content`. [Static or Dynamic rendering](https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/)
- Assety z `style`, `viewStyle`, `viewScript` i `viewScriptModule` są ładowane na froncie **tylko, gdy blok jest na stronie**. [block.json metadata](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)
- Koszt: komponent `edit` w JS/JSX, który musi odzwierciedlać markup z PHP (ryzyko rozjazdu podglądu z frontem). Build przez `@wordpress/scripts` z manifestem bloków.

### B. ServerSideRender

Dokumentacja pakietu nazywa go „fallback or legacy mechanism”, który „is not appropriate for developing new features against”. Podgląd pochodzi z endpointu `/wp/v2/block-renderer/:block` i jest debounce'owany. [ServerSideRender](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-server-side-render/)

### C. Rejestracja wyłącznie w PHP (`autoRegister`)

- Podgląd w edytorze idzie przez ServerSideRender, a bloki domyślnie mają API v3. [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- Ograniczenia według dev note: brak InnerBlocks („no clear vision”), brak atrybutów źródłowych (HTML), brak pól media, textarea i rich-text. Kontrolki nie powstają dla atrybutów z `role: local` ani dla typów array i object. `postId` z kontekstu jest dostępne tylko przy pierwszym renderze. [Dev note](https://make.wordpress.org/core/2026/03/03/php-only-block-registration/)
- Gutenberg 23.8 dodał `autoRegister` do schematu `block.json` i dokumentacji. [What's new in Gutenberg 23.8](https://make.wordpress.org/core/2026/08/19/whats-new-in-gutenberg-23-8-19-august/)
- Wniosek: dla Sekcji z tekstami i obrazami to za mało. Pasuje do bloków „danych” (np. lista realizacji, mapa, blok kontaktu z opcji motywu).

### D. ACF Blocks

- ACF Blocks to funkcja PRO. [ACF block.json config](https://www.advancedcustomfields.com/resources/acf-block-configuration-via-block-json/)
- Inline editing działa tylko w Blocks v3 (ACF PRO 6.7+). Pola Text i Textarea edytuje się w kanwie, pozostałe przez popup. Repeater, Flexible Content i Clone nie mają edycji inline. [Inline editing for ACF Blocks](https://www.advancedcustomfields.com/resources/inline-editing-for-acf-blocks/)
- Według ACF tryb edycji v2 jest niesprawny w edytorze iframe WP 7.1; od ACF 6.8.9 domyślnie obowiązuje v3. [ACF 6.8.9](https://www.advancedcustomfields.com/blog/acf-6-8-9-released/)
- Wniosek: działa, ale dokłada płatną zależność i drugi model danych, a core (A + C) pokrywa te same potrzeby.

### E. Wzorce (patterns)

- Wzorce motywu (`/patterns/*.php`) służą jako biblioteka gotowych Sekcji w inserterze.
- W 7.0 wstawiony wzorzec niesynchronizowany otwiera się w `contentOnly`, czyli edytuje się teksty i media, a pełną edycję daje „Edit pattern”. Bloki bez atrybutów z `role: content` są w tym trybie niewidoczne w List View i niewybieralne. **Własne bloki Sekcji muszą więc oznaczać atrybuty treści `"role": "content"`.** Dev note nie mówi wprost, czy w `contentOnly` można swobodnie kopiować i przesuwać całe Sekcje, więc trzeba to sprawdzić w prototypie. Ewentualnie można włączyć `disableContentOnlyForUnsyncedPatterns`. [Pattern Editing 7.0](https://make.wordpress.org/core/2026/03/15/pattern-editing-in-wordpress-7-0/)
- Synced patterns z Pattern Overrides pozwalają mieć globalny układ z lokalną treścią. Komentarze pod dev note zgłaszają, że wszystkie nadpisywalne pola muszą być wypełnione, a motyw nie może dostarczać wzorców synchronizowanych. [Pattern Overrides 7.0](https://make.wordpress.org/core/2026/03/16/pattern-overrides-in-wp-7-0-support-for-custom-blocks/)

### F. Block Bindings

Domyślnie wspierane atrybuty to `core/image` (url, alt, title, caption, id), `core/heading` i `core/paragraph` (content), `core/button` (url, text, linkTarget, rel), `core/navigation-link` (url) oraz `core/post-date`. Atrybuty własnych bloków dodaje się filtrem `block_bindings_supported_attributes` (6.9+). Źródło PHP rejestruje się przez `register_block_bindings_source()`. Edycja w kanwie wymaga źródła JS z `setValues` i `canUserEditValue`. [Block Bindings](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/)

Zastosowanie: wspólne dane (telefon, adres, NAP pod SEO lokalne) wpisywane raz i pokazywane w wielu Sekcjach.

### Tokeny z `theme.json`

- Presety generują zmienne `--wp--preset--{kategoria}--{slug}`, a `settings.custom` generuje `--wp--custom--{nazwa}`. [Global Settings & Styles](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)
- Wybór ogranicza się do tokenów przez `color.custom: false`, `color.defaultPalette: false`, `typography.customFontSize: false` i `spacing.customSpacingSize: false`, z wyjątkami per blok w `settings.blocks`. (to samo źródło)
- `render.php` używa zmiennych CSS i klas presetów, a `get_block_wrapper_attributes()` dokłada klasy i style z `supports`, więc wygląd zostaje przy tokenach.
- 7.1 dodało stany pseudo (hover/focus) i responsywne style w Global Styles, ale na razie tylko dla wybranych bloków core. [Pseudo states 7.1](https://make.wordpress.org/core/2026/08/05/pseudo-and-custom-style-states-in-wordpress-7-1/), [Responsive styles 7.1](https://make.wordpress.org/core/2026/08/05/responsive-block-styles-and-configurable-viewports-in-wordpress-7-1/)

## Otwarte kwestie (do prototypu)

1. Czy w `contentOnly` (7.0+) Zleceniodawca może duplikować i przesuwać Sekcję wstawioną z wzorca, czy trzeba włączyć `disableContentOnlyForUnsyncedPatterns`.
2. Czy CSS bloków core ładuje się na froncie tylko dla użytych bloków w motywie blokowym. Wspomina o tym komentarz pod Field Guide 7.1, ale nie dev note, więc trzeba to zmierzyć w Lighthouse.
3. Czy `supports` (kolor, spacing) działają dla bloków `autoRegister`. Twierdzi tak tylko komentarz pod dev note.
4. Czy ACF Blocks ładują JS na froncie: niezweryfikowane (nieistotne, jeśli odrzucamy ACF).
