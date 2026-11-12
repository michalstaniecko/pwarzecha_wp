# Prompty dla Claude Design

Do biletu „Design w Claude Design: design system i makiety” (#10). Kolejność:

1. Prompt 1 → design system. Załącz `docs/brief/845924557_1125295240056213_7100668203733018552_n.jpg` (paleta i charakter marki) oraz oba zrzuty kravmagaunity z `docs/brief/` (tylko jako punkt odniesienia dla gęstości treści, nie dla stylu).
2. Prompt 1b → uzupełnienie DS o komponenty i warianty rozstrzygnięte w #29 (`docs/design/decyzje-ds-i-layoutow.md`). Wykonaj go przed makietami stron.
3. Opublikuj DS, wpisz nazwy jego komponentów w sekcję „Komponenty DS” w prompcie 2.
4. Prompt 2 (wspólny wstęp) + prompty 2a–2i, każdy jako osobna iteracja/projekt. Zacznij od Strony oferty (2a): definiuje prawie wszystkie Sekcje, reszta je reużywa. Układy stron pochodzą z `docs/design/decyzje-ds-i-layoutow.md` (sekcja 3).
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
- Fasada mapy Google (ilustracja lub zdjęcie wejścia + adres + link „Wyznacz trasę”; opcjonalnie przycisk „Pokaż mapę”, który dopiero po kliknięciu ładuje mapę, z notą o Google; ADR 0008).
- Siatka Opinii: Opinie to GRAFIKI (zrzuty ekranu rekomendacji z Facebooka/Google o różnych proporcjach), nie tekst; zaproponuj, jak je ładnie ułożyć.
- Pasek CTA (nagłówek + dwa równorzędne przyciski: telefon i e-mail), blok kontaktu (telefon, e-mail, Messenger jako duże klikalne elementy).
- Okruszki (breadcrumbs).
- Nagłówek strony z menu: desktop i mobile (hamburger, bez JS-owych efektów poza otwarciem), przycisk telefonu zawsze pod ręką na mobile.
- Stopka: dane kontaktowe, Lokalizacje, social, linki prawne.
- Karta Aktualności (zdjęcie, tytuł, data, lead).

WYNIK
- Strona design systemu z próbkami wszystkich tokenów i komponentów (desktop i mobile).
- Na końcu TABELA TOKENÓW: slug, wartość, zastosowanie, w nazewnictwie powyżej, gotowa do przepisania do theme.json. Do tego wybrane fonty z odmianami i nazwa zestawu ikon.
```

---

## Prompt 1b: uzupełnienie design systemu (#29)

```
Uzupełnij opublikowany design system o poniższe komponenty i warianty. Używaj wyłącznie istniejących tokenów. Każdy nowy element pokaż na desktopie (1440 px) i mobile (375 px), z realistyczną polską treścią, i dopisz go do katalogu komponentów. Prototyp strony głównej jest zamrożony: nie poprawiaj go.

NOWE KOMPONENTY I WARIANTY
1. Tekst ze zdjęciem (na bazie sp-split): H2 + dowolna treść (akapity, listy, lista ❌/✅) + zdjęcie. Warianty: zdjęcie po prawej, po lewej, bez zdjęcia (sam tekst w szerokości do czytania). Proporcja zdjęcia 4:3 lub 1:1. Na telefonie najpierw tekst, potem zdjęcie. Pokaż dwie takie Sekcje pod sobą z naprzemiennym zdjęciem.
2. Trener: wariant Tekstu ze zdjęciem ze zdjęciem 1:1 i linkiem tekstowym „Więcej o trenerze →”.
3. Galeria: H2 + siatka zdjęć 4:3, 3 kolumny na desktopie, 2 na telefonie, bez lightboxa.
4. Teaser ceny: H2 + „od X zł / mies.” (styl ceny z karty Karnetu) + link „Zobacz cennik →”.
5. Hero: dodaj wariant bez zdjęcia (H1 + lead + opcjonalne przyciski na jasnym tle) i rozmiar „duży” (ok. 620 px, strona główna). Linijka „Pierwszy trening jest zawsze bezpłatny” (ikona gift) jest opcjonalna i ma edytowalny tekst; pokaż też wersję „Pierwsze zajęcia pokazowe są bezpłatne”. Usuń okruszki z Hero. Nie dodawaj wariantów „zdjęcie obok” ani „lista atutów”.
6. Okruszki: jeden wariant, jasny pasek (neutral-100) nad Hero, poza Hero. Pokaż nad Hero ze zdjęciem i nad Hero bez zdjęcia.
7. Pasek CTA: H2 + zdanie + dwa równorzędne przyciski „Zadzwoń” (tel:) i „Napisz e-mail” (mailto:); opcjonalny trzeci „Umów pierwszy trening”. Na telefonie przyciski jeden pod drugim, długi adres e-mail się zawija.
8. Kafelek z ikoną: dopuść 1–2 akcje jedna pod drugą (np. „Jak się zapisać?”: telefon i e-mail).
9. Siatka 4 kafelków: 4 kolumny od ok. 1100 px, 2×2 na tablecie, 1 kolumna na telefonie.
10. Blok kontaktu: bez wariantu z filmem (Film to osobna Sekcja). Dodaj opcjonalną linijkę z godzinami odbierania telefonu.
11. Lokalizacja: karta (nazwa, adres z map-pin, opis dojazdu, podgląd jako ilustracja lub zdjęcie wejścia, link „Wyznacz trasę”, link „Grafik tej Lokalizacji →”, opcjonalny przycisk „Pokaż mapę” z notą o Google). Bez wiersza godzin. Pokaż tryb „jedna” (z opcjonalnym filmem pod kartą) i „wszystkie” (2 karty obok siebie, na telefonie jedna pod drugą).
12. Karta oferty: adres opcjonalny; zamiast niego linijka obszaru z tą samą ikoną map-pin (np. „Chorzów, Katowice i okolice”). Opcjonalny nadtytuł z grupą oferty („Treningi grupowe”, „Treningi indywidualne”, „Dla placówek”).
13. Grafik: kotwica na każdą Lokalizację; pod tabelą Lokalizacji link „Więcej o zajęciach w Chorzowie →”.
14. Długi tekst (sp-prose): dodaj cytat, zdjęcie z podpisem, tabelę w treści (np. lista cookies) i link „Zmień ustawienia cookies”. Pokaż pełny podgląd prozy z H2, H3, listami i fasadą filmu w treści.
15. Pojedyncza Aktualność: H1 + data (ikona calendar) + zdjęcie wyróżniające 16:9, złożone z istniejących elementów.
16. Paginacja i komunikat „Brak wpisów” dla listy Aktualności.
17. Social: ikony Facebook, Instagram, YouTube, TikTok (logotypy z Simple Icons w stylu ikon Lucide) do stopki i Kontaktu.
18. Mapa znaczeń ikon: dopisz award (uprawnienia, certyfikaty), medal (staż, lata doświadczenia), wallet (cena, Karnet), ticket (Multisport, bez logo marki), heart-pulse (pierwsza pomoc), backpack (co zabrać), file-text (dokumenty), receipt (umowa, faktura), user-check (dopasowanie do uczestnika). calendar oznacza wyłącznie datę.

ZASADY DO DOPISANIA W DS
- Rytm CTA: między punktami kontaktu (pasek CTA, blok kontaktu, CTA w Hero, kafelek „Jak się zapisać?”) najwyżej 5–6 Sekcji. Strona kończy się punktem kontaktu. Bezpośrednio pod blokiem kontaktu nie ma paska CTA.
- Ton: trzy warianty z przykładowymi CTA: do rodzica (domyślny), do dorosłego (treningi 1:1 dla dorosłych), do placówki (przedszkola, szkoły).
- FAQ: pytanie jako H3 w summary, pierwsze pytanie może być otwarte.
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
- Brak formularzy. Telefon i e-mail to równorzędne sposoby zapisu: wszędzie, gdzie mówimy o zapisie (pasek CTA, „Jak się zapisać?”, Kontakt), pokazuj oba kanały jako duże klikalne elementy (tel: i mailto:). CTA prowadzą do sekcji Kontakt (#kontakt), a na stronach bez niej do /kontakt/#kontakt; obok zawsze telefon i e-mail.
- Rytm CTA: między punktami kontaktu (pasek CTA, Kontakt, CTA w Hero, kafelek „Jak się zapisać?”) najwyżej 5–6 Sekcji. Strona kończy się punktem kontaktu; bezpośrednio pod Sekcją Kontakt nie ma paska CTA.
- Nagłówek z menu (razem z telefonem i e-mailem u góry) jest gotowym komponentem DS: wstaw go bez zmian, nie projektuj od nowa.
- Okruszki jako jasny pasek nad Hero (nie w Hero) na każdej stronie poza główną: Strona główna › Tytuł strony.
- Lokalizacje z zajęciami grupowymi są dwie: Chorzów, Katowice (w tej kolejności).
- Treść realistyczna, po polsku, w długości docelowej (Strony oferty są długie, ok. 2000 słów). Nie skracaj do lorem ipsum ani do 2 zdań.
- Zdjęcia: realne zdjęcia z treningów dzieci i dorosłych na sali (placeholdery z opisem kadru).
- Na końcu wypisz listę użytych Sekcji z wariantami oraz ewentualne NOWE SEKCJE / NOWE KOMPONENTY.

DOSTĘPNE SEKCJE (z wariantami)
1. Hero: H1, lead, 1–2 CTA, opcjonalna linijka „Pierwszy trening jest zawsze bezpłatny” (edytowalna). Warianty: ze zdjęciem-tłem, bez zdjęcia; rozmiar standard / duży (strona główna).
2. Tekst ze zdjęciem: H2, tekst/listy (także listy ❌/✅), zdjęcie 4:3 lub 1:1. Warianty: zdjęcie lewo / prawo / bez zdjęcia.
3. Kafelki: H2, lead, N kafelków. Warianty: ze zdjęciem (3×2), z ikoną (3×3 albo 4 w siatce 4 kolumn). Kafelek z ikoną może mieć 1–2 akcje (telefon i e-mail).
4. Kontakt (kotwica #kontakt): telefon, e-mail, Messenger, opcjonalne godziny odbierania telefonu. Bez filmu (Film to osobna Sekcja).
5. CTA: H2 + zdanie + dwa równorzędne przyciski: telefon i e-mail.
6. Trener: bio i zdjęcie 1:1 Przemka + lokalny nagłówek, link „Więcej o trenerze” do strony /trener/.
7. Opinie: H2, lead, siatka Opinii (grafiki-zrzuty).
8. FAQ: H2, lead, pytania H3 w akordeonie. Uniwersalny blok: dowolna podstrona, dowolne pytania (bez osobnej strony FAQ).
9. Film: H2, opcjonalny tekst, jeden film YouTube (fasada).
10. Galeria: H2, siatka zdjęć 4:3 (bez lightboxa).
11. Karty ofert: karty Stron oferty (zdjęcie, opcjonalny nadtytuł z grupą, H3, adres albo obszar, 1–2 zdania, przycisk). Na Stronie oferty jako opcjonalne „Zobacz też”.
12. Karnety: karty Karnetów.
13. Teaser ceny: nagłówek + „od X zł / mies.” + link do Cennika.
14. Grafik: dzień, godzina, Grupa, wg Lokalizacji, pod tabelą link do Strony oferty. Warianty: pełny, dla jednej Lokalizacji.
15. Lokalizacja: nazwa, adres, podgląd, opis dojazdu, „Wyznacz trasę”, „Grafik tej Lokalizacji”, opcjonalnie „Pokaż mapę” i film. Warianty: jedna, wszystkie.
16. Aktualności: ostatnie 3 Aktualności.

NIE MA stron-hubów: „Treningi grupowe”, „Treningi indywidualne” i „Dla firm i instytucji” to tylko etykiety menu z rozwijaną listą Stron oferty. Nie linkuj do nich i nie wstawiaj ich do okruszków.
```

## 2a: Strona oferty (zacznij od niej)

```
Strona: „Samoobrona dla dzieci i młodzieży – Chorzów”, pełna Strona oferty. Okruszki: Strona główna › Samoobrona dla dzieci i młodzieży – Chorzów. Ton: do rodzica.
Układ (Szablon oferty), dokładnie w tej kolejności:
Hero (ze zdjęciem, linijka „Pierwszy trening jest zawsze bezpłatny”) → Kontakt (#kontakt, z godzinami telefonu) → Film → Tekst ze zdjęciem (intro, zdjęcie prawo) → Tekst ze zdjęciem ×3 (program dla 3 Grup wiekowych, każda z własnym H2, naprzemiennie lewo/prawo) → CTA → Tekst ze zdjęciem (wartości, lista ❌/✅) → Teaser ceny → Tekst ze zdjęciem (jak wyglądają zajęcia) → Galeria → Kafelki ze zdjęciem (6 zalet) → CTA → Trener → Kafelki z ikoną (9 „najważniejszych informacji”: pierwszy trening bezpłatny, jak się zapisać (telefon i e-mail), cena, czy bez doświadczenia, kiedy dołączyć, jak często, co zabrać, ubezpieczenie, Multisport) → FAQ (10 pytań, pierwsze otwarte) → CTA → Opinie → Grafik (dla jednej Lokalizacji) → Lokalizacja (jedna, z opisem dojazdu) → Karty ofert „Zobacz też” (3 powiązane Strony oferty) → CTA.
Pokaż, jak zmienia się rytm tła Sekcji, żeby długa strona nie była monotonna.
Dodatkowo pokaż na jednym ekranie różnice dla pozostałych Stron oferty: Hero strony dla placówek (linijka „Pierwsze zajęcia pokazowe są bezpłatne”, ton do dyrektora), „Jak wyglądają zajęcia” jako 4 kafelki z ikoną w siatce 4 kolumn (strony 1:1 i dla placówek), Lokalizacja w trybie „wszystkie” (strony 1:1), Karta oferty z obszarem zamiast adresu.
```

## 2b: Strona główna

```
Strona główna. Bez okruszków. Przedstawia Trenera i prowadzi bezpośrednio do 6 Stron oferty (nie ma hubów), z mocnym „Pierwszy trening bezpłatny”.
Układ, dokładnie w tej kolejności:
Hero (ze zdjęciem, rozmiar „duży”) → Karty ofert (6 kart w kolejności menu: Chorzów, Katowice, 1:1 dla dorosłych, 1:1 dla dzieci, Przedszkola, Szkoły i instytucje; nadtytuł karty = grupa oferty; plakietka „Pierwszy trening gratis” na 2 kartach grupowych; karty 1:1 i dla placówek z obszarem zamiast adresu) → Tekst ze zdjęciem (ok. 300 słów o samoobronie dla dzieci w Chorzowie i Katowicach) → Trener → Kafelki z ikoną „Jak zacząć?” (4 w siatce 4 kolumn, tło granatowe; „Jak się zapisać?” z telefonem i e-mailem) → CTA → Opinie → Grafik (pełny) → Lokalizacja (wszystkie, 2 sale) → CTA → FAQ → Aktualności → Kontakt (#kontakt, koniec strony, bez paska CTA pod spodem).
FAQ z pytaniami rodziców: Co zabrać na pierwszy trening? Moje dziecko ma 4 lata, czy może spróbować? Czy dziecko nie będzie agresywne po zajęciach? Czy dziecko nauczy się dyscypliny? (odpowiedzi przykładowe, docelowe dostarczy Zleceniodawca).
```

## 2c: Trener

```
Strona: „Trener” (/trener/), Przemek, pisana głównie dla rodziców. Nadtytuł w Hero: „O mnie”. Okruszki: Strona główna › Trener.
Układ: Hero (ze zdjęciem) → Tekst ze zdjęciem (bio, zdjęcie 1:1) → Kafelki z ikoną (uprawnienia, staż, pierwsza pomoc, praca z placówkami; Sekcja opcjonalna) → Tekst ze zdjęciem (jak pracuję z dziećmi, lista ❌/✅) → Film (opcjonalny) → CTA → Galeria → Opinie → Karty ofert „Gdzie trenujemy” (6 kart) → FAQ (opcjonalne) → CTA.
CTA prowadzą do /kontakt/#kontakt, obok telefon i e-mail.
```

## 2d: Cennik

```
Strona: „Cennik”. Układ: Hero (bez zdjęcia) → Karnety (3 Karnety: 3, 6 i 12 miesięcy, środkowy „Polecany”) → Tekst ze zdjęciem w wariancie bez zdjęcia (zasady i dopłaty: opłata startowa, Multisport, rodzeństwo, nieobecności) → FAQ (4–5 pytań o płatności) → CTA.
```

## 2e: Grafik

```
Strona: „Grafik zajęć”. Układ: Hero (bez zdjęcia) → Grafik (pełny: 2 Lokalizacje, Chorzów i Katowice, w każdej 3–5 pozycji dzień/godzina/Grupa; pod każdą tabelą link „Więcej o zajęciach w {mieście} →”) → CTA.
Szczególnie dopracuj wersję mobile tabeli.
```

## 2f: Kontakt

```
Strona: „Kontakt”. Układ: Hero (bez zdjęcia, bez przycisków) → Kontakt (#kontakt, z godzinami telefonu) → Lokalizacja (wszystkie: 2 sale, z przyciskiem „Pokaż mapę”) → Tekst ze zdjęciem w wariancie bez zdjęcia: dane firmy (nazwa, NIP) + ikony social. Bez formularza, bez FAQ i bez paska CTA (cała strona jest kontaktem).
```

## 2g: Aktualności: lista i wpis

```
Dwa szablony (to nie są Sekcje, tylko szablony WordPressa):
1. Lista Aktualności: Hero (bez zdjęcia, H1 + lead), siatka kart Aktualności 3×3 (zdjęcie, tytuł, data, lead), paginacja, pod spodem pasek CTA. Pokaż też stan „Brak wpisów”.
2. Pojedyncza Aktualność, dokładnie w tej kolejności: tytuł (H1), data, zdjęcie wyróżniające → treść wpisu z typowymi elementami (H2, H3, akapity, lista, cytat, zdjęcie z podpisem, fasada filmu, galeria) → pasek CTA → „Inne Aktualności” (3 karty + link „Wszystkie Aktualności →”).
Szerokość tekstu wygodna do czytania. Pod tytułem tylko data (bez autora i czasu czytania).
```

## 2h: Stopka

```
Stopka w pełnej rozdzielczości (desktop + mobile). Nagłówek i menu są już gotowe w DS, nie projektuj ich.
- linki: wszystkie Strony oferty pogrupowane jak w menu (Treningi grupowe: Chorzów, Katowice; Treningi indywidualne: Dla dorosłych, Dla dzieci; Dla firm i instytucji: Przedszkola, Szkoły i instytucje) + Trener, Cennik, Grafik, Aktualności, Kontakt, Polityka prywatności, Ustawienia cookies,
- dane kontaktowe (telefon, e-mail, Messenger), adresy 2 Lokalizacji (Chorzów, Katowice), social (Facebook, Instagram, YouTube, TikTok).
```

## 2i: Polityka prywatności, 404 i wyniki wyszukiwania

```
Trzy proste szablony z istniejących komponentów:
1. Polityka prywatności: Hero (bez zdjęcia, H1 + zdanie + data aktualizacji) → długi tekst (rozdziały H2/H3, listy, tabela cookies, link „Zmień ustawienia cookies”). Bez paska CTA.
2. Strona 404: Hero (bez zdjęcia, „Nie znaleziono strony”) → krótki tekst → Karty ofert (6) jako podpowiedź, dokąd przejść.
3. Wyniki wyszukiwania: Hero (bez zdjęcia, „Wyniki dla: …”) → lista wyników (tytuł, lead) z paginacją; stan „Brak wyników” z linkami do ofert.
```
