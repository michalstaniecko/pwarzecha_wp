# Motyw `pw-theme` – konwencje

Motyw blokowy (FSE, ADR 0003). Struktura według ADR 0013. W ZIP-ie jako katalog `pw-theme/` (z `build/`, bez `vendor/`).

## Gdzie co leży

| Ścieżka | Zawartość |
| --- | --- |
| `style.css` | Tylko nagłówek motywu. `Version` = `version` z `package.json` (pilnuje `tools/theme/theme.test.mjs`). |
| `functions.php` | Tylko `require` `inc/bootstrap.php`. |
| `theme.json` | Tokeny DS (ADR 0002, 0009). Wygląd wyłącznie stąd. |
| `templates/`, `parts/`, `patterns/` | Szablony, części, wzorce (HTML z bloków). |
| `src/blocks/<slug>/` | Blok: `block.json`, `render.php`, `edit.js`, `style.css`, `*.test.js`. |
| `src/{facade,editor,styles,admin}/index.js` | Osobne wejścia webpacka → `build/<nazwa>/`. Wejście buduje się dopiero, gdy istnieje `index.js`. |
| `build/` | Wynik `npm run build` (poza gitem): `build/blocks/`, `build/blocks-manifest.php`, `build/<wejście>/`. |
| `inc/bootstrap.php` | Ładuje kolejno `inc/helpers`, `data`, `schema`, `editor`, `migrations`, `styleguide`, `blocks.php`, `styles.php`, `cli` (tylko pod WP-CLI). |
| `inc/<katalog>/index.php` | Ładuje (`require_once`) pliki katalogu. Nowy plik = nowa linia w `index.php`. Bez autoloadera Composera. Wyjątek: `inc/styleguide/partials/*.php` (bez plików zaczynających się od `_`) nie są wypisane – ładuje je globem `PW\Styleguide\load_partials()` na `wp_loaded`, tylko gdy styleguide jest włączony (filtr `pw/styleguide/enabled` dodany w `after_setup_theme`/`init` działa). Nowa grupa = nowy plik, bez zmian w `index.php`. |
| `inc/styles.php` | Block styles bloków core, wczytanie `build/styles/index.css` (front i edytor), preload fontu H1. |
| `src/styles/` | Wspólny CSS DS (#41): kontekst kolorów `--sp-*`, `sp-prose`, przyciski, bloki core. Tylko tokeny. |
| `assets/fonts/` | Fonty self-hosted (Archivo, Source Sans 3; zmienne, `latin` + `latin-ext`, licencje OFL). Podpięte w `theme.json` (`fontFace`). |
| `assets/icons/` | Ikony z `manifest.json` (`lucide/`, `brand/`) i licencje pakietów `LICENSE-lucide.txt`, `LICENSE-simple-icons.txt`. Generowane przez `npm run icons` (`tools/icons/sync-icons.mjs`), nie edytujemy ręcznie. |
| `inc/styleguide/` | Strona deweloperska `/?pw_styleguide=1` (#42): `api.php` (włączenie, rejestr grup), `route.php`, `chrome.php` (`.sg-*`), `fixtures.php`, `partials/<grupa>.php`, `style.css`, `assets/` (zdjęcia testowe). Nigdy na produkcji. |
| `inc/migrations/NNNN-nazwa.php` | Migracja: plik zwraca callable, musi być idempotentna. |

## Nazewnictwo i prefiksy

- PHP: przestrzeń nazw `PW\<Moduł>` (np. `PW\Migrations`, `PW\Blocks`) albo funkcje globalne z prefiksem `pw_`. Opcje i meta: `pw_…`.
- Bloki: `pw/<slug>`, katalog `src/blocks/<slug>/`, kategoria `pw` („Samoobrona Przemek”).
- Hooki własne: `pw/…` (np. filtr `pw/data/<nazwa>`).
- Text domain: `pw-theme`.

## Zasady

- **Dane globalne tylko przez repozytoria** w `inc/data/` (ADR 0007). Repozytorium zwraca wynik przepuszczony przez filtr `pw/data/<nazwa>`; bloki, schema i szablony nie czytają opcji ani meta bezpośrednio.
- **Bloki rejestruje manifest**: `inc/blocks.php` woła `wp_register_block_types_from_metadata_collection()` na `init`. Nie rejestrujemy bloków ręcznie. Blok bez logiki edytora: `supports.autoRegister` + `render.php`, bez `edit.js`.
- **CSS tylko z tokenów** (`var(--wp--preset--…)`, `var(--wp--custom--…)`), bez literałów (stylelint, ADR 0003).
- **Breakpointy kontenera** (`.wp-site-blocks` = szerokość strony, `src/styles/context.css`): wąski telefon `@container (max-width: 479px)`, telefon `@container (max-width: 599px)`. Zmienne CSS nie działają w warunkach `@container`, więc to jedyne dopuszczalne literały – pilnuje `tools/theme/breakpoints.test.mjs`; nowa wartość = zmiana w obu miejscach i tutaj.
- **Migracje** (`inc/migrations/runner.php`): wykonane ID w opcji `pw_migrations`, wersja w `pw_theme_version`. Uruchamiane na `after_switch_theme` i na `admin_init`, gdy `Version` ze `style.css` różni się od `pw_theme_version`; ręcznie `wp pw migrate [--list]`. Pierwszy błąd zatrzymuje kolejne migracje, wersja nie jest wtedy zapisywana (ponowna próba przy następnym `admin_init`). Migracje atrybutów bloków robimy w `render.php`, nie w bazie (ADR 0012).
- Każdy plik w `inc/` zaczyna się od `defined( 'ABSPATH' ) || exit;`.

## Licencje zasobów

Motyw: GPL-2.0-or-later (`style.css`). Dołączone zasoby zewnętrzne:

| Zasób | Licencja | Plik licencji | Źródło |
| --- | --- | --- | --- |
| Archivo (font) | SIL OFL 1.1 | `assets/fonts/OFL-Archivo.txt` | https://github.com/Omnibus-Type/Archivo |
| Source Sans 3 (font) | SIL OFL 1.1 | `assets/fonts/OFL-SourceSans3.txt` | https://github.com/adobe-fonts/source-sans |
| Lucide (ikony, `lucide-static`) | ISC (część ikon pochodzi z Feather, MIT) | `assets/icons/LICENSE-lucide.txt` | https://lucide.dev |
| Simple Icons (ikony marek, `simple-icons`) | CC0 1.0 | `assets/icons/LICENSE-simple-icons.txt` | https://simpleicons.org |

Ikony marek (Facebook, Instagram, YouTube, TikTok, Google) są znakami towarowymi ich właścicieli; użycie zgodnie z wytycznymi marek, bez sugerowania powiązania z nimi (https://github.com/simple-icons/simple-icons/blob/develop/DISCLAIMER.md).

Pliki licencji muszą trafić do ZIP-a motywu razem z fontami i ikonami (w repo nie ma jeszcze konfiguracji pakowania – kto ją doda, nie wyklucza `assets/**/*.txt`). Licencje ikon odświeża `npm run icons` przy aktualizacji pakietów, a `npm run icons -- --check` (część `npm run check`) kończy się błędem, gdy ich brak albo różnią się od pakietów.
