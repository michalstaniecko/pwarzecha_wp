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
| `inc/bootstrap.php` | Ładuje kolejno `inc/helpers`, `data`, `schema`, `editor`, `migrations`, `styleguide`, `blocks.php`, `cli` (tylko pod WP-CLI). |
| `inc/<katalog>/index.php` | Ładuje (`require_once`) pliki katalogu. Nowy plik = nowa linia w `index.php`. Bez autoloadera Composera. |
| `inc/styles.php` | Block styles bloków core, wczytanie `build/styles/index.css` (front i edytor), preload fontu H1. |
| `src/styles/` | Wspólny CSS DS (#41): kontekst kolorów `--sp-*`, `sp-prose`, przyciski, bloki core. Tylko tokeny. |
| `assets/fonts/` | Fonty self-hosted (Archivo, Source Sans 3; zmienne, `latin` + `latin-ext`, licencje OFL). Podpięte w `theme.json` (`fontFace`). |
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
- **Migracje** (`inc/migrations/runner.php`): wykonane ID w opcji `pw_migrations`, wersja w `pw_theme_version`. Uruchamiane na `after_switch_theme` i na `admin_init`, gdy `Version` ze `style.css` różni się od `pw_theme_version`; ręcznie `wp pw migrate [--list]`. Pierwszy błąd zatrzymuje kolejne migracje, wersja nie jest wtedy zapisywana (ponowna próba przy następnym `admin_init`). Migracje atrybutów bloków robimy w `render.php`, nie w bazie (ADR 0012).
- Każdy plik w `inc/` zaczyna się od `defined( 'ABSPATH' ) || exit;`.
