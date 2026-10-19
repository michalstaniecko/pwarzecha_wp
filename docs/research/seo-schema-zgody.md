# SEO, schema, zgody i analityka: wybór wtyczek i konfiguracji

Research do issue #5. Stan źródeł na 2026-10-19. Każde twierdzenie ma przypis do źródła pierwotnego (lista na końcu).

## TL;DR — rekomendacja

| Obszar | Decyzja |
|---|---|
| SEO (meta, sitemap, breadcrumbs, bazowy graf schema) | **Yoast SEO free** |
| Schema wielu lokalizacji | **Bez osobnej wtyczki do schemy.** Motyw dokłada węzły `SportsActivityLocation` (podtyp `LocalBusiness`) do grafu Yoasta przez filtr `wpseo_schema_graph_pieces`, z danych globalnych/CPT lokalizacji. Alternatywa płatna: Yoast Local SEO (w pakiecie Yoast SEO Premium, 118,80 USD/rok) |
| FAQPage | **Nie inwestować w rich results** — Google wyłączył wyniki FAQ 7 maja 2026. Treść FAQ zostaje (dla użytkowników), znacznik opcjonalny |
| Course / Service | **Nie** pod rich results — „Course info” usunięte we wrześniu 2025; `Service` nie jest funkcją Google Search. Ewentualnie jako zwykłe węzły w grafie, bez oczekiwań |
| BreadcrumbList | Yoast free (blok/funkcja breadcrumbs Yoasta) |
| Baner zgód | **CookieYes (wtyczka WP)** z włączonym GCM v2 w trybie **Basic** |
| GA4 | **gtag.js bezpośrednio** w motywie (tuż po `wp_head()`), **bez Site Kit i bez GTM** |
| Search Console | **Weryfikacja DNS (właściwość domeny)**; meta tag tylko jako awaryjna alternatywa |

„Wtyczka do schemy” z briefu = Yoast SEO + kod motywu. Rekomendujemy wytłumaczyć klientowi, że osobna wtyczka dublowałaby graf Yoasta (dwa `Organization`/`WebPage`) zamiast go uzupełniać.

## 1. Czy Yoast wystarcza do schemy?

### Co daje Yoast SEO free
- Zawsze: `Organization` (lub `Person`), `WebSite`, `WebPage`, `BreadcrumbList`, `ImageObject`; na wpisach `Article`; `FAQPage` przy użyciu bloku FAQ Yoasta, `HowTo` przy bloku How-to [Y1].
- **Nie** generuje `LocalBusiness` [Y1].

### Yoast Local SEO (płatny)
- Wiele lokalizacji, godziny otwarcia, wybór typu działalności, store locator, geo sitemap, mapy Google [Y2].
- Sprzedawany wyłącznie w pakietach: Yoast SEO Premium 118,80 USD/rok, WooCommerce SEO 178,80, SEO AI+ 358,80 (netto) [Y2].
- Schema: lokalizacja główna (Primary Location) jest scalana z `Organization` → `LocalBusiness`; każda inna strona lokalizacji dostaje własny `LocalBusiness` z `branchOf` → `Organization`; archiwum lokalizacji → `ItemList` [Y3].
- Lista typów pochodzi ze schema.org (podtypy `LocalBusiness`) [Y4]. Nie potwierdziliśmy w dokumentacji, że w dropdownie jest konkretnie `SportsActivityLocation` — do sprawdzenia w panelu, jeśli wybierzemy tę ścieżkę.
- Minusy dla nas: koszt roczny, wbudowane mapy Google (konflikt z fasadą map — trzeba by ich nie używać), dodatkowy CPT/UI obok naszego modelu danych lokalizacji.

### Rekomendowane: motyw rozszerza graf Yoasta
Yoast udostępnia API schemy [Y5]:
- `wpseo_schema_graph_pieces` — dodawanie własnych klas dziedziczących po `Abstract_Schema_Piece`,
- `wpseo_schema_<piece>` (np. `wpseo_schema_organization`, `wpseo_schema_webpage`) — modyfikacja pojedynczego węzła,
- `wpseo_schema_graph` — modyfikacja całego grafu,
- `Schema_IDs::ORGANIZATION_HASH` itd. — stabilne `@id` do referencji.

Plan: motyw dodaje piece `Location` generujący `SportsActivityLocation` dla każdej lokalizacji (Chorzów, Katowice, …) na stronie danej lokalizacji (oraz ewentualnie listę na stronie głównej/kontaktu), z `parentOrganization`/`branchOf` → `#organization`. Zero dodatkowych wtyczek, zero JS, jeden spójny graf JSON-LD.

Wymagania Google dla `LocalBusiness` [G1]: wymagane `name`, `address` (`PostalAddress`); zalecane `geo` (≥5 miejsc po przecinku), `openingHoursSpecification`, `telephone`, `url`, `priceRange`. Każdą lokalizację oznaczać jako osobną encję; używać możliwie najbardziej szczegółowego podtypu (Google pokazuje przykład `HealthClub`) [G1]. `SportsActivityLocation` jest podtypem `LocalBusiness` w schema.org [S1].

### Status rich results (ważne dla oczekiwań klienta)
- **FAQ**: od 2023 tylko dla serwisów rządowych/zdrowotnych; **od 7 maja 2026 nie wyświetlany w ogóle**, dokumentacja usunięta 15 czerwca 2026 [G2][G3].
- **Course info**: usunięte z dokumentacji 9 września 2025 (nie pojawia się w wyszukiwarce) [G3]. W galerii zostaje tylko „Course list” (karuzela kursów jednego dostawcy) [G4] — mało realne dla zajęć dla dzieci, nie priorytet.
- **HowTo**: nieobecny w galerii [G4].
- Wspierane i dla nas istotne: **Local business, Organization, Breadcrumb**, ewentualnie **Event** (np. zajęcia pokazowe/obozy) i **Review snippet** (tylko jeśli recenzje są zbierane na stronie i spełniają zasady) [G4].
- `Service` nie jest funkcją Google Search — można dodać dla semantyki, bez efektu w SERP.

Wniosek: FAQ zostawiamy jako treść (pomaga użytkownikom i jest sensownym tekstem dla wyszukiwarki), blok FAQ Yoasta może zostać, ale nie obiecujemy rozszerzonych wyników.

## 2. CookieYes + Google Consent Mode v2 + GA4

### Consent Mode v2 — fakty
- Parametry: `ad_storage`, `analytics_storage` + nowe w v2 `ad_user_data`, `ad_personalization` [G5].
- `gtag('consent','default',…)` musi być wywołany przed jakimkolwiek `config`/`event`; kolejność ma znaczenie [G5].
- `wait_for_update` (ms) dla banerów ładowanych asynchronicznie [G5].
- **Basic**: tagi Google zablokowane do interakcji z banerem; przy odmowie nic nie trafia do Google. **Advanced**: tagi ładują się od razu i wysyłają pingi bez cookies; modelowanie dokładniejsze [G6].

### CookieYes
- Wtyczka WP `cookie-law-info` v3.5.7 (aktualizacja 14.10.2026), 1M+ instalacji, GCM v2 (Basic i Advanced) „out of the box”, auto-blokowanie skryptów w darmowym planie [C1].
- CookieYes deklaruje status Google-certified CMP [C2].
- **Do włączenia GCM we wtyczce wymagane jest połączenie z web app CookieYes** [C3]. Konfiguracja: CookieYes > Google Consent Mode → włączyć, domyślnie kategorie nie-niezbędne „denied”, region „ALL”, `wait_for_update` domyślnie 2000 ms; skrypt gtag/GTM umieścić tuż pod `wp_head()`, CookieYes wstrzykuje się sam [C3].
- **Limit darmowego planu: 5 000 odsłon** (rozszerzalny w planach płatnych) [C1] — do potwierdzenia z klientem, czy ruch go nie przekroczy; jeśli tak, plan płatny CookieYes.

### GA4: gtag vs Site Kit vs GTM
| Opcja | Ocena |
|---|---|
| **gtag.js w motywie** | Najmniej warstw: jeden skrypt Google, ładowany po zgodzie (Basic). Kolejność gwarantowana przez CookieYes + umieszczenie pod `wp_head()` [C3]. **Rekomendowane.** |
| Site Kit | Wymaga dodatkowo wtyczki WP Consent API; Site Kit sam ustawia domyślną zgodę i każe **wyłączyć** GCM w CMP, żeby nie było konfliktów [K1]. Dodaje panel w adminie i więcej PHP/JS — dwie wtyczki zamiast jednej linijki. GSC i GA można oglądać w natywnych panelach Google. |
| GTM | Dodatkowy kontener JS przed gtag; sensowny tylko przy wielu tagach marketingowych (piksele, Ads). Brief ich nie wymaga. |

### Koszt wydajnościowy (Lighthouse mobile ≥ 95)
- Lighthouse testuje „czystą” sesję bez zgody. W trybie **Basic** gtag.js nie odpala się przed zgodą [G6], więc nie obciąża wyniku Lighthouse — to główny argument za Basic zamiast Advanced (Advanced ładuje gtag od razu).
- Kosztem zostaje skrypt banera CookieYes (wstrzykiwany w `<head>`) i render banera. Należy zmierzyć na stagingu; baner nie powinien być elementem LCP (stylować go tak, by nie był największym elementem w viewport mobile) i nie powodować CLS (pozycja fixed/overlay). Dokumentacja CookieYes nie podaje danych o wydajności [C2] — to wymaga pomiaru, nie deklaracji.
- Fasady YouTube/Maps: ich załadowanie po kliknięciu powinno respektować kategorie zgód CookieYes (YouTube/Maps ustawiają cookies stron trzecich) — do zaprojektowania przy fasadach (osobny temat).

## 3. Search Console: DNS vs meta
- **Właściwość domeny wymaga weryfikacji DNS** (TXT) i obejmuje wszystkie protokoły i subdomeny (http/https, www/bez www) [G7].
- Meta tag działa tylko dla właściwości z prefiksem URL, musi być na stronie głównej dla niezalogowanych, w pierwszych 2 MB HTML [G7]; ryzyko utraty weryfikacji przy zmianie motywu/wtyczki.
- Rekord DNS trzeba zostawić na stałe [G7].
- **Rekomendacja: DNS** (wymaga dostępu do panelu DNS klienta). Meta tag (np. pole w Yoast) tylko gdy dostęp do DNS jest niemożliwy.
- Sitemap: `sitemap_index.xml` z Yoasta zgłosić w GSC.

## 4. Podział odpowiedzialności: motyw vs wtyczki

**Wtyczki**
- Yoast SEO free: title/meta description, canonical, robots, XML sitemap, Open Graph, breadcrumbs (`BreadcrumbList`), bazowy graf (`Organization`, `WebSite`, `WebPage`), ustawienia organizacji (logo, `sameAs`), blok FAQ (opcjonalnie).
- CookieYes: baner, auto-blokowanie, GCM v2 (domyślne `denied`, update po zgodzie), rejestr zgód.

**Motyw**
- Dane globalne lokalizacji (nazwa, adres, geo, telefon, godziny, URL strony lokalizacji) — jedno źródło prawdy, używane zarówno w treści, jak i w schemie.
- Klasa(y) schema rozszerzające graf Yoasta (`wpseo_schema_graph_pieces`) → `SportsActivityLocation` per lokalizacja, referencje do `#organization`; ewentualnie `Event` dla wydarzeń.
- Snippet gtag.js (GA4) pod `wp_head()`, ID pomiaru z opcji motywu.
- Fasady YouTube/Maps zintegrowane z kategoriami zgód CookieYes.
- Bez osobnej wtyczki do schemy, bez Site Kit, bez GTM.

## Otwarte kwestie
1. Ruch miesięczny vs limit 5 000 odsłon w darmowym CookieYes.
2. Dostęp do DNS klienta (weryfikacja GSC).
3. Czy klient upiera się przy „osobnej wtyczce do schemy” — jeśli tak, Yoast Local SEO (Premium) jest jedyną opcją niekolidującą z grafem Yoasta; wtedy trzeba zweryfikować dostępność typu `SportsActivityLocation` i wyłączyć jej mapy.

## Źródła
- [Y1] Yoast developer — Schema: Yoast SEO for WordPress: https://developer.yoast.com/features/schema/plugins/yoast-seo/
- [Y2] Yoast — Local SEO for WordPress: https://yoast.com/wordpress/plugins/local-seo/
- [Y3] Yoast developer — Schema: Local SEO: https://developer.yoast.com/features/schema/plugins/local-seo/
- [Y4] Yoast help — My business is not listed: https://yoast.com/help/my-business-is-not-listed-can-you-add-it
- [Y5] Yoast developer — Schema API: https://developer.yoast.com/features/schema/api/
- [G1] Google Search Central — Local business structured data: https://developers.google.com/search/docs/appearance/structured-data/local-business
- [G2] Google Search Central — FAQPage (deprecation notice): https://developers.google.com/search/docs/appearance/structured-data/faqpage
- [G3] Google Search Central — Documentation updates: https://developers.google.com/search/updates
- [G4] Google Search Central — Search gallery: https://developers.google.com/search/docs/appearance/structured-data/search-gallery
- [G5] Google Tag Platform — Set up consent mode: https://developers.google.com/tag-platform/security/guides/consent
- [G6] Google Tag Platform — Consent mode concepts (basic vs advanced): https://developers.google.com/tag-platform/security/concepts/consent-mode
- [G7] Search Console Help — Verify site ownership: https://support.google.com/webmasters/answer/9008080
- [S1] schema.org — SportsActivityLocation: https://schema.org/SportsActivityLocation
- [C1] WordPress.org — CookieYes plugin: https://wordpress.org/plugins/cookie-law-info/
- [C2] CookieYes — Implementing Google Consent Mode: https://www.cookieyes.com/documentation/implementing-google-consent-mode-using-cookieyes/
- [C3] CookieYes — GCM v2 integration with WordPress plugin: https://www.cookieyes.com/documentation/google-consent-mode-v2-integration-with-cookieyes-wordpress-plugin/
- [K1] Site Kit — Consent mode: https://sitekit.withgoogle.com/documentation/using-site-kit/consent-mode/
