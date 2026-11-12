# Uwagi Zleceniodawcy do design systemu i layoutów

Uwagi zebrane po prezentacji prototypów strony głównej (wybór wariantu: ADR 0002). Każda uwaga ma konsekwencje dla design systemu i layoutów, które trzeba uwzględnić w kolejnych iteracjach.

## 1. Wybrany wariant granatowy (A)

> Moim zdaniem wariant granatowy lepszy.

- Kontynuujemy design system w obecnym kształcie. Komponenty dodane tylko na potrzeby wariantu B (jasny nagłówek, dzielone hero bez nakładki, lista „Jak zacząć?” bez kart) nie trafiają do DS.
- Przy okazji poprawić w DS błąd z prototypu: nagłówek, hero i stopka są przesunięte o 20 px względem Sekcji. Przyczyną jest padding liczony wewnątrz `max-width: var(--wide-size)`. W prototypie obeszliśmy to regułą `max-width: calc(var(--wide-size) + 2 * var(--fluid-spacing-40))`.

## 2. Pozycja „O mnie” w menu

> Co do panelu menu - to tam też moim zdaniem powinien być guzik - o mnie. I tam rodzice sobie doczytają więcej szczegółów.

- W menu głównym ma się pojawić „O mnie”. Prowadzi do osobnej strony o Trenerze, przeznaczonej głównie dla rodziców.
- Wstępny skład menu: Dzieci, Dorośli, Szkoły i firmy, Grafik, Cennik, **O mnie**, Kontakt, a obok przycisk telefonu. **Otwarte pytanie:** skład i kolejność pozycji ustalimy przy budowie komponentów i layoutów. Trzeba wtedy też sprawdzić, czy 7 pozycji mieści się w jednej linii przy 1280 px.
- Do zaprojektowania nowy layout: strona „O mnie”. Prawdopodobnie Hero, potem Tekst ze zdjęciem (bio, doświadczenie, uprawnienia), Galeria, Opinie i CTA. Sekcja „Trener” na stronie głównej i na Stronach oferty powinna linkować do tej strony.

## 3. FAQ jako uniwersalny blok

> Na pierwszej stronie czy dałoby się zawrzeć zakładkę FAQ i tam takie informacje jak: co zabrać, moje dziecko ma 4 lata czy może spróbować, czy dziecko nie będzie agresywne po zajęciach, czy nauczy się dyscypliny itp.

- **Decyzja:** FAQ to blok (Sekcja), który można wstawić na dowolnej podstronie z dowolną treścią: własny tytuł, lead i lista pytań z odpowiedziami. Na stronie głównej pojawi się jedna instancja tego bloku. Nie robimy osobnej zakładki ani strony FAQ.
- Akordeon FAQ już jest w DS (natywne `<details>`/`<summary>`, pytania jako H3). Blok powinien generować dane strukturalne `FAQPage` (SEO).
- Przykładowe pytania na stronę główną (odpowiedzi musi dostarczyć Zleceniodawca):
  - Co zabrać na pierwszy trening?
  - Moje dziecko ma 4 lata, czy może spróbować?
  - Czy dziecko nie będzie agresywne po zajęciach?
  - Czy dziecko nauczy się dyscypliny?

## 4. Telefon i e-mail jako równorzędne sposoby zapisu

> Jak wygląda sprawa zamieszczenia adresu e-mail. Tak żeby rodzice jak chcą zapisać żeby mieli jasną informację, że mogą zadzwonić lub napisać maila. Bo teraz mail jest w stopce.

- W prototypie e-mail był tylko w stopce. Wszędzie tam, gdzie mówimy o zapisie, mają być widoczne oba kanały: „Zadzwoń lub napisz”, telefon i e-mail jako duże, klikalne elementy (`tel:` i `mailto:`).
- Dotyczy to:
  - paska CTA, który dziś ma tylko jeden przycisk; potrzebny wariant z dwoma przyciskami albo z telefonem i e-mailem obok siebie;
  - kroku „Jak się zapisać?” w „Jak zacząć?”;
  - bloku Kontakt (`#kontakt`), który w DS już ma telefon, e-mail i Messengera; sprawdzić, czy e-mail jest równie widoczny jak telefon.
- **Wymaganie:** telefon i e-mail mają być dostępne już na samej górze strony, w nagłówku lub tuż nad nim, na każdej podstronie.
- **Otwarte pytanie (do rozwiązania przy projektowaniu designów i layoutów):** jak dokładnie pokazać oba kanały u góry strony na desktopie i na mobile. Do przetestowania różne rozwiązania, np. górny pasek nad menu, oba kanały obok menu albo przycisk „Zadzwoń” plus ikona e-mail na mobile.
