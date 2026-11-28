# Natywny Gutenberg z blokami renderowanymi w PHP zamiast page buildera

Brief prosi o edycję „jak w Elementorze” (kopiowanie i przesuwanie Sekcji), ale jednocześnie o maksymalną lekkość pod SEO. Wybieramy natywny edytor blokowy WordPressa i własny motyw oparty na `theme.json`, w którym wszystkie własne bloki są renderowane po stronie serwera (PHP), a cały wygląd wynika z tokenów design systemu. Kopiowanie i przesuwanie Sekcji zapewnia sam Gutenberg, bez narzutu page buildera (Elementor, Bricks, Kadence itp.).

## Uzupełnienie: dlaczego każdy własny blok jest renderowany w PHP

Każdy blok, który tworzymy, jest dynamiczny: jego HTML powstaje w `render.php` (lub w callbacku PHP) przy każdym wyświetleniu, a w treści wpisu zapisują się tylko atrybuty i ewentualnie InnerBlocks. Dzięki temu markup wszystkich już wstawionych wystąpień bloku zmieniamy jedną zmianą w kodzie, bez błędów walidacji bloków w edytorze, bez `deprecated` i bez migracji treści. Bloki statyczne (z markupem zapisanym w `save()`) są wykluczone. Dotyczy to też bloków z JS w edytorze: `save()` zwraca `null` albo tylko `<InnerBlocks.Content />`.

## Zmiany

- Zakaz `deprecated` dotyczy markupu. Migracje atrybutów: [ADR 0012](0012-zmiany-atrybutow-blokow.md).
