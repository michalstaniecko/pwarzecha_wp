# Ikony: zestaw Lucide jako inline SVG renderowany w PHP

Jedynym zestawem ikon w projekcie jest [Lucide](https://lucide.dev) (licencja ISC, liniowe ikony 24×24 ze stałą grubością linii). Ikony trafiają do HTML jako inline SVG generowane przez helper PHP motywu z plików SVG z pakietu `lucide-static`. Nie używamy fontu ikon, sprite'a ładowanego przez JS ani biblioteki JS na froncie. Kolor ikony to `currentColor`, więc sterują nim tokeny `theme.json`. W edytorze wybór ikony to atrybut bloku (nazwa ikony Lucide), a podgląd renderuje ten sam PHP.

Jeden spójny zestaw daje jednolity wygląd, a inline SVG nie dodaje żądań sieciowych ani JS (cel Lighthouse mobile ≥ 95). Do repo dołączamy tylko te ikony, których używamy, a nie cały pakiet. Ikony spoza Lucide (np. logotypy serwisów społecznościowych, których Lucide nie ma) dodajemy jako wyjątki w tym samym formacie i tym samym helperem.
