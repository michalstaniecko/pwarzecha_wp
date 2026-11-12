# Filmy YouTube i Mapy Google osadzamy wyłącznie przez fasady

Treści stron trzecich (filmy YouTube, mapy Google) nigdy nie ładują się razem ze stroną. Zamiast nich renderujemy w PHP fasadę: miniaturę lub podgląd, opis i przycisk. Dopiero kliknięcie podmienia fasadę na iframe. Przed kliknięciem strona nie wysyła żadnego żądania do Google ani YouTube. Pod fasadą jest krótka nota, że po kliknięciu dane trafią do Google. Kliknięcie po przeczytaniu noty traktujemy jako świadomą zgodę w miejscu użycia, więc fasady nie czekają na zgodę z banera CookieYes.

- **Film:** blok VideoFacade, miniatura serwowana z naszego serwera, po kliknięciu iframe z `youtube-nocookie.com`. `core/embed` dla YouTube jest wyłączony, także w treści wpisów, bo od razu ładuje iframe.
- **Mapa:** karta Lokalizacji ma zawsze adres i link „Wyznacz trasę” (otwiera Google Maps w nowej karcie, bez iframe). Osadzona mapa jest opcją bloku („Pokaż mapę”): iframe dopiero po kliknięciu, z notą. Podgląd to ilustracja lub zdjęcie wejścia, a nie zrzut z Google Maps (licencja).

Dzięki temu Lighthouse mierzy stronę bez skryptów i ciasteczek Google (cel mobile ≥ 95, ADR 0005), a baner cookies nie blokuje treści. Rozwiązanie łączy trzy osobno opisane wymagania: zgody i Politykę prywatności (#5, #22), wydajność oraz brief, który wymienia na Kontakcie osadzoną mapę.

## Konsekwencje

- Fasada wymaga kilku linii JS do podmiany na iframe. To jedyny dozwolony JS na froncie poza nawigacją. Test E2E „brak JS na froncie” z ADR 0005 dopuszcza ten skrypt i sprawdza, że przed kliknięciem nie ma żadnego żądania do domen Google i YouTube.
- Polityka prywatności opisuje oba przypadki: YouTube (tryb `nocookie`) i Mapy Google, z informacją, że dane trafiają do Google dopiero po kliknięciu.
- Jeśli prawnik uzna, że kliknięcie nie wystarcza, fasady dostaną dodatkowy warunek zgody z kategorii CookieYes, a sam markup się nie zmieni.
