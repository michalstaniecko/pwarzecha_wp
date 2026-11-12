# Decyzje dla design systemu i layoutów stron

Rozstrzygnięcia pytań z #29, przyjęte w całości zgodnie z rekomendacjami. Ten dokument jest punktem wyjścia do makiet w Claude Design (prompty: `docs/design/prompty-claude-design.md`) i ma pierwszeństwo przed opisami w ticketach stron #15–#22 i #31, dopóki tickety nie zostaną zaktualizowane.

Hierarchia źródeł: ADR > ten dokument > ustalenia w issues (#5, #7, #8, #11) > DS i prompty. DS jest źródłem prawdy dla wyglądu (ADR 0002, 0003), a źródło danych, render i struktura szablonów wynikają z ADR i issues.

Punkty oznaczone **[ZL]** to robocze założenia: makiety je stosują, ale wymagają potwierdzenia Zleceniodawcy lub Trenera. Lista pytań do Zleceniodawcy jest na końcu.

---

## 1. Reguły ogólne

- **Okruszki** są w szablonie FSE (Yoast), jako jasny pasek `neutral-100` nad Hero, na każdej stronie poza główną. Nie są częścią Hero i nie da się ich usunąć. Jeden wariant komponentu (bez wariantu „na granacie”).
- **Punkt kontaktu**: CtaBar, ContactBlock, CTA w Hero albo kafelek „Jak się zapisać?”. Między punktami kontaktu najwyżej 5–6 Sekcji. Strona kończy się punktem kontaktu (CtaBar albo ContactBlock). Bezpośrednio pod ContactBlockiem nie stawiamy CtaBar. Wyjątki: Kontakt (cała strona jest kontaktem), Polityka prywatności (bez CtaBar), pojedyncza Aktualność (szablon z własną kolejnością).
- **Kanały zapisu**: telefon i e-mail zawsze razem, jako równorzędne, duże, klikalne elementy (`tel:` i `mailto:`).
- **CTA na stronach bez `#kontakt`** (Cennik, Grafik, Trener, Aktualności) prowadzi do `/kontakt/#kontakt`, obok telefon i e-mail.
- **Ton**: trzy warianty w DS: do rodzica (domyślny), do dorosłego (1:1 dla dorosłych), do placówki (przedszkola, szkoły). Każdy z przykładowymi tekstami CTA. **[ZL]** akceptuje teksty.
- **Menu**: etykieta „Trener” (zgodnie z `/trener/` i GLOSSARY), na stronie Trenera nadtytuł „O mnie”. **[ZL]** potwierdza, bo prosił o „O mnie”.
- **Nagłówek**: wariant z DS (topbar z telefonem i e-mailem, „Kontakt” jako wyróżniony przycisk na desktopie). Korekta do #11.
- **Lokalizacje**: 2, w kolejności Chorzów, Katowice. **[ZL]**
- **Treści stron trzecich**: tylko przez fasady, patrz ADR 0008.

## 2. Zmiany w design systemie

Do dodania w Claude Design (prompt 1b), potem do `theme.json` i bloków.

| Komponent | Decyzja |
| --- | --- |
| **Tekst ze zdjęciem** | Jeden komponent na bazie `sp-split`: media lewo / prawo / brak, proporcja 4:3 lub 1:1. Treść dowolna (H2, akapity, listy, CheckList ❌/✅) w InnerBlocks. Na telefonie najpierw tekst, potem zdjęcie. Wariant „bez zdjęcia” zastępuje Sekcję tekstową (Cennik, dane firmy, tekst SEO). |
| **Trener** | Wariant Tekstu ze zdjęciem: zdjęcie 1:1 + `sp-link` „Więcej o trenerze” → `/trener/`. Synced pattern, H2 jako `core/heading` z Pattern Override. |
| **Galeria** | `core/gallery` ze stylem motywu: kadr 4:3, 3 kolumny desktop, 2 kolumny telefon (wyjątek od `sp-grid`). Bez lightboxa (wyłączony w `theme.json`). |
| **Teaser ceny** | Blok dynamiczny: H2 + „od X zł / mies.” w stylu `sp-price` + `sp-link` do `/cennik/`. X liczone z Karnetów w przeliczeniu na miesiąc. Bez Karnetów blok się nie renderuje. |
| **Hero** | Jeden blok z atrybutami: `media` (zdjęcie-tło / brak), `rozmiar` (standard / duży; duży ≈ 620 px na stronie głównej). Wariant bez zdjęcia: H1 + lead (+ opcjonalne CTA) na jasnym tle. Warianty „zdjęcie obok” i „lista atutów” wykreślone (sprzeczne z ADR 0002). |
| **Linijka w Hero** | „Pierwszy trening jest zawsze bezpłatny” z ikoną `gift`: przełącznik „pokaż” (domyślnie włączony), edytowalny tekst i ikona. Na stronach dla placówek: „Pierwsze zajęcia pokazowe są bezpłatne” **[ZL]**. |
| **CtaBar** | H2 + zdanie + dwa równorzędne przyciski: „Zadzwoń” (`tel:`) i „Napisz e-mail” (`mailto:`). Przycisk „Umów…” → `#kontakt` opcjonalny. Na telefonie przyciski jeden pod drugim, długi adres e-mail się zawija. |
| **IconTile** | 1–2 akcje w `sp-icon-tile__action`, jedna pod drugą (np. telefon i e-mail w „Jak się zapisać?”). |
| **`sp-grid--4`** | 4 kolumny od ok. 1100 px, 2×2 na tablecie, 1 kolumna na telefonie. |
| **ContactBlock** | Bez wariantu z filmem: Kontakt i Film to dwie osobne Sekcje. `id="kontakt"` tylko przy pierwszym ContactBlocku na stronie. Godziny odbierania telefonu (`sp-contact__hint`) z danych globalnych, puste ukryte. |
| **Film** | Jedna VideoFacade na Sekcję. Układ 2 filmów odłożony. |
| **Lokalizacja (MapFacade)** | Blok w trybie „jedna” / „wszystkie” (lista: `sp-grid--2`, telefon 1 kolumna). Karta: nazwa, adres, opis dojazdu, podgląd, link „Wyznacz trasę”, link „Grafik tej Lokalizacji” → `/grafik/#<slug>`. Bez wiersza godzin. Opcja bloku „Pokaż mapę” (iframe dopiero po kliknięciu, z notą). Film Lokalizacji tylko w trybie „jedna”. Podgląd: ilustracja lub zdjęcie wejścia, nie zrzut z Google Maps. |
| **OfferCard** | Adres opcjonalny; zamiast niego linijka obszaru z tą samą ikoną `map-pin` (np. „Chorzów, Katowice i okolice”). Opcjonalny nadtytuł z grupą oferty. |
| **Schedule (Grafik)** | Blok dynamiczny PHP z danych Lokalizacji. Kotwica per Lokalizacja (`#chorzow`, `#katowice`). W wariancie pełnym pod tabelą Lokalizacji `sp-link` „Więcej o zajęciach w {mieście} →” do Strony oferty z pola Lokalizacji. Lokalizacja bez wierszy się nie pokazuje. Brak stylu `is-style-schedule` dla `core/table`. |
| **FAQ** | Własny blok: pytanie jako H3 w `<summary>`, odpowiedź w InnerBlocks, atrybut „pierwsze pytanie otwarte”. Jeden węzeł `FAQPage` na stronę w grafie Yoasta (z wyłącznikiem), bez obietnicy rich results. |
| **`sp-prose`** | Dodać: cytat (blockquote), zdjęcie z podpisem (figcaption), tabelę w treści (także tabela cookies z CookieYes), link „Zmień ustawienia cookies” (integracja CookieYes). Film w treści tylko jako VideoFacade, `core/embed` wyłączony. Preview w DS. |
| **Nagłówek wpisu** | Bez nowego komponentu: szablon `single.html` z `core/post-title` (H1), `core/post-date` (ikona `calendar`), `core/post-featured-image` (Photo `sp-photo--wide`). Pod H1 tylko data. |
| **Paginacja, pusta lista** | Style `core/query-pagination` i `query-no-results` w `theme.json` na istniejących tokenach. |
| **Social** | Jeden blok Social (dane z `pw/kontakt`) w stopce i na Kontakcie. Logotypy (Facebook, Instagram, YouTube, TikTok) z Simple Icons, tym samym helperem co Lucide. |
| **Ikony** | Dowolna ikona Lucide, dopisana do mapy znaczeń (jedna ikona = jedno znaczenie): `award` uprawnienia i certyfikaty, `medal` staż / lata doświadczenia, `wallet` cena / Karnet, `ticket` Multisport (bez logo marki), `heart-pulse` pierwsza pomoc, `backpack` co zabrać, `file-text` dokumenty, `receipt` umowa / faktura, `user-check` dopasowanie do uczestnika. `calendar` tylko dla daty. |
| **Szablony 404 i wyszukiwania** | W zakresie, bez osobnego projektu: Hero bez zdjęcia + krótki tekst + Karty ofert / linki do ofert. |

## 3. Układy stron

Kolumna „Tło” to propozycja rytmu (`base` / `light` / `navy`); edytor może ją zmienić.

### Szablon oferty (#31, 6 Stron oferty)

| # | Sekcja | Uwagi |
| --- | --- | --- |
| 1 | Hero (zdjęcie-tło) | Linijka w Hero edytowalna; na PRZ i SZK tekst o zajęciach pokazowych **[ZL]** |
| 2 | Kontakt `#kontakt` | ContactBlock z godzinami telefonu |
| 3 | Film | jedna VideoFacade |
| 4 | Tekst ze zdjęciem (intro) | zdjęcie prawo |
| 5 | Tekst ze zdjęciem ×N (program) | naprzemiennie lewo/prawo, każda Sekcja z H2 (PRZ: osobne H2 dla 3–4 i 5–6 lat) |
| 6 | CtaBar #1 | |
| 7 | Tekst ze zdjęciem (wartości, CheckList ❌/✅) | zdjęcie opcjonalne |
| 8 | Teaser ceny | tylko CHO, KAT |
| 9 | Jak wyglądają zajęcia | A: Tekst ze zdjęciem (CHO, KAT); B: IconTile ×4 w `sp-grid--4` (1:1, B2B) |
| 10 | Galeria | |
| 11 | Kafelki zalet (PhotoTile ×6) | |
| 12 | CtaBar #2 | |
| 13 | Trener | |
| 14 | Najważniejsze informacje (IconTile ×6–9) | „Jak się zapisać?” z dwiema akcjami; SZK: „Bezpieczeństwo i formalności” z dokumentami Trenera **[ZL]** |
| 15 | FAQ | |
| 16 | CtaBar #3 | opcjonalny, CHO, KAT |
| 17 | Opinie | per miasto przy ≥ 6 Opiniach na miasto, inaczej wspólne; PRZ, SZK: referencje placówek |
| 18 | Grafik (jedna Lokalizacja) | tylko CHO, KAT; na 1:1 pominięty |
| 19 | Lokalizacja | CHO, KAT: tryb „jedna”; 1:1: tryb „wszystkie” (obie sale) **[ZL]**; bez PRZ i SZK |
| 20 | Zobacz też (OfferCard ×3) | opcjonalna, włączona |
| 21 | CtaBar końcowy | |

Strona dla szkół zmienia slug na `/zajecia-z-samoobrony-w-szkolach/` **[ZL]**. Strony 1:1 i B2B: OfferCard i schema `Service` z `areaServed` = „Chorzów, Katowice i okolice” do czasu listy miast **[ZL]**.

### Strona główna (#17)

| # | Sekcja | Tło | Uwagi |
| --- | --- | --- | --- |
| 1 | Hero (zdjęcie-tło, rozmiar „duży”) | – | bez okruszków (brak w `front-page.html`) |
| 2 | Karty ofert | base | 6 OfferCard w kolejności menu, nadtytuł karty = grupa („Treningi grupowe”, „Treningi indywidualne”, „Dla placówek”); Badge „Pierwszy trening gratis” na 2 kartach grupowych; karty 1:1 i B2B z linijką obszaru |
| 3 | Tekst ze zdjęciem (ok. 300 słów, frazy ogólne) | light | nowa Sekcja |
| 4 | Trener | base | |
| 5 | Jak zacząć? (IconTile ×4, `sp-grid--4`) | navy | „Jak się zapisać?” z telefonem i e-mailem |
| 6 | CtaBar | – | |
| 7 | Opinie | light | |
| 8 | Grafik (pełny) | base | |
| 9 | Lokalizacje (tryb „wszystkie”) | light | |
| 10 | CtaBar | – | |
| 11 | FAQ | base | pytania rodziców z uwag klienta |
| 12 | Aktualności (3 NewsCard) | light | |
| 13 | Kontakt `#kontakt` | base | koniec strony, bez CtaBar pod spodem |

### Trener (#18)

Hero (zdjęcie) → Tekst ze zdjęciem (bio, zdjęcie 1:1) → Kafelki z ikoną (uprawnienia, staż, pierwsza pomoc…; opcjonalna) → Tekst ze zdjęciem (jak pracuję z dziećmi, CheckList ❌/✅) → Film (opcjonalny) → CtaBar → Galeria → Opinie → Gdzie trenujemy (OfferCard ×6, karty bez sali z linijką obszaru) → FAQ (opcjonalne) → CtaBar.

Nadtytuł Hero: „O mnie”. Schema `Person` (mainEntity), to samo `@id` co autor Aktualności; nazwisko tylko za zgodą Trenera **[ZL]**. Sekcje opcjonalne wypadają, jeśli Trener nie dostarczy treści.

### Cennik (#15)

Hero (bez zdjęcia) → Karnety → Tekst ze zdjęciem w wariancie bez zdjęcia (zasady i dopłaty) → FAQ (płatności) → CtaBar.

### Grafik (#16)

Hero (bez zdjęcia) → Grafik (pełny, 2 Lokalizacje, kotwice, link do Strony oferty pod każdą tabelą) → CtaBar. Sekcja „Zanim przyjdziesz” usunięta. Schema: tylko `WebPage`.

### Kontakt (#19)

Hero (bez zdjęcia, bez przycisków) → Kontakt `#kontakt` (z godzinami telefonu) → Lokalizacje (tryb „wszystkie”, opcja „Pokaż mapę” włączona) → Tekst w wariancie bez zdjęcia + Social (nazwa firmy, NIP; adres rejestrowy tylko jeśli nie jest prywatnym mieszkaniem **[ZL]**). Bez FAQ i bez CtaBar.

### Aktualności: lista (#20)

Hero (bez zdjęcia; lead z zajawki strony „Aktualności”, wymaga `excerpt` dla stron) → lista NewsCard, 9 na stronę (3×3), paginacja, pusta lista → CtaBar. Bez kategorii, tagów i archiwów (wyłączone w Yoaście).

### Aktualności: pojedynczy wpis (#21)

Nagłówek wpisu (H1, data, zdjęcie wyróżniające) → treść (`sp-prose`) → CtaBar → Inne Aktualności (3 NewsCard bez bieżącego, `sp-link` „Wszystkie Aktualności →”; ukryte przy < 2 innych wpisach).

Dozwolone bloki w treści: akapit, H2, H3, lista, cytat, Photo, CheckList, VideoFacade, Galeria. Bez Grafiku i bez Sekcji. Wpis bez zdjęcia wyróżniającego dostaje domyślne zdjęcie z motywu, a edytor widzi ostrzeżenie przed publikacją (bez blokady). Autor: Trener (osobne konto WP).

### Polityka prywatności (#22)

Hero (bez zdjęcia, data aktualizacji) → treść (`sp-prose`, tabela cookies, link „Zmień ustawienia cookies”). Bez CtaBar. Treść obejmuje wizerunek dzieci; dane zdrowotne nie (osobna klauzula przy zapisie) **[ZL]**.

### 404 i wyniki wyszukiwania

Hero (bez zdjęcia) → krótki tekst → Karty ofert / linki do ofert. `search.html` minimalny (wyszukiwarki nie ma w menu).

## 4. Zmiany w modelu danych (do #8)

- „Dane strony” (`pw/kontakt`): nowe pole **godziny odbierania telefonu**.
- **Grupy**: stała lista jako niepubliczna taksonomia; w wierszu Grafiku wybór z listy (zapis po ID). Do zrobienia przed wpisywaniem danych na stage'u.
- **Lokalizacja**: nowe pole „Strona oferty” (link z Grafiku). Bez pola godzin.
- **`pw_lokalizacja`**: wiele wartości albo tryb „wszystkie” w bloku Lokalizacja (strony 1:1 na obu salach).
- **Karnet**: okres płatności, żeby Teaser ceny liczył cenę miesięczną.
- **Strony**: włączony `excerpt` (lead listy Aktualności).

## 5. Schema i SEO

- `FAQPage`: jeden węzeł na stronę, z wyłącznikiem.
- `Person` dla Trenera; nazwisko za zgodą **[ZL]**.
- `Service` z `areaServed` (bez `Offer` i ceny) na stronach 1:1 i B2B.
- Grafik: tylko `WebPage`. `SportsActivityLocation` emitują strona główna, Kontakt i Strony oferty.

## 6. Do poprawy w innych dokumentach i issues

- #7: usunąć „Hero ze zdjęciem obok”, „listę atutów”, „Kontakt (z filmem)”.
- #8: zmiany modelu z sekcji 4; mapa jako hybryda z ADR 0008.
- #11: nagłówek w wariancie DS; slug strony dla szkół.
- #5: status `FAQPage`; fasady według ADR 0008.
- Tickety stron #15–#22, #31: układy z sekcji 3.

## 7. Pytania do Zleceniodawcy i Trenera

Kolejność według tego, ile stron blokuje odpowiedź.

1. **Kontakt i firma**: telefon, e-mail, Messenger, godziny odbierania telefonu; Facebook, Instagram, YouTube, czy jest konto TikTok; nazwa firmy, NIP, adres rejestrowy (czy może być publiczny); wizytówki Google dla sal.
2. **Zdjęcia**: z sali w proporcjach 16:9, 4:3, 1:1; potwierdzenie zgód rodziców (i placówek); zdjęcie domyślne dla Aktualności.
3. **Trener**: bio (początki, dyscypliny, stopnie, uprawnienia w tym pierwsza pomoc, staż, lata pracy z dziećmi, placówki), portret 1:1, zgoda na nazwisko, dokumenty (KRK, RSPTS, OC), czy są Standardy ochrony małoletnich.
4. **Lokalizacje**: potwierdzenie 2 sal; nazwa obiektu, adres, link do Map, dojazd i parking; gdzie odbywają się treningi 1:1 (jedna sala, obie, dojazd do klienta).
5. **Opinie**: zrzuty z treścią do `alt`, liczba dla Chorzowa i Katowic, referencje placówek i zgody na publikację.
6. **FAQ i organizacja zajęć**: odpowiedzi na pytania z uwag; czas trwania, częstotliwość, liczebność Grupy, minimalny wiek, obuwie, NNW, czy rodzic może zostać na sali.
7. **Filmy**: ID filmów YouTube.
8. **Grafik i Grupy**: dni, godziny, stała lista nazw Grup i przedziały wieku per miasto, wolne miejsca, czy program różni się między Grupami.
9. **Ceny**: Karnety (nazwy, ceny, okresy, co obejmuje, polecany), czy wszystkie są miesięczne; opłata startowa, Multisport, rodzeństwo, odrabianie, przerwy wakacyjne, odwoływanie 1:1; cena 1:1 („od X zł / trening”).
10. **Treningi 1:1**: czy ścieżki dla dorosłych, samoobrona dla kobiet i trening w parze to realna oferta; wiek dzieci; praca z dziećmi z ADHD / w spektrum; akceptacja wątku przemocy rówieśniczej.
11. **Oferta dla placówek**: formy współpracy, typy placówek, moduły programu, model rozliczenia, czas zajęć i liczebność, lista miast dojazdu, czy zajęcia pokazowe są bezpłatne, akceptacja slugu `/zajecia-z-samoobrony-w-szkolach/`.
12. **Polityka prywatności**: kto przygotuje treść i kiedy, potwierdzenie rozdziału o wizerunku dzieci, czy przy zapisie zbierane są dane zdrowotne.
13. **Aktualności**: kto fizycznie publikuje wpisy.
14. **Etykieta menu** „Trener” zamiast „O mnie” i **mapa** jako link + opcjonalny podgląd zamiast stale osadzonej mapy.
