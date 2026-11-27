# Zmiany atrybutów bloków po starcie treści: zasada addytywna, normalizator w PHP i `migrate` tylko dla atrybutów

Doprecyzowuje ADR 0001 i ADR 0006. Usuwa sprzeczność między ADR 0001 („bez `deprecated` i bez migracji treści”) a rozwiązaniem #12 („migracja w kodzie: `render.php` / `deprecated`”).

ADR 0001 ma rację co do markupu: bloki są dynamiczne, `save()` zwraca `null` albo `<InnerBlocks.Content />`, więc zmiana HTML w `render.php` nie wywołuje błędów walidacji i nie wymaga `deprecated`. Nie rozwiązuje jednak zmian **atrybutów**. Gdy na stage'u są już treści Zleceniodawcy (ADR 0006), zmiana nazwy albo znaczenia atrybutu zostawia w bazie stary kształt. Front go nie rozumie, a edytor po otwarciu strony po cichu go gubi przy zapisie.

Ustalamy:

1. **Zasada addytywna (domyślna).** Nowe atrybuty dodajemy z wartością domyślną, która odtwarza dotychczasowe zachowanie. Nie zmieniamy nazw, typów ani znaczenia istniejących atrybutów i nie usuwamy ich.
2. **Gdy zmiana jest konieczna:** blok dostaje normalizator `pw_<blok>_normalize_attributes( array $attrs ): array` w PHP, wołany na początku `render.php`, który przepisuje stary kształt na nowy. Edytor dostaje wpis w `deprecated` z `attributes` (stary schemat), `save: () => null` (albo `<InnerBlocks.Content />`) i `migrate` z tą samą logiką. `deprecated` służy tu wyłącznie migracji atrybutów, nigdy markupu.
3. **Testy:** PHPUnit renderuje blok ze starym kształtem atrybutów i porównuje z nowym, Vitest sprawdza `migrate`. Fikstura starego kształtu zostaje w repo na stałe.
4. Do chwili, gdy Zleceniodawca zaczyna wpisywać treści na stage'u, zmiany atrybutów są wolne (bez migracji), bo seed odtwarza treść.

Odrzuciliśmy: migracje bazy skryptem WP-CLI przy wdrożeniu (na Zenbox wdrażamy ręcznie zipem, a treść przenosi wtyczka migracyjna, więc skrypt nie miałby gdzie się uruchomić) i całkowity zakaz zmian atrybutów (blokowałby poprawki po starcie treści).

## Konsekwencje

- Każdy issue bloku po starcie treści na stage'u, który zmienia atrybuty, musi wskazać, czy zmiana jest addytywna; jeśli nie, dodaje normalizator, `deprecated.migrate` i oba testy.
- ADR 0001 dostaje adnotację: „bez `deprecated`” dotyczy markupu; migracje atrybutów według ADR 0012.
