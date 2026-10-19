# Claude Design: handoff design systemu i makiet do kodu

Badanie do issue #4. Stan na 2026-10-19. Źródła: wyłącznie materiały Anthropic (anthropic.com, claude.com, support.claude.com, code.claude.com, academy.claude.com).

## TL;DR

- Claude Design to dziś **szablon artefaktu w Claude** (czat, zakładka Artifacts, Claude Code przez `/design`). Samodzielna wersja `claude.ai/design` zostaje **zamknięta 14 grudnia 2026**, więc nie warto w niej zaczynać pracy. [S1][S5]
- **Eksport:** `.zip` (archiwum projektu, w tym HTML), PDF, PPTX, Google Slides, samodzielny HTML, integracje (Canva, Vercel, Lovable itd.) oraz **handoff do Claude Code** (lokalnie albo Claude Code on the web). Artboardy z `/design` można też eksportować jako PNG/PDF. [S1][S6]
- **Design system** (kolory, typografia, komponenty, wzorce layoutu: odstępy i siatka) da się zdefiniować i współdzielić między projektami. **Nie ma udokumentowanego eksportu tokenów** (np. JSON/CSS variables), więc nie ma gotowego mostu do `theme.json`. [S2][S4]
- **Rekomendacja dla nas:** źródłem prawdy dla tokenów powinien być `theme.json` w repo (paleta navy/orange/white/black, typografia, skala odstępów), a Claude Design ma go **czytać** jako kontekst (link do repo / upload pliku). Makiety przekazujemy do Claude Code przez handoff bundle (HTML + README + chat), a Claude Code tłumaczy HTML/CSS na bloki PHP i odwołania do `var(--wp--preset--...)`. Wartości tokenów nigdy nie są przepisywane ręcznie z makiety.
- **Koszt:** dostępne na wszystkich planach, łącznie z Free. Nie ma osobnej puli: zużycie wlicza się do wspólnych limitów Claude (razem z Claude Code). Duże repo i dużo iteracji zużywają więcej. [S1][S4]

## 1. Co potrafi Claude Design

- Tworzy projekty, interaktywne prototypy, wireframe'y, flow, landing page'e, one-pagery i decki rozmową z Claude. Działa na Claude Opus 4.7. [S3]
- Układ to czat i canvas. Poprawki można robić czatem (zmiany strukturalne), komentarzami inline (na poziomie komponentu) albo bezpośrednio na canvasie (przeciąganie, zmiana rozmiaru, wyrównanie, suwaki odstępów i kolorów). [S1][S3][S4]
- Można poprosić o warianty, przegląd dostępności i kontrastu oraz o zmiany w całym projekcie naraz. [S1][S3]
- Gdzie się uruchamia: czat (Output > Design), zakładka Artifacts, Claude Code (`/design <brief>` w CLI lub desktopie, research preview od v2.1.234, a wg aktualnej dokumentacji artefaktów wymagane v2.1.265+). [S1][S6][S7]

## 2. Formaty eksportu i handoff do kodu

| Kanał | Co dostajemy | Źródło |
|---|---|---|
| `.zip` / „Project HTML” → „Project archive” | Wszystkie pliki projektu (HTML/CSS/JS, uploady). Pobranie nie wlicza się do limitów | [S1][S5] |
| Standalone HTML | Pojedynczy plik HTML | [S1][S3] |
| PDF, PPTX, Google Slides | Do prezentacji i akceptacji klienta | [S1] |
| Integracje | Adobe, Base44, Canva, Gamma, HubSpot, Hyperframes, Lovable, Miro, Netlify, Replit, v0, Vercel, Wix | [S1] |
| **Handoff do Claude Code** | Export → „Hand off to Claude Code”: bundle z plikami designu, historią czatu i README z instrukcją dla modelu. Opcja lokalna daje prompt z URL bundle'a („Copy command”), opcja web otwiera Claude Code on the web | [S1][S3][S8] |
| `/design` w Claude Code | Canvas z artboardami jako artefakt. Wybierasz artboard i mówisz Claude, który zaimplementować. Eksport artboardu: PNG/PDF | [S6][S7] |

Wnioski:
- Handoff to **HTML/CSS jako referencja**, a nie gotowy kod WordPressa. Konwersję na bloki (`block.json` + `render.php`) i style z presetów `theme.json` robi Claude Code w repo.
- Nazwy komponentów nadane w prototypie przechodzą do handoffu, a decyzje zapisane w czacie trafiają do bundle'a. Warto więc nazywać sekcje tak, jak będą nazywać się bloki (np. „Hero”, „OfferGrid”). [S8]
- Nie ma udokumentowanego eksportu do Figmy ani eksportu obrazów z poziomu standalone/projektu (poza PNG/PDF artboardu w `/design`). [S1][S7]

## 3. Design system a `theme.json`

Co obejmuje design system: paletę (primary, secondary, accent), typografię (rodziny, rozmiary, grubości), komponenty (przyciski, karty, nawigacja) i wzorce layoutu (odstępy, siatka, struktura stron). Wewnętrznie składa się z „guide, tokens and components” i jest przechowywany jako artefakt, którym zarządza się w Settings > Design systems. [S2][S4]

Źródła wejściowe: repo/codebase (GitHub lub lokalnie), pliki designu (także Figma), screenshoty, decki/PDF, logo, pliki palety, próbki fontów. `/design-sync` w Claude Code czyta tokeny i komponenty bezpośrednio z kodu, ale „najlepiej działa dla design systemów w React”. [S2][S4]

**Eksport:** dokumentacja **nie opisuje** eksportu design systemu ani formatu tokenów. [S2][S4] Wniosek: nie da się (w udokumentowany sposób) wygenerować `theme.json` z Claude Design. Kierunek odwrotny działa: Claude czyta tokeny z kodu/plików.

Wskazówka z dokumentacji Claude Code: wbudowany skill designu szuka istniejącego design systemu w projekcie (np. w `CLAUDE.md` albo w pliku motywu) i stawia go ponad własne wybory. [S7] Dla nas oznacza to krótką sekcję „Design system” w `CLAUDE.md`, która wskazuje na `theme.json`.

Proponowany przepływ:
1. Ręcznie (z Claude Code) definiujemy `theme.json`: `settings.color.palette` (navy, orange, white, black i ewentualnie odcienie), `typography.fontFamilies`/`fontSizes`, `spacing.spacingSizes`.
2. Tworzymy design system w Claude Design z uploadu: `theme.json`, palety z `docs/brief/`, logo i fontów. Alternatywnie z linku do repo (bez `/design-sync`, bo nie mamy Reacta). Publikujemy go jako domyślny („Published”).
3. Makiety projektujemy w Claude Design. Jeśli zmienia się token, zmieniamy go w `theme.json` i aktualizujemy design system (prośba do Claude albo ponowny upload), nigdy odwrotnie.

## 4. Spójność wielu makiet

- Opublikowany design system jest automatycznie używany w nowych projektach. Claude sprawdza wynik względem systemu i poprawia go, zanim zobaczymy rezultat. [S2][S4]
- Przy flow wielu ekranów każdy ekran powstaje w kontekście pozostałych. [S8]
- Dobre praktyki z dokumentacji: zaimportować kompletny system, zaczynać prosto, odwoływać się do komponentów po nazwie i wcześnie myśleć o responsywności. [S1]
- **Brak historii wersji.** Żeby zachować kierunek, trzeba poprosić Claude o zapis projektu albo pobrać `.zip`. [S1]

## 5. Materiały wejściowe

Obsługiwane wejścia to prompt tekstowy, obrazy i screenshoty („make it look like this”), wireframe'y, DOCX, PPTX, XLSX, PDF, pliki Figma, logo, fonty, link do repo i narzędzie „web capture” do pobierania elementów z istniejącej strony. [S1][S2][S3][S4] Paletę z `docs/brief/`, zdjęcia i inspiracje podajemy więc jako upload do design systemu (to, co trwałe) albo do projektu (to, co dotyczy jednej makiety).

## 6. Ograniczenia

- Beta (design systems i szablony). Brak historii wersji. Współedycja jest „podstawowa” i może być zawodna. Komentarze inline czasem się nie wyświetlają. [S1][S2]
- Duże repo powodują lagi. Zalecenie: linkować tylko istotny katalog i wykluczyć `.git` oraz `node_modules`. [S1][S8]
- Jakość importu design systemu zależy od jakości plików źródłowych. [S1][S2]
- Brak udokumentowanego eksportu tokenów i formatu bundle'a (struktura plików nie jest opisana). [S2][S8]
- Artefakty z `/design`: jedna strona, rygorystyczny CSP, obrazy jako data URI, limit 16 MiB, wymagane logowanie kontem claude.ai (nie działa z kluczem API, Bedrock ani Vertex). [S7]
- Projekty ze standalone nie są jeszcze migrowane, a czaty i komentarze ze standalone zostaną usunięte po zamknięciu. [S5]

## 7. Koszty i limity

- Dostępne na Free, Pro, Max, Team i Enterprise. W Enterprise włączone domyślnie od 15.10.2026. [S1][S4]
- Brak osobnego limitu Claude Design: użycie wlicza się do wspólnych limitów planu, razem z Claude Code. Po wyczerpaniu limitu Claude Design jest niedostępny do resetu, chyba że włączone są usage credits. [S1]
- Ceny planów nie są podane na stronach produktu, tylko na stronie cennika (claude.com/pricing). [S4]

## Źródła

- [S1] Get started with Claude Design: https://support.claude.com/en/articles/14604416-get-started-with-claude-design
- [S2] Set up your design system in Claude Design: https://support.claude.com/en/articles/14604397-set-up-your-design-system-in-claude-design
- [S3] Introducing Claude Design by Anthropic Labs (17.04.2026): https://www.anthropic.com/news/claude-design-anthropic-labs
- [S4] Strona produktu Claude Design: https://claude.com/product/design oraz blog „Claude Design now stays on brand for daily work” (17.06.2026, aktualizacja z 09.2026): https://claude.com/blog/claude-design-stays-on-brand-for-daily-work
- [S5] Migrate from standalone Claude Design to Claude: https://support.claude.com/en/articles/17440474
- [S6] Claude Code, What's new, tydzień 34 (17–21.08.2026), `/design`: https://code.claude.com/docs/en/whats-new/2026-w34
- [S7] Claude Code, Share session output as artifacts (sekcje „Improve the visual design”, „Draft a design canvas”, „Page constraints”): https://code.claude.com/docs/en/artifacts
- [S8] Claude Academy, Using Claude Design for prototypes and UX: https://academy.claude.com/tutorials/using-claude-design-for-prototypes-and-ux
