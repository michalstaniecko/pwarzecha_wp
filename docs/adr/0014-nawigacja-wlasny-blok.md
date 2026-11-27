# Menu główne to własny blok `pw/site-nav` renderujący `wp_navigation` w markupie DS

Rozstrzyga sprzeczność między design systemem (dropdowny i hamburger na `<details>`, bez JS) a #30 (`core/navigation` z Interactivity API).

`core/navigation` generuje własny markup i własny overlay mobilny. Odtworzenie wyglądu DS 1:1 (grupy jako etykiety rozwijane kliknięciem, wyróżniony przycisk „Kontakt” na desktopie, przycisk telefonu na mobile i tablecie, panel na całą wysokość) wymagałoby nadpisywania jego CSS i zachowania, a każda aktualizacja WordPressa mogłaby to zepsuć. Interactivity API dokłada też runtime JS, a cel z ADR 0008 to brak JS na froncie poza nawigacją i fasadami.

Wybieramy własny blok dynamiczny `pw/site-nav`. Strukturę menu Zleceniodawca edytuje jak zwykle w Edytorze witryny jako wpis `wp_navigation` („Menu główne”), a blok w `render.php` czyta jego bloki (`core/navigation-link`, `core/navigation-submenu`) i wypisuje markup DS: grupy jako `<details>/<summary>`, hamburger jako `<details>` z panelem. Telefon pochodzi z danych globalnych (`pw/kontakt`). Do tego mały `viewScriptModule` (ok. 40 linii): Escape zamyka, kliknięcie obok zamyka, otwarcie jednej grupy zamyka pozostałe. Bez JS menu nadal działa (natywne `<details>`). Ten sam mechanizm (render `wp_navigation` jako listy) wykorzystują listy linków w stopce (`pw/menu-list`), bez JS.

Odrzuciliśmy: `core/navigation` ze stylami (walka z markupem core i Interactivity API) oraz menu zapisane na sztywno w części szablonu (Zleceniodawca nie mógłby go zmienić).

## Konsekwencje

- E2E menu: klawiatura (Tab, Enter, Escape), zamykanie po kliknięciu obok, panel mobilny, `aria-current="page"` i `.is-current` na grupie.
- Seed tworzy wpisy `wp_navigation`: „Menu główne” (z etykietą „Trener”), „Stopka – Oferta”, „Stopka – Na skróty”.
- #30 dostaje korektę opisu SiteHeader.
