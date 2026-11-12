# Prompty dla Claude Design

Do biletu „Design w Claude Design: design system i makiety” (#10). Kolejność:

1. Prompt 1 → design system. Załącz `docs/brief/845924557_1125295240056213_7100668203733018552_n.jpg` (paleta i charakter marki) oraz oba zrzuty kravmagaunity z `docs/brief/` (tylko jako punkt odniesienia dla gęstości treści, nie dla stylu).
2. Opublikuj DS, wpisz nazwy jego komponentów w sekcję „Komponenty DS” w prompcie 2.
3. Prompt 2 (wspólny wstęp) + prompty 2a–2h, każdy jako osobna iteracja/projekt. Zacznij od Strony oferty (2a): definiuje prawie wszystkie Sekcje, reszta je reużywa.
4. Po zatwierdzeniu: z tabeli tokenów piszemy `theme.json` w repo, który od tej chwili jest źródłem prawdy (Claude Design go czyta przy kolejnych zmianach).

---

## Prompt 1: design system

```
Zbuduj design system dla strony WWW zajęć z samoobrony prowadzonych przez trenera Przemka (Chorzów, Katowice).

KONTEKST
- Główna oferta: samoobrona dla dzieci (grupy wiekowe), do tego treningi grupowe i indywidualne dla dorosłych oraz zajęcia dla szkół, firm i instytucji.
- Odbiorcy: przede wszystkim rodzice (decydują i płacą), w drugiej kolejności dyrektorzy szkół / osoby z firm, dorośli kursanci.
- Strona jest nastawiona na SEO lokalne: długie Strony oferty (ok. 2000 słów, ok. 15 zdjęć, ok. 16 sekcji jedna pod drugą). Design musi dobrze nieść dużo tekstu i zdjęć, nie tylko wyglądać dobrze w hero.
- Główny argument sprzedażowy: „Pierwszy trening bezpłatny”. Kontakt tylko telefonem, e-mailem i Messengerem (nie ma formularza).
- Język: wyłącznie polski. Fonty MUSZĄ mieć pełne polskie znaki (latin-ext: ą ć ę ł ń ó ś ź ż), sprawdź na nagłówkach pisanych wersalikami.

MARKA I CHARAKTER
- Paleta z załączonej grafiki: granat (kolor dominujący, tła sekcji, nagłówek, stopka), pomarańcz (akcent: CTA, linie, wyróżnienia), biel, czerń. Odczytaj odcienie z grafiki; możesz dodać jaśniejsze/ciemniejsze warianty i 2–3 neutralne szarości.
- Typografia nagłówków w duchu grafiki: mocny, szeroki, geometryczny bezszeryfowy, dobrze wyglądający w wersalikach z rozstrzeleniem. Tekst ciągły: czytelny bezszeryfowy do długich akapitów.
- Charakter: energiczny i sportowy, ale budzący zaufanie rodzica: bezpiecznie, profesjonalnie, ciepło. Nie „agresywne sztuki walki” i nie czarno-neonowo.

OGRANICZENIA TECHNICZNE (ważne, design trafi do motywu WordPress na theme.json, bloki Gutenberga)
- Cel wydajności: Lighthouse mobile ≥ 95. Maks. 2 rodziny fontów (najlepiej variable), łącznie maks. 4 odmiany; fonty self-hostowane (np. z Google Fonts). Bez efektów wymagających JS (parallax, animacje scrollowe, karuzele/slidery). Akordeon FAQ ma działać na natywnym <details>/<summary>.
- Kontrast tekstu co najmniej WCAG AA. Sprawdź szczególnie tekst na pomarańczu i pomarańcz na bieli; jeśli nie przechodzi, zaproponuj ciemniejszy odcień akcentu do tekstu/przycisków.
- Ikony: wyłącznie zestaw Lucide (inline SVG, bez fontów ikon), patrz ADR 0004.
- Zdjęcia: zakładaj realne zdjęcia z sali (różna jakość, telefon). Zaproponuj zasady kadrowania i proporcje (np. 4:3, 16:9, 1:1), zaokrąglenia, ewentualną nakładkę na zdjęciach w hero.
- Wszystkie wartości jako tokeny. Nazewnictwo tokenów ma się dać przenieść 1:1 do theme.json:
  - kolory: slugi typu base, contrast, primary, primary-dark, primary-light, accent, accent-dark, neutral-100…neutral-900 (dopasuj do palety),
  - rozmiary fontów: small, medium, large, x-large, xx-large, xxx-large, płynne (min/max dla 375 px i 1440 px, do clamp()),
  - odstępy: skala 20, 30, 40, 50, 60, 70, 80 (od drobnych odstępów po odstęp między sekcjami), płynne dla dużych wartości,
  - layout: szerokość treści (contentSize) i szeroka (wideSize),
  - promienie, cienie (maks. 2–3), grubość linii.

KOMPONENTY DO ZAPROJEKTOWANIA
- Przyciski: główny (CTA), drugorzędny, przycisk telefonu „Zadzwoń”, link tekstowy; stany hover/focus (widoczny focus z klawiatury).
- Nagłówek sekcji: nadtytuł (opcjonalnie), H2, lead.
- Tła sekcji: biel, jasny neutralny, granat (tekst biały). Sekcje będą układane w dowolnej kolejności, więc przejścia między tłami muszą zawsze wyglądać dobrze.
- Kafelek ze zdjęciem (zdjęcie + H3 + 1–2 zdania), kafelek z ikoną (ikona + H3 + krótki tekst, może zawierać link/telefon).
- Karta oferty (zdjęcie, H3, lokalizacja/adres, 1–2 zdania, przycisk).
- Karta Karnetu (nazwa, cena „X zł / mies.”, okres, lista „co obejmuje”, opcjonalna plakietka „Polecany”, CTA).
- Lista z ikonami ❌/✅ (np. „czego unikamy / co robimy”).
- Akordeon FAQ (pytanie H3).
- Tabela Grafiku: dzień, godzina, Grupa (np. „dzieci 6–9 lat”), pogrupowana wg Lokalizacji; musi działać na telefonie (bez poziomego scrolla).
- Fasada filmu YouTube (miniatura + przycisk play, film ładuje się dopiero po kliknięciu).
- Fasada mapy Google (statyczny podgląd/ilustracja + adres + przycisk „Otwórz w Mapach”).
- Siatka Opinii: Opinie to GRAFIKI (zrzuty ekranu rekomendacji z Facebooka/Google o różnych proporcjach), nie tekst; zaproponuj, jak je ładnie ułożyć.
- Pasek CTA (nagłówek + przycisk), blok kontaktu (telefon, e-mail, Messenger jako duże klikalne elementy).
- Okruszki (breadcrumbs).
- Nagłówek strony z menu: desktop i mobile (hamburger, bez JS-owych efektów poza otwarciem), przycisk telefonu zawsze pod ręką na mobile.
- Stopka: dane kontaktowe, Lokalizacje, social, linki prawne.
- Karta Aktualności (zdjęcie, tytuł, data, lead).

WYNIK
- Strona design systemu z próbkami wszystkich tokenów i komponentów (desktop i mobile).
- Na końcu TABELA TOKENÓW: slug, wartość, zastosowanie, w nazewnictwie powyżej, gotowa do przepisania do theme.json. Do tego wybrane fonty z odmianami i nazwa zestawu ikon.
```

---

## Prompt 2: wspólny wstęp do makiet

Wklejaj na początku każdego z promptów 2a–2h.

```
Zaprojektuj makietę (desktop 1440 px i mobile 375 px) dla strony zajęć samoobrony trenera Przemka. Używaj WYŁĄCZNIE opublikowanego design systemu: jego tokenów i komponentów. Jeśli czegoś brakuje, nazwij to wprost jako „NOWY KOMPONENT” i opisz, zamiast wymyślać styl ad hoc.

KOMPONENTY DS (uzupełnione po publikacji design systemu):
<wklej listę nazw komponentów z DS>

ZASADY
- Strona składa się z SEKCJI: samodzielnych, pełnoszerokościowych bloków ułożonych jeden pod drugim. Każda Sekcja musi wyglądać dobrze w dowolnej kolejności i z dowolnym sąsiadem, bo edytor (nie programista) będzie je kopiował, przesuwał i usuwał. Tło Sekcji to ustawienie (biel / jasny neutralny / granat), nie część jej projektu.
- Używaj tylko Sekcji z listy poniżej. Jeśli strona naprawdę potrzebuje innej, oznacz ją etykietą „NOWA SEKCJA” i krótko uzasadnij.
- Nagłówki: jedno H1 na stronę, tylko w Hero. Tytuł każdej Sekcji to H2. Kafelki, karty i pytania FAQ to H3.
- „Pierwszy trening bezpłatny” to główny argument: Hero, pasek CTA, kafelki.
- Brak formularzy. Telefon i e-mail to równorzędne sposoby zapisu: wszędzie, gdzie mówimy o zapisie (pasek CTA, „Jak się zapisać?”, Kontakt), pokazuj oba kanały jako duże klikalne elementy (tel: i mailto:). CTA prowadzą do sekcji Kontakt (#kontakt), dzwonią albo otwierają e-mail.
- Nagłówek z menu (razem z telefonem i e-mailem u góry) jest gotowym komponentem DS: wstaw go bez zmian, nie projektuj od nowa.
- Okruszki nad Hero na każdej stronie poza główną: Strona główna › Tytuł strony.
- Treść realistyczna, po polsku, w długości docelowej (Strony oferty są długie, ok. 2000 słów). Nie skracaj do lorem ipsum ani do 2 zdań.
- Zdjęcia: realne zdjęcia z treningów dzieci i dorosłych na sali (placeholdery z opisem kadru).
- Na końcu wypisz listę użytych Sekcji z wariantami oraz ewentualne NOWE SEKCJE / NOWE KOMPONENTY.

DOSTĘPNE SEKCJE (z wariantami)
1. Hero: H1, lead, 1–2 CTA, opcjonalna lista atutów. Warianty: ze zdjęciem (tło lub obok), bez zdjęcia.
2. Tekst ze zdjęciem: H2, tekst/listy (także listy ❌/✅), zdjęcie. Warianty: zdjęcie lewo / prawo / bez zdjęcia.
3. Kafelki: H2, lead, N kafelków. Warianty: ze zdjęciem (3×2), z ikoną (3×3).
4. Kontakt (kotwica #kontakt): telefon, e-mail, Messenger. Wariant: z filmem obok.
5. CTA: nagłówek + telefon i e-mail (oba kanały obok siebie).
6. Trener: bio i zdjęcie Przemka + lokalny nagłówek, przycisk „Więcej o trenerze” do strony /trener/.
7. Opinie: H2, lead, siatka Opinii (grafiki-zrzuty).
8. FAQ: H2, lead, pytania H3 w akordeonie. Uniwersalny blok: dowolna podstrona, dowolne pytania (bez osobnej strony FAQ).
9. Film: H2, opcjonalny tekst, 1–2 filmy YouTube (fasada).
10. Galeria: H2, siatka zdjęć (bez lightboxa).
11. Karty ofert: karty Stron oferty (zdjęcie, H3, lokalizacja, 1–2 zdania, przycisk). Na Stronie oferty jako opcjonalne „Zobacz też”.
12. Karnety: karty Karnetów.
13. Teaser ceny: nagłówek + „od X zł / mies.” + link do Cennika.
14. Grafik: dzień, godzina, Grupa, wg Lokalizacji. Warianty: pełny, dla jednej Lokalizacji.
15. Lokalizacja: nazwa, adres, mapa (fasada), opcjonalny opis dojazdu i film. Warianty: jedna, lista wszystkich.
16. Aktualności: ostatnie 3 Aktualności.

NIE MA stron-hubów: „Treningi grupowe”, „Treningi indywidualne” i „Dla firm i instytucji” to tylko etykiety menu z rozwijaną listą Stron oferty. Nie linkuj do nich i nie wstawiaj ich do okruszków.
```

## 2a: Strona oferty (zacznij od niej)

```
Strona: „Samoobrona dla dzieci w Chorzowie”, pełna Strona oferty. Okruszki: Strona główna › Samoobrona dla dzieci w Chorzowie.
Układ (Szablon oferty), dokładnie w tej kolejności:
Hero (ze zdjęciem) → Tekst ze zdjęciem (intro) → Kontakt (z filmem) → Tekst ze zdjęciem ×3 (program dla grup 4–6, 7–10, 11–14 lat; naprzemiennie lewo/prawo) → Tekst ze zdjęciem (wartości, lista ❌/✅) → Teaser ceny → Tekst ze zdjęciem (jak wyglądają zajęcia) → Kafelki ze zdjęciem (6 zalet) → Trener → Kafelki z ikoną (9 „najważniejszych informacji”: pierwszy trening bezpłatny, jak się zapisać, cena, czy bez doświadczenia, kiedy dołączyć, jak często, co zabrać, ubezpieczenie, Multisport) → Grafik (dla jednej Lokalizacji) → Galeria → Opinie (8) → Lokalizacja (jedna, z opisem dojazdu) → FAQ (10 pytań) → Karty ofert „Zobacz też” (opcjonalnie, 2–3 powiązane Strony oferty) → CTA.
Pokaż, jak zmienia się rytm tła Sekcji, żeby długa strona nie była monotonna.
```

## 2b: Strona główna

```
Strona główna. Ma przedstawić Trenera i trzy filary oferty (Treningi grupowe, Treningi indywidualne, Dla firm i instytucji) i prowadzić bezpośrednio do Stron oferty (nie ma hubów), z mocnym „Pierwszy trening bezpłatny”.
Zaproponuj układ z dostępnych Sekcji (np. Hero → Karty ofert pogrupowane wg 3 filarów → Tekst ze zdjęciem → Trener → Kafelki → Opinie → Grafik → FAQ → Aktualności → CTA).
FAQ (jedna instancja) z pytaniami rodziców: Co zabrać na pierwszy trening? Moje dziecko ma 4 lata, czy może spróbować? Czy dziecko nie będzie agresywne po zajęciach? Czy dziecko nauczy się dyscypliny? (odpowiedzi przykładowe, docelowe dostarczy Zleceniodawca). Tu najbardziej prawdopodobne są NOWE SEKCJE; jeśli którąś dodajesz, oznacz i uzasadnij.
```

## 2c: Trener

```
Strona: „Trener” (/trener/), Przemek, pisana głównie dla rodziców. Propozycja układu: Hero (ze zdjęciem) → Tekst ze zdjęciem (bio, doświadczenie) → Kafelki z ikoną (uprawnienia, certyfikaty, osiągnięcia) → Film → Galeria → Opinie → CTA.
```

## 2d: Cennik

```
Strona: „Cennik”. Układ: Hero (bez zdjęcia) → Karnety (3 Karnety: 3, 6 i 12 miesięcy, środkowy „Polecany”) → Tekst bez zdjęcia (uwagi: opłata startowa, Multisport, rodzeństwo) → CTA.
```

## 2e: Grafik

```
Strona: „Grafik zajęć”. Układ: Hero (bez zdjęcia) → Grafik (pełny: 3 Lokalizacje, w każdej 3–5 pozycji dzień/godzina/Grupa) → CTA.
Szczególnie dopracuj wersję mobile tabeli.
```

## 2f: Kontakt

```
Strona: „Kontakt”. Układ: Hero (bez zdjęcia) → Kontakt → Lokalizacja (lista wszystkich, 3 sale z mapami-fasadami) → dane firmy i social (prosta Sekcja tekstowa). Bez formularza.
```

## 2g: Aktualności: lista i wpis

```
Dwa szablony (to nie są Sekcje, tylko szablony WordPressa):
1. Lista Aktualności: nagłówek, siatka kart Aktualności (zdjęcie, tytuł, data, lead), paginacja.
2. Pojedyncza Aktualność: tytuł (H1), data, zdjęcie wyróżniające, treść wpisu z typowymi elementami (H2, H3, akapity, lista, cytat, zdjęcie w treści, osadzony film), na końcu pasek CTA i 3 inne Aktualności.
Szerokość tekstu wygodna do czytania.
```

## 2h: Stopka

```
Stopka w pełnej rozdzielczości (desktop + mobile). Nagłówek i menu są już gotowe w DS, nie projektuj ich.
- linki: wszystkie Strony oferty pogrupowane jak w menu (Treningi grupowe: Chorzów, Katowice; Treningi indywidualne: Dla dorosłych, Dla dzieci; Dla firm i instytucji: Przedszkola, Szkoły i instytucje) + Trener, Cennik, Grafik, Aktualności, Kontakt, Polityka prywatności, Ustawienia cookies,
- dane kontaktowe (telefon, e-mail, Messenger), adresy Lokalizacji, social.
```
