# Kod piszemy metodą TDD, więc narzędzia testowe są częścią fundamentu projektu

Cały kod motywu i wtyczki `pw-dane` pisze agent (Claude Opus), w większości metodą TDD: najpierw test, potem implementacja. Lokalne środowisko testowe musi więc działać, zanim powstanie pierwszy blok. Konfigurację narzędzi testowych robimy w pierwszym milestonie, a nie na końcu projektu. Każde zadanie implementacyjne uznajemy za skończone dopiero wtedy, gdy ma testy i te testy przechodzą lokalnie.

Testy są dla agenta jedynym szybkim i obiektywnym sprawdzianem poprawności. Bez nich musiałby polegać na ręcznym klikaniu w WordPressie, a tego nie zrobi wiarygodnie.

## Warstwy testów

- **PHP (PHPUnit + WordPress test suite):** HTML zwracany przez `render.php` każdego bloku dla danych atrybutów, helpery motywu (np. ikony), wtyczka `pw-dane` (CPT, meta, Block Bindings), schema dokładana do Yoast.
- **JS edytora (Jest z `@wordpress/scripts`):** tylko tam, gdzie blok ma własną logikę w edytorze.
- **E2E (Playwright z `@wordpress/e2e-test-utils-playwright`):** wstawianie, duplikowanie i przesuwanie Sekcji, Szablon oferty przy nowej stronie, render frontu, brak JS na froncie.
- **Statyczna analiza:** PHPCS (WordPress Coding Standards), PHPStan z `szepeviktor/phpstan-wordpress`, ESLint/Stylelint z `@wordpress/scripts`. Stylelint pilnuje też, żeby CSS nie zawierał literalnych wartości zamiast tokenów (ADR 0003).
- **Budżet wydajności:** Lighthouse CI na kluczowych typach stron (mobile ≥ 95).

## Testy uruchamiamy lokalnie, nie w GitHub Actions

Podstawowym i jedynym codziennym miejscem testów jest lokalne środowisko. Przy dużej liczbie commitów i PR-ów darmowy limit minut GitHub Actions kończy się bardzo szybko, więc workflowy nie uruchamiają się na push ani na PR. GitHub Actions służy tylko jako końcowa kontrola przy podbiciu wersji motywu lub przy release (np. tag `v*` albo ręczne `workflow_dispatch`). Przed commitem i przed scaleniem PR testy muszą przejść lokalnie. Całość (statyczna analiza, PHPUnit, Jest, Playwright, Lighthouse) uruchamia jedna komenda opisana w `CLAUDE.md`. Szybkie testy (statyczna analiza, testy jednostkowe) mogą dodatkowo działać jako lokalny git hook.

Wybór środowiska uruchomieniowego testów (np. `wp-env`, który ma wbudowane PHPUnit i bazę testową) rozstrzyga ticket o środowisku dev i pipeline.
