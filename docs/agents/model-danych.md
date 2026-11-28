# Model danych (M2) – ściąga dla agenta

Jak bloki, schema i seed korzystają z modelu danych z milestone 2 (#48–#54, PR #101). Pojęcia: `GLOSSARY.md`. Decyzje: ADR 0007, ADR 0013, `docs/design/decyzje-ds-i-layoutow.md` §4–5. Testy: `tests/README.md`. Kod jest źródłem prawdy; ten plik mówi, gdzie patrzeć i czego kod sam nie powie.

## 1. Zasady

- **Czytanie tylko przez repozytorium.** Blok, szablon i schema czytają dane wyłącznie funkcjami `pw_*()` z tej ściągi; każda przepuszcza wynik przez filtr `pw/data/<nazwa>` (ADR 0013), dzięki czemu działa `with_data()` w testach i `pw_sg_with_data()` w styleguidzie. Bezpośrednie `get_option` / `get_post_meta` / `get_terms` na danych motywu tylko w `theme/inc/data/` (dla `pw_kontakt` pilnuje tego `tests/php/unit/Data/KontaktAccessTest.php`).
- **Wartości są surowe.** Repozytoria nie escapują; escapuj przy renderowaniu.
- **Zapis przez zarejestrowane meta.** Każdy klucz ma `sanitize_callback` (biegnie też przy REST, `update_post_meta` i `meta_input`) i `auth_callback` `\PW\Data\can_edit_post_meta`. Niezmienniki (jedna kotwica, jeden polecany, sortowanie Grafiku) pilnują hooki `added_post_meta` / `updated_post_meta`, nie metabox.
- **Gdzie co leży:**
  - `theme/inc/data/` – dane, ładowane z `index.php` (nowy plik = nowy `require_once`); `*-helpers.php`, `kontakt-format.php`, `karnet-cena.php` są czyste (ładowalne w Brain Monkey);
  - `edit-guards.php` – wspólne `can_edit_post_meta()` i `can_save_metabox( $post_id, $nonce_field, $nonce_action )`;
  - `class-private-posts-controller.php` – `PW\Data\Private_Posts_Controller` (`rest_controller_class` dla CPT tylko dla panelu);
  - `admin/` – ekran „Dane strony”, pole obrazu `PW\Data\Admin\render_media_field()`, `enqueue_admin_scripts()` (`admin/scripts.php`, bundle `pw-admin` z `theme/src/admin/`, ładowany na ekranie edycji Lokalizacji);
  - `theme/inc/schema/` – moduł schemy (§3), `theme/inc/cli/` – seed (§5), `theme/src/editor/page-panel/` – panel dokumentu (§4).

## 2. Obszary danych

### 2.1 Dane strony i Block Bindings `pw/kontakt`

- **Odczyt:** `pw_kontakt(): array<string,string>` – zawsze wszystkie klucze `PW_KONTAKT_DEFAULTS`, same stringi; `pw_kontakt_pole( $klucz )` – jedno pole, `''` dla nieznanego. Filtr `pw/data/kontakt` biegnie **przed** normalizacją: klucz pominięty przez filtr daje `''` (także `topbarTekst`).
- **Klucze:** `telefon`, `email`, `messenger`, `godzinyTelefonu`, `facebook`, `instagram`, `youtube`, `tiktok`, `nazwaFirmy`, `nip`, `adresRejestrowy`, `topbarTekst`, `trenerImie`, `trenerNazwisko`, `trenerNazwiskoWSchemie` (`'1'`/`''`).
- **Pusty = ukryty.** Zapisane `''` zostaje `''`. Domyślna wartość jest tylko dla `topbarTekst` („Pierwszy trening bezpłatny”) i tylko wtedy, gdy klucza nigdy nie zapisano.
- **Formaty zapisu:** telefon `+48 600 000 000` (`pw_phone_format`), NIP 10 cyfr (wyświetlanie `pw_nip_format()` → `123-456-78-90`), URL-e tylko `https://`, Messenger `https://m.me/…` lub `https://www.messenger.com/…`. Helpery: `pw_phone_format`, `pw_nip_normalize`, `pw_nip_format`, `pw_email_short`; linki `pw_tel_href()`, `pw_mailto_href()` (`theme/inc/helpers/render.php`).
- **Zapis:** opcja `PW_KONTAKT_OPTION` (`pw_kontakt`, jedna tablica, autoload), ekran `pw-dane-strony`, uprawnienie `PW_KONTAKT_CAP` (`pw_manage_site_data`). `pw_kontakt_sanitize()`: nieznane klucze odpadają; błędna wartość zostawia zapisaną i dodaje settings error `pw_kontakt_<klucz>`. Hook `pw/data/kontakt/updated` ( $new, $old ) z pełnymi tablicami. REST: brak (`show_in_rest` false).
- **Bindings:** źródło `pw/kontakt`, `args: { "key": "…" }`, klucze `PW_KONTAKT_BINDING_KEYS` (klucze opcji bez `trener*` oraz wyliczane `telefonHref`, `emailHref`, `copyright` – „© <rok> <nazwaFirmy albo nazwa witryny>”; `nip` zwraca format z myślnikami). Nieznany klucz → `null`, blok zostaje przy swojej treści.

### 2.2 Lokalizacja

- **Odczyt:** `pw_lokalizacje( [ 'ids' => int[] ] = [] )` – tylko opublikowane, po `menu_order`, potem tytule (także z `ids`: kolejność to nadal `menu_order`, puste `ids` → `[]`); filtr `pw/data/lokalizacje` ( $list, $args ). `pw_lokalizacja( $id ): ?array` idzie przez `pw_lokalizacje()`, więc ten sam filtr; szkic / inny typ → `null`.
- **Kształt** (opis w `theme/inc/data/lokalizacja-repository.php`): `id`, `nazwa` (tytuł), `ulica` (z prefiksem „ul.”), `kod`, `miasto`, `kotwica` (domyślnie `sanitize_title( miasto )`), `geo` `{lat, lng}|null`, `mapaUrl`, `mapaEmbed` (tylko `https://www.google.com/maps/embed?…`), `dojazd`, `podgladId` (0 = brak), `film` `{youtubeId, miniaturaId}|null`, `stronaOfertyUrl` (permalink z ID, `''` gdy strona nieopublikowana).
- **Helpery** (`lokalizacja-helpers.php`): `pw_lokalizacja_adres( $l )` → „ul. Wolności 1, 41-500 Chorzów”, `pw_lokalizacja_grafik_url( $l )` → `home_url( '/grafik/' )#kotwica` (slug `/grafik/` na sztywno), `pw_youtube_id()` (do ponownego użycia w Filmie), `pw_lokalizacja_mapa_embed()`, `pw_lokalizacja_kotwica()`.
- **Meta** (stałe w `PW\Data\Lokalizacja`, typ i sanitizer w `fields()`): `ULICA` `pw_ulica`, `KOD` `pw_kod` (tylko `NN-NNN`, inaczej `''`), `MIASTO` `pw_miasto`, `KOTWICA` `pw_kotwica`, `GEO_LAT` / `GEO_LNG` `pw_geo_lat` / `pw_geo_lng`, `MAPA_URL`, `MAPA_EMBED`, `DOJAZD`, `PODGLAD_ID`, `FILM_YOUTUBE_ID`, `FILM_MINIATURA_ID`, `STRONA_OFERTY` `pw_strona_oferty` (ID strony). `POST_TYPE` = `lokalizacja`.
- **Niezmienniki:** geo nie ma `default`; `geo` jest `null`, gdy brakuje którejś współrzędnej **albo obie są 0** (0,0 = brak). Kotwica jest unikalna przy każdym zapisie `pw_kotwica` / `pw_miasto` (`keep_unique_anchor()`, zajęta → `chorzow-2`).
- **REST i front:** `/wp/v2/lokalizacja` przez `Private_Posts_Controller` – gość 401, subskrybent 403, edytor 200. CPT ma `supports: custom-fields` (bez tego REST nie zwraca `meta`). `/?p=<id>` daje 404 (`hide_from_front()`). Metabox: formularz `pw_lokalizacja[<meta>]`, nonce `pw_lokalizacja_nonce` / `pw_lokalizacja_save`.

### 2.3 Grafik i Grupy

- **Odczyt:** `pw_grafik( $lokalizacja_id )` – wiersze `{dzien 1–7 (1 = pon.), od, do "HH:MM", grupa (term ID), dzienNazwa, grupaNazwa}` po dniu, potem godzinie; `[]` dla ID spoza Lokalizacji; **dowolny status posta** (widoczność Lokalizacji decyduje wywołujący, np. przez `pw_lokalizacje()`); wiersze nieistniejących Grup pomijane. Filtr `pw/data/grafik` ( $wiersze, $lokalizacja_id ). `pw_grupy()` – `{id, nazwa, opis (przedział wieku), kolejnosc}` po `pw_kolejnosc`, potem nazwie; filtr `pw/data/grupy`. Helpery: `pw_grafik_dni()`, `pw_grafik_dzien_nazwa()`, `pw_grafik_godzina()`, `pw_grafik_sort()`.
- **Meta:** `PW\Data\Grafik\META` = `pw_grafik` (post meta Lokalizacji, tablica wierszy), `TAXONOMY` = `grupa`, `ORDER` = `pw_kolejnosc` (term meta, int, mniejsze = wcześniej).
- **Zapis:** `pw_grafik_sanitize()` po cichu odrzuca wiersze z dniem spoza 1–7, złą godziną, `od >= do`, brakującą lub nieistniejącą Grupą (**najpierw Grupy, potem wiersze**), normalizuje „H:MM” → „HH:MM” i **sortuje przy zapisie**. REST: `meta.pw_grafik` na `/wp/v2/lokalizacja/<id>` (zły kształt → 400). Formularz terminu zapisuje `pw_kolejnosc` tylko z `$_POST` – w kodzie ustaw `update_term_meta()` sam.
- **Usuwanie Grupy** używanej w jakimkolwiek Grafiku (także szkic i kosz) kończy się `wp_die()` 409 (`pre_delete_term`); link „Usuń” na liście jest wtedy ukryty. W PHPUnit `wp_die` rzuca `WPDieException`. Masowe usuwanie: #104.
- **REST Grup:** taksonomia ma `show_in_rest` bez prywatnego kontrolera, więc nazwy i opisy Grup są czytelne publicznie (`/wp/v2/grupa`).

### 2.4 Karnet i ceny

- **Odczyt:** `pw_karnety()` – opublikowane po `menu_order`, kształt `{id, nazwa, cena (int zł ≥ 0), okresMiesiace (≥ 1), okresEtykieta (np. „/ mies.”), obejmuje list<string>, polecany bool}`; filtr `pw/data/karnety`.
- **Ceny** (`karnet-cena.php`, czyste): `pw_cena_format( int )` → „1 200 zł” ze **spacją niełamiącą** jako separatorem tysięcy i przed „zł”; `pw_karnet_min_cena_miesieczna( ?array $karnety = null ): ?int` – minimum `floor( cena / okresMiesiace )` dla Teasera ceny, `null` bez Karnetów. PassCard pokazuje `cena` bez dzielenia.
- **Meta:** `PW_KARNET_POST_TYPE` (`karnet`), `PW_KARNET_CENA` `pw_cena`, `PW_KARNET_OKRES_MIESIACE` `pw_okres_miesiace`, `PW_KARNET_OKRES_ETYKIETA` `pw_okres_etykieta`, `PW_KARNET_OBEJMUJE` `pw_obejmuje` (tablica albo tekst, jeden punkt na linię), `PW_KARNET_POLECANY` `pw_polecany`. Pliki: `karnet.php` (CPT, meta, niezmiennik), `karnet-repository.php`, `karnet-metabox.php`.
- **Niezmiennik:** najwyżej jeden polecany – ustawienie flagi kasuje ją na pozostałych (każda ścieżka zapisu). Brak flagi = meta usunięte.
- **REST:** prywatny (`Private_Posts_Controller`, 401/403/200 jak Lokalizacja). Uprawnienia CPT domyślne (`post`).

### 2.5 Meta strony: `pw_lokalizacja`, zajawka

- **Odczyt:** `pw_strona_lokalizacje( $post_id )` – Lokalizacje strony w kształcie z §2.2, w **kolejności zaznaczenia** (nie `menu_order`), z pominięciem usuniętych i nieopublikowanych; filtr `pw/data/lokalizacje` (przez `pw_lokalizacja()`), potem `pw/data/strona_lokalizacje` ( $list, $post_id ). Nigdy nie czytaj surowego meta: sanitizer filtruje tylko w chwili zapisu.
- **Meta:** `PW\Data\PageMeta\LOKALIZACJA_KEY` = `pw_lokalizacja`, `int[]`, domyślnie `[]`. Sanitizer: tylko istniejące **opublikowane** Lokalizacje, unikalne, w podanej kolejności; nie-lista → `[]`. Bez wartości „wszystkie”: strona o obu salach zaznacza obie; „wszystkie” to atrybut bloku (ADR 0013).
- **Zajawka:** strony mają `excerpt` (`add_excerpt_support()`); lead Hero listy Aktualności (`home.html`) to zajawka strony „Aktualności”.

## 3. Schema (Yoast)

### 3.1 Struktura modułu

`theme/inc/schema/`: `nodes.php` (czyste budowniczki węzłów: `location_id()`, `location_node()`, `postal_address()`, `trener_id()`, `person_node()`, `service_node()`), `meta.php` (`TYP_KEY` `pw_schema_typ`, `AREA_KEY` `pw_area_served`, `typ( $id )`, `area_served( $id )`), `class-registry.php` (`Registry` na `wpseo_schema_graph_pieces` i `wpseo_schema_webpage`), `pieces/` (`Piece` + `Location_Piece`, `Trener_Piece`, `Service_Piece`). Bez Yoasta nic się nie ładuje i strona działa (pilnuje `tests/e2e/schema.spec.ts`).

### 3.2 Typy stron i węzły

`pw_schema_typ` ∈ `PW\Schema\TYPY` (`TYP_BRAK`, `TYP_OFERTA`, `TYP_KONTAKT`, `TYP_TRENER`, `TYP_SERVICE`), domyślnie `brak`; REST odrzuca inną wartość (400), `typ()` daje `brak` poza stronami.

| Strona | `SportsActivityLocation` | Inne węzły motywu |
| --- | --- | --- |
| strona główna (dowolny typ) | wszystkie `pw_lokalizacje()` | – |
| `kontakt` | wszystkie `pw_lokalizacje()` | – |
| `oferta` | `pw_strona_lokalizacje()` | – |
| `service` | `pw_strona_lokalizacje()` | `Service` (`name` = `serviceType` = tytuł, `areaServed` = `City` z `pw_area_served`, puste → `AREA_SERVED_DEFAULT` Chorzów, Katowice; bez Offer i ceny) |
| `trener` | – | `Person` (`@id` = `trener_id()`), WebPage dostaje `mainEntity` |
| `brak` (np. Grafik) | – (nawet z `pw_lokalizacja`) | – |

Location: `telephone` / `email` z `pw_kontakt()`, `url` = `stronaOfertyUrl`, `geo` tylko z niepustymi współrzędnymi, `@id` = `home_url( '/#/schema/lokalizacja/<kotwica>' )`. Person: imię z `trenerImie` (filtr `pw/schema/trener_imie`; puste → brak węzła), nazwisko z `trenerNazwisko` tylko przy `trenerNazwiskoWSchemie = '1'` (filtr `pw/schema/trener_nazwisko`).

### 3.3 Nowy węzeł przez `pw/schema/pieces` (#67)

Klasa `PW\Schema\Faq_Page_Piece extends PW\Schema\Piece` w `pieces/class-faq-page-piece.php` z `$identifier` (np. `pw-faq`), `is_needed()` i `generate()`. Helpery `Piece`: `page_id()`, `is_front_page()`, `typ()`, `organization()`. Rejestracja: `add_filter( 'pw/schema/pieces', fn( $pieces ) => [ ...$pieces, new Faq_Page_Piece() ] )`. Plik klasy ładuj **wewnątrz** tego callbacku albo dopisz do `Registry::load()` / `defaults()`: klasy rozszerzają `Abstract_Schema_Piece` Yoasta i bez Yoasta nie istnieją. Wpisy niebędące `Piece` są odrzucane.

### 3.4 Wymóg Organization

`parentOrganization` (Location) i `provider` (Service) to referencje do Organization Yoasta, które `Piece::organization()` zwraca tylko przy `company_or_person = company`. Yoast tworzy ten węzeł dopiero z `company_name` i logo (`company_logo` + `company_logo_id`, załącznik). Bez nich węzły motywu po prostu nie mają referencji. Seed tego nie ustawia (logo [ZL]).

### 3.5 `trener_id()` (#102)

`PW\Schema\trener_id()` = `home_url( '/#/schema/trener' )` to jedyne `@id` Trenera. Autor Aktualności w schemie (#102) ma wskazywać to samo `@id`, nie budować nowego.

## 4. Panel edytora

- `theme/src/editor/page-panel/PagePanel.js`: `PluginDocumentSettingPanel` `pw-strona`, tytuł „Lokalizacje i schema”, tylko dla `page`. Sekcje: `export const SECTIONS = [ LokalizacjeSection, SchemaSection ]`, renderowane w tej kolejności.
- **Nowa sekcja:** komponent dopisany do `SECTIONS`. Czyta i zapisuje swoje meta przez `useEntityProp( 'postType', 'page', 'meta' )`, przy zapisie **zawsze** `setMeta( { ...meta, klucz: wartość } )`. Meta musi być zarejestrowane z `show_in_rest`. Logikę wydziel do czystego pliku z testem Vitest (wzór: `toggle-lokalizacja.js`, `normalize-cities.js`). Lista typów w `SchemaSection.js` odzwierciedla `PW\Schema\TYPY` – zmieniaj oba.
- **Selektory e2e:** `getByRole( 'button', { name: 'Lokalizacje i schema' } )` (rozwinięcie panelu), `getByRole( 'checkbox', { name: '<tytuł Lokalizacji>' } )`, `getByRole( 'combobox', { name: 'Typ strony w schemie' } )`, `getByRole( 'combobox', { name: 'Obszar usługi (miasta)' } )` (tylko dla `service`).

## 5. Seed `wp pw seed`

### 5.1 Części, klucze, strażnik

- `PW\Cli\Seed_Command` (`theme/inc/cli/class-seed-command.php`): `run( $args, $assoc )` zwraca `[część => liczba]` bez zależności od WP-CLI (tak wołają testy); `__invoke()` mapuje wyjątek na `WP_CLI::error` (kod 1).
- `PARTS` = `kontakt, grupy, lokalizacje, grafik, karnety, menu, yoast`, zawsze w tej kolejności; `--only=a,b` wybiera podzbiór, nieznana część → `InvalidArgumentException` przed jakimkolwiek zapisem.
- **Klucze wyszukiwania** (re-run z `--force` aktualizuje, nie duplikuje): Lokalizacje `chorzow`, `katowice` (`post_name` = kotwica); Grupy `dzieci-6-9-lat`, `dzieci-10-13-lat`, `mlodziez-14`; Karnety `karnet-1..3` (111 / 222 / 999 zł, okresy 1 / 1 polecany / 5, wartości robocze [ZL]); menu `wp_navigation` `menu-glowne`, `stopka-oferta`, `stopka-na-skroty`. Dane strony: opcja nadpisywana w całości (telefon `+48 000 000 000`, `kontakt@example.com`, `trenerImie` „Przemek”, reszta `[...]` albo pusta); Grafik zastępowany w całości; Grupy aktualizowane, nigdy usuwane (strażnik §2.3 nie strzela). Geo puste.
- **Strażnik** `is_empty_database()`: dowolna Lokalizacja lub Karnet (status inny niż kosz) albo opublikowana strona poza `sample-page` / `przykladowa-strona` → `RuntimeException` z podpowiedzią `--force`. Działa także z `--only`.

### 5.2 Co dokłada #93 (strony)

- Część `strony` w `PARTS` **po** `lokalizacje` i **przed** `menu`, strony wyszukiwane po slugu (`upsert_post( 'page', … )`). Strażnik już liczy opublikowane strony – zostaw go. `SeedTest::test_unknown_part_is_rejected_before_anything_is_seeded` używa dziś `strony` jako nieznanej części – zmień przykład.
- Menu: w `seed_menu()` / `nav_link()` zamiast `url: "#"`, `kind: "custom"` linki do stron (`kind: "post-type"`, `type: "page"`, `id`, `url`). Grupy (`core/navigation-submenu`) zostają bez `url`.
- `pw_schema_typ` na stronę: Strony oferty `oferta`, Kontakt `kontakt`, Trener `trener`, 1:1 i B2B `service` (`pw_area_served` opcjonalnie), Grafik i reszta `brak` (Grafik **bez** `pw_lokalizacja`).
- `pw_lokalizacja` przez `meta_input` dopiero **po** opublikowaniu Lokalizacji (sanitizer odrzuca nieznane ID); strony 1:1 na obu salach → oba ID.
- `post_excerpt` strony „Aktualności”.
- Lokalizacja `pw_strona_oferty` → ID odpowiedniej Strony oferty (daje `stronaOfertyUrl`: `url` w schemie i link z Grafiku).

### 5.3 Gdzie biegnie

`npm run seed` (dev, wp-env), ostatni krok `tests/e2e/blueprint.json` (`wp pw seed --force`), `npm run test:e2e:wp-env` (seeduje instancję testów). `test:php:mariadb` nie seeduje. Stage (#81): entrypoint woła `wp pw seed` **bez** `--force`; kod 1 = baza niepusta → pomiń seed, inny kod niezerowy = błąd startu.

## 6. Testy

Szczegóły i przykłady: `tests/README.md` (sekcje „PHP integracja” i „Schema w grafie Yoasta”). Skrót tego, co dotyczy danych:

- **`with_data( $klucz, $dane )`** w `PW\Tests\BlockTestCase` podmienia `pw/data/<klucz>` na czas testu: `kontakt`, `lokalizacje` (dawaj elementom `id` – `pw_lokalizacja()` szuka po nim), `grafik`, `grupy`, `karnety`, `strona_lokalizacje`. Dla `kontakt` brakujące klucze stają się `''`.
- **`PW\Tests\RegistersThemeMeta`**: `tear_down()` wyrejestrowuje wszystkie meta, a `init` nie biegnie ponownie – test zapisujący meta motywu woła w `set_up()` `$this->register_theme_meta()`. Nowe meta dopisz do traitu.
- **`SchemaTestCase`** (`@group yoast`): `graph( $url )`, `nodes()`, `types()`, fikstury `lokalizacja()`, `page( $title, $meta )`, `set_front_page()`, `configure_organization()`. `graph()` czyści `Meta_Tags_Context_Memoizer` po `go_to()`; własny odczyt grafu bez tego dostaje graf poprzedniego testu.
- **Zapis metaboxu:** wypełnij `$_POST` (przez `wp_slash`, z nonce `wp_create_nonce( '<akcja>' )`) jako użytkownik z rolą i wywołaj `wp_update_post( [ 'ID' => $id ] )`; wzory `save_metabox()` w `LokalizacjaTest`, `KarnetTest`, `LocationPieceTest`. Render metaboxu wymaga `require_once ABSPATH . 'wp-admin/includes/template.php'`.
- **Settings errors** nie są czyszczone między testami: `$GLOBALS['wp_settings_errors'] = array();` w `set_up()` (wzór `KontaktTest`, `SeedTest`).
- **`do_action( 'admin_init' )`** odpala kod admina Yoasta i CookieYes; wołaj konkretny callback (np. `pw_kontakt_page_fields()`).
- **E2E na danych z seedu:** Playground ma stan z §5.1 (Chorzów, Katowice z Grafikiem, 3 Karnety, menu). REST Lokalizacji i Karnetów wymaga zalogowania (`requestUtils` jest adminem, `context: 'edit'`). Test zmieniający dane sprząta po sobie; nowe Lokalizacje: `requestUtils.rest( { method: 'POST', path: '/wp/v2/lokalizacja', data: { title, status: 'publish', menu_order } } )`.

## 7. Pułapki

- `pw_grafik()` nie sprawdza statusu Lokalizacji – iteruj po `pw_lokalizacje()` / `pw_strona_lokalizacje()`.
- `pw_lokalizacje( [ 'ids' => … ] )` nie zachowuje kolejności `ids`; kolejność wyboru daje tylko `pw_strona_lokalizacje()`.
- `geo` 0,0 to brak, nie punkt; współrzędne bez wartości domyślnej.
- Cena z `pw_cena_format()` zawiera NBSP – asercje w testach i snapshoty muszą go uwzględniać (`"\u{00A0}"`).
- Kotwica z seedu i z panelu może dostać sufiks (`-2`) – link do Grafiku buduj z `kotwica` z repozytorium, nie ze sluga.
- Kolejność zapisu w kodzie: Grupy → Grafik, Lokalizacje → `pw_lokalizacja` stron; inaczej sanitizer po cichu zapisze `[]`.
- Bez Organization Yoasta (domyślnie na Playground i po seedzie) testy oczekujące `parentOrganization` / `provider` muszą wołać `configure_organization()`.
- Synced pattern „Trener” z ADR 0013 (tworzony przez seed i migrację) jeszcze nie istnieje.
