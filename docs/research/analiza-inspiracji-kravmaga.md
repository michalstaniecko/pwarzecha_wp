# Analiza inspiracji: Strona oferty i hub kravmagaunity.pl

Ticket: #2. Stan stron z 2026-10-19 (pobrany HTML, WordPress 7.1.3 + Elementor 3.25 / Elementor Pro, Yoast SEO 28.7, Schema & Structured Data for WP 1.47, Polylang).

Źródła (pierwotne):

- [Z] Strona oferty: https://kravmagaunity.pl/samoobrona-krav-maga-dla-dzieci-i-mlodziezy-zabrze/
- [H] Hub: https://kravmagaunity.pl/samoobrona-krav-maga-dla-dzieci-i-mlodziezy/
- [S1] Zrzut z briefu, kafelki zalet: http://icecream.me/668752a9ceed83862bffbd8b9d1f7cb6 (obraz `/uploads/668752a9ceed83862bffbd8b9d1f7cb6.png`, dostępny)
- [S2] Zrzut z briefu, „najważniejsze informacje”: http://icecream.me/4811c5fc59acbc14ccfc7774762c164c (obraz `/uploads/4811c5fc59acbc14ccfc7774762c164c.png`, dostępny)

Liczby słów policzono od H1 do paska z hashtagami, czyli bez nagłówka strony, menu i stopki. Treść odpowiedzi FAQ wlicza się do sumy, bo jest w HTML (zwinięta). Wartości są przybliżone.

## TL;DR

- **Strona oferty [Z]** jest długim, jednokolumnowym landingiem: **około 21 Sekcji**, **około 2040 słów**, **około 15 zdjęć treściowych, 8 zrzutów Opinii, 3 filmy YouTube i około 14 ikon**. Ma jedno H1. **Wszystkie pozostałe nagłówki to H2 (60 sztuk), a H3 nie ma wcale.** Elementor używa H2 również do akapitów i leadów.
- **Hub [H]** to H1, przycisk, **6 kafelków-kart** (4 miasta, BJJ i treningi indywidualne; każda karta ma zdjęcie, adres, krótki opis i przycisk „Sprawdź zajęcia”) oraz FAQ z 10 pytaniami. Razem **około 930 słów**, z czego około 70% przypada na FAQ.
- **Schema** pochodzi z wtyczki SASWP, a Yoast ma wyłączony graf. Obie strony mają `sportsclub` (z małej litery, więc **niepoprawny typ** schema.org) z wieloma pustymi polami. [Z] ma dodatkowo `ItemList` z 3 `VideoObject`. **Nie ma `FAQPage`, `BreadcrumbList`, `Review` ani `AggregateRating`**, mimo że strona zawiera FAQ i Opinie.
- **CTA:** brak formularza. Wszystkie CTA prowadzą do kotwicy `#kontakt` (Sekcja z telefonem i e-mailem) lub do `/gdzie-chcesz-trenowac/`. Telefon i e-mail wracają kilka razy.

## 1. Strona oferty [Z]: inwentarz Sekcji w kolejności

Meta: title „Samoobrona Krav Maga dla dzieci i młodzieży Zabrze | Sprawdź ofertę!”, meta description około 20 słów, canonical ustawiony.

| # | Sekcja (nagłówek) | Typ treści | Ilość treści |
|---|---|---|---|
| 1 | Hero: **H1** „Samoobrona Krav Maga dla Dzieci 6-15 lat Zabrze \| Zapisz swoje dziecko…” + H2-lead (pytanie do rodzica) | tekst + przycisk CTA (`#kontakt`) | około 40 słów, brak `<img>` (tło najpewniej w CSS) |
| 2 | Intro: „Zadbaj o bezpieczeństwo dziecka…” + 2 akapity (oba jako H2), w tym adres sali | tekst | około 75 słów |
| 3 | Kotwica `#kontakt`: „Zapisz swoje dziecko na pierwszy, darmowy trening…” + „Wybierz wygodną formę kontaktu 👇” | tekst (nagłówki) | około 25 słów |
| 4 | „Włącz ten krótki film i zobacz jak wyglądają nasze zajęcia!” | **film** YouTube | 1 film |
| 5 | Drugi film + H2 „Odbierz darmową wejściówkę… skontaktuj się z nami!” | **film** + tekst | 1 film |
| 6 | Kontakt: „Zadzwoń do nas!” (`tel:`) i „Wyślij E-mail!” (`mailto:`) | 2 kafelki z ikoną emoji | około 10 słów |
| 7 | „Czego uczymy… dzieci w wieku 6-8 lat?” | zdjęcie + tekst (lista z myślnikami) | około 105 słów, 1 zdjęcie |
| 8 | „Czego uczymy… dzieci w wieku 9-15 lat?” | tekst + zdjęcie (układ odwrócony) | około 130 słów, 1 zdjęcie |
| 9 | „Tego dzieci ❌ NIE usłyszą…” / „Zamiast tego uczymy, że:” | zdjęcie + 2 listy (❌ i ✅) | około 85 słów, 1 zdjęcie |
| 10 | Cena-teaser: „Ile kosztuje karnet…?” / „od 229 zł / mies” | tekst (wyróżniona cena) | około 20 słów |
| 11 | „Jak wyglądają zajęcia?” (grupy wiekowe 6-8 i 9-15) | zdjęcie + tekst | około 135 słów, 1 zdjęcie |
| 12 | **Kafelki zalet** [S1]: „Dlaczego warto zapisać dziecko…?” + lead + **6 kafelków** (Ruch i zabawa, Skuteczna samoobrona, Pewność siebie i asertywność, Umiejętność radzenia sobie w trudnych sytuacjach, Szacunek i odpowiedzialność, Rozwój emocjonalny i społeczny) | **kafelki** (zdjęcie + tytuł + 1–2 zdania) w siatce 3×2 | około 150 słów, 6 zdjęć, 1 ikona |
| 13 | „Kto prowadzi zajęcia…?” + karta trenera „Daniel Folda” + „Doświadczenie trenerskie” | zdjęcie + bio | około 95 słów, 1 zdjęcie |
| 14–16 | **Najważniejsze informacje** [S2]: nagłówek + **3 siatki po 3 kafelki, razem 9 kafelków**: Pierwszy trening bezpłatny; Jak zapisać dziecko (tel./e-mail); Cena zajęć (3 karnety + opłata startowa); Czy bez doświadczenia; Kiedy można dołączyć; Jak często (+ link do grafiku); Jak długo trwają; Grupy wiekowe; Gdzie trenujemy (+ linki do 3 innych miast) | **kafelki** (ikona + tytuł + krótki tekst) | około 200 słów, 9 ikon |
| 17 | Galeria | **zdjęcia** (4 zdjęcia, m.in. egzaminy) | 4 zdjęcia, 0 słów |
| 18 | „Sprawdź opinie rodziców naszych Studentów!” + 2 leady + **8 zrzutów Opinii** z social media (tekst Opinii tylko w `alt`) | tekst + **zdjęcia-zrzuty** (siatka 2 kolumn × 4 rzędy) | około 45 słów widocznego tekstu, 8 obrazów |
| 19 | „Jak dotrzeć na nasze zajęcia… w Zabrzu?” | tekst (wskazówki dojścia) + **film** | około 60 słów, 1 film |
| 20 | „Masz inne pytanie?” + lead + **FAQ** (Elementor Toggle) z **11 pytaniami** | **FAQ / akordeon** | około 640 słów |
| 21 | CTA końcowe: „Zapisz swoje dziecko na pierwszy, bezpłatny trening…” / „Skontaktuj się z nami!” + przycisk „Zapisz się TERAZ!” (`#kontakt`) | tekst + przycisk | około 20 słów |
| – | Elementy globalne: pasek z hashtagami (marquee), stopka (oferta, informacje, kontakt, ocena „4.9/5”) | – | – |

Nie ma zakładek (tabs). FAQ jest jedynym elementem zwijanym.

Pytania FAQ [Z]: opłaty; dzieci młodsze niż 6 lat; w jakich miastach; gdzie i kiedy (lokalne); trening w różnych miastach; co zabrać; czy są dziewczynki; czy pierwszy trening bezpłatny; karty Multisport; dołączenie w trakcie sezonu; sparingi. Lista jest **prawie identyczna z FAQ huba**. Strona oferty ma tylko jedno pytanie lokalne więcej („Gdzie i kiedy odbywają się zajęcia?”).

### Sumy [Z]

- Słowa: **około 2040** (FAQ około 640, reszta około 1400)
- Obrazy: **37 `<img>`** w treści, w tym około 15 zdjęć treningowych, 8 zrzutów Opinii i około 14 ikon/emoji
- Filmy: **3** osadzone z YouTube (2 „jak wyglądają zajęcia”, 1 „jak dotrzeć”)
- Nagłówki: 1×H1, 60×H2, **0×H3**, 1 pusty H6 w FAQ. Hierarchia jest płaska i dekoracyjna, bo jako H2 oznaczono też leady, akapity, numer telefonu i adres e-mail.

### Linkowanie wewnętrzne [Z]

- W treści: link do huba tylko z menu („Dzieci → Treningi grupowe”) i ze stopki. **W treści nie ma linku zwrotnego do huba ani breadcrumbs.**
- Linki do Stron oferty w innych miastach (Bytom, Gliwice, Pyskowice) są w kafelku „Gdzie trenujemy?”.
- Cross-sell: link do BJJ dla dzieci Zabrze w FAQ.
- `/lokalizacje/` (Grafik) jest w FAQ. Zewnętrzny grafik `kravmagaunity.wod.guru/zajecia` jest w kafelku i w FAQ.
- Menu i stopka zawierają około 40 unikalnych linków wewnętrznych.

### CTA [Z]

1. Pasek nad nagłówkiem: „ODBIERZ BEZPŁATNĄ WEJŚCIÓWKĘ…” → `/gdzie-chcesz-trenowac/`
2. Przycisk w nagłówku: „ZAPISY ONLINE” → `/gdzie-chcesz-trenowac/`
3. Hero: „Zapisz dziecko TERAZ na pierwszy, bezpłatny trening!” → `#kontakt`
4. Sekcja kontaktu (#6): `tel:+48796695522`, `mailto:biuro@kravmagaunity.pl`
5. Kafelek „Jak zapisać dziecko” powtarza tel./e-mail
6. CTA końcowe „Zapisz się TERAZ!” → `#kontakt`

Na stronie **nie ma formularza** (0 `<form>`). Główny argument sprzedażowy powtarza się w całej treści: „pierwszy trening bezpłatny”.

### Schema [Z]

Generuje ją SASWP („Schema & Structured Data For WP v1.47”). Yoast nie wypisuje swojego `@graph`.

- `ItemList` → 3×`VideoObject` (name, thumbnailUrl, uploadDate, embedUrl z YouTube; autor to redaktor strony, `userInteractionCount: "0"`).
- `"@type": "sportsclub"` (**z małej litery**, więc nie jest to poprawny `SportsClub`), `@id: "LocalBusiness"` (niepoprawne, nie jest IRI). Pola: name (= title), url, description (= meta description), PostalAddress, telephone, geo, image, areaServed, founder, sameAs (FB/IG). **Puste:** openingHours, priceRange, hasMap, employee, review oraz `makesOffer.itemOffered.name/url`.
- Brak: `FAQPage`, `BreadcrumbList`, `Review`/`AggregateRating`, `Person` dla trenera, `Offer` z ceną.
- Niespójność danych: telefon w schema i w stopce to `538 919 516`, w treści `796 695 522`, a link `tel:` w stopce prowadzi do `796695522`.

## 2. Hub [H]: inwentarz Sekcji

Meta: title „Samoobrona Krav Maga dla dzieci Śląsk | Sprawdź zajęcia w Twoim mieście!”.

| # | Sekcja | Typ treści | Ilość treści |
|---|---|---|---|
| 1 | **H1** „samoobrona krav maga dla dzieci i młodzieży” + H2 „Zobacz gdzie odbywają się nasze zajęcia…” + przycisk „Sprawdź” (`href="#"`, martwy) | logo + tekst + przycisk | około 15 słów |
| 2–7 | **6 kart** (każda to osobna Sekcja z H2): Krav Maga Zabrze, Bytom, Gliwice, Pyskowice; BJJ dla dzieci Zabrze; Treningi indywidualne dla dzieci | **kafelek/karta**: zdjęcie + ikona dyscypliny + H2 + adres + 1–2 zdania (dni, grupy wiekowe) + przycisk „Sprawdź zajęcia” → Strona oferty | około 35–45 słów na kartę, 1 zdjęcie + 1 ikona |
| 8 | „Masz inne pytanie?” + **FAQ** z **10 pytaniami** (to samo co [Z] bez pytania lokalnego) | **FAQ / akordeon** | około 630 słów |
| – | Marquee + stopka (globalne) | – | – |

Sumy [H]: **około 930 słów** (około 300 bez FAQ), 13 `<img>` (6 zdjęć, 6 ikon, 1 logo), 0 filmów, 1×H1, 14×H2, 0×H3.

Linkowanie [H]: hub prowadzi do 6 Stron oferty przyciskami kart. FAQ linkuje do `/lokalizacje/` i BJJ. Nie ma tekstu wprowadzającego (SEO) ani Opinii.

Schema [H]: tylko `sportsclub`, tak samo jak w [Z] (adres Zabrze, `areaServed: Zabrze`, mimo że hub obejmuje 4 miasta). Nie ma `ItemList` ze Stronami oferty.

CTA [H]: przyciski „Sprawdź zajęcia” na kartach i globalne „ZAPISY ONLINE”. Brak telefonu i e-maila w treści.

## 3. Zrzuty z briefu

- **[S1] Kafelki zalet**: odpowiada Sekcji #12 w [Z]. Siatka 3 kolumn: zdjęcie kwadratowe u góry, tytuł, 1–2 zdania (około 20 słów). Na zrzucie widać rząd 1 (Ruch i zabawa, Skuteczna samoobrona, Pewność siebie i asertywność) oraz zdjęcia rzędu 2. Wersja na żywo ma 6 kafelków, czyli 2 rzędy.
- **[S2] Najważniejsze informacje**: odpowiada Sekcjom #14–16 w [Z]. Kafelki z ikoną, tytułem-pytaniem i krótką odpowiedzią (10–35 słów), siatka 3 kolumn. Zrzut pokazuje 6 kafelków, a wersja na żywo ma **9** (dodano „Jak długo trwają zajęcia?”, „Jakie są grupy wiekowe?”, „Gdzie trenujemy?”). Treść pokrywa się częściowo z FAQ (cena, pierwszy trening bezpłatny, dołączanie w trakcie roku).

## 4. Wnioski dla projektu (kandydaci na Sekcje Szablonu oferty)

Kolejność Sekcji na [Z] układa się w powtarzalny wzór, który da się przenieść do Szablonu oferty:

1. Hero (H1 + lead + CTA)
2. Intro / dla kogo
3. Kontakt (kotwica) + film
4. Program (Czego uczymy, osobno dla każdej grupy wiekowej)
5. „Czego nie usłyszą / czego uczymy” (wartości)
6. Teaser ceny
7. Jak wyglądają zajęcia
8. Kafelki zalet
9. Trener
10. Najważniejsze informacje (kafelki)
11. Galeria
12. Opinie
13. Dojazd (+ film)
14. FAQ
15. CTA końcowe

Na co zwrócić uwagę przy własnej implementacji. To obserwacje, nie decyzje:

- Hierarchia nagłówków: H2 tylko dla tytułów Sekcji, H3 dla kafelków, kart i pytań FAQ, a leady i akapity jako `<p>`.
- Opinie jako tekst (nie zrzuty), co pozwala dodać `Review`.
- `FAQPage` z FAQ oraz `BreadcrumbList` hub → Strona oferty.
- Poprawny typ `SportsClub`/`ExerciseGym` z prawidłowym `@id`, a dla huba `ItemList` ze Stronami oferty.
- Jedno źródło danych kontaktowych, bo dziś telefony są niespójne.
- Link zwrotny Strona oferty → hub w treści.
- Spójne grupy wiekowe: [Z] podaje w różnych miejscach 6-15, 9-15 i 9-13, a [H] 9-12.

Skala treści do zaplanowania: Strona oferty ma **około 1400 słów bez FAQ i około 2000 z FAQ**, ok. 15 zdjęć, 3 filmy, 9 + 6 kafelków i około 11 pytań FAQ. Hub ma **około 300 słów bez FAQ**, z czego większość przypada na karty.
