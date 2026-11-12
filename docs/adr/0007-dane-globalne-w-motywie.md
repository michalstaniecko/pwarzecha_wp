# Dane globalne programujemy w motywie, bez osobnej wtyczki

Cały kod projektu, łącznie z modelem danych globalnych (strona ustawień kontaktu, CPT Lokalizacja i Karnet z metaboxami, wiersze Grafiku, meta `pw_lokalizacja`, źródła Block Bindings), leży w motywie. Nie ma osobnej wtyczki ani mu-pluginu `pw-dane`. Konwencja WordPressa („plugin territory”) każe trzymać typy treści poza motywem, żeby przetrwały jego zmianę. Tu motyw jest pisany pod tę jedną stronę i nie przewidujemy jego wymiany, a jedna paczka to prostsze wdrożenie (jeden zip na Zenbox), jedna wersja i jedno miejsce na testy.

## Konsekwencje

- Zmiana albo wyłączenie motywu ukrywa w panelu Lokalizacje, Karnety i ustawienia kontaktu. Dane zostają w bazie i wracają po ponownym włączeniu motywu.
- Kod danych trzymamy w motywie w wydzielonym katalogu z własnym prefiksem, żeby dało się go w przyszłości przenieść do wtyczki bez zmiany nazw CPT, meta i opcji.
