# Rok szkolny na listach wpisów + kategoria logopedy

Data: 2026-09-16 · Status: zatwierdzony, do zaplanowania

Powiązane: [PLAN.md](../../../PLAN.md) · [MIGRACJA.md](../../../MIGRACJA.md) · [CLAUDE.md](../../../CLAUDE.md)

---

## Problem

Wpisy grup i ogłoszenia narastają bez końca. Dziś `/aktualnosci/` i archiwa
kategorii pokazują wszystkie cztery roczniki naraz, jedną paginowaną listą.
Rodzic wchodzący na „Misie" dostaje wpisy z 2023 wymieszane z bieżącymi.

Potrzebne: wpisy pogrupowane rokiem szkolnym, stare niewidoczne w bieżących
listach, ale wciąż osiągalne. Osobno — wpisy logopedki, które są poradnikami
i nie starzeją się wraz z rokiem szkolnym.

## Stan wyjściowy

- Taksonomia płaska: 6 grup + `ogloszenia`. 430 wpisów, 2023–2026.
- `index.php` obsługuje wszystkie listy; brak `pre_get_posts` na froncie.
- `template-parts/filtr-kategorii.php` — filtr po kategorii, zwykłe odnośniki, bez JS.
- `front-page.php` — 3 ostatnie z `ogloszenia`.
- `template-parts/aktualnosci-grupy.php` — 3 ostatnie z kategorii grupy, wołane
  z `page.php` po slugu strony.
- „Kącik logopedy" istnieje jako strona (ID 48) z samymi godzinami pracy;
  to opis kategorii 19 przeniesiony z Joomli (MIGRACJA.md:532).
- `inc/seo.php` polega na rdzeniu. Rdzeń **nie** wystawia `rel=canonical`
  na archiwach — tylko na pojedynczych wpisach i stronach.

## Decyzje

| Decyzja | Uzasadnienie |
|---|---|
| Rok szkolny liczony z daty publikacji, nie z taksonomii | zero pracy redaktora, zero pustych terminów; przeniesienie wpisu = zmiana daty, co WP już umie. Podtrzymuje decyzję z 2026-09-11 |
| Granica 1 września, bez przesunięcia na sierpień | prostota; puste okno na starcie roku obsłużone komunikatem |
| Archiwum kategorii pokazuje domyślnie tylko bieżący rok | to jest ta „archiwizacja" — stare przestaje zasłaniać nowe |
| Pusty rok = uczciwy komunikat + odnośnik wstecz | bez cichego podmieniania roku za plecami użytkownika |
| Adres `?rok=2026-2027`, bez reguł przepisania | działa od pierwszej minuty; paginacja obsłużona przez rdzeń |
| Bloki „3 ostatnie" na stronach grup tną po roku, z komunikatem gdy pusto | „przestały być widoczne w ostatnich wpisach" dotyczy też kafelków |
| Strona główna **nie** tnie — zawsze 3 najnowsze ogłoszenia | strona główna nigdy nie ma być pusta |
| Przełącznik lat jako `<select>` + „Pokaż" | skaluje się bez końca, formularz GET działa bez JS |
| Lista lat wspólna dla całego serwisu | jedno zapytanie zamiast liczenia pokrycia per kategoria |
| `logopeda` jako zwykła kategoria, wyłączona z cięcia po roku | nowa porada widoczna w aktualnościach, stare mają stały adres |
| `?rok=` dostaje `noindex, follow` | 7 kategorii × 4 lata = 28 list duplikatów w indeksie |
| Wpisy logopedki z konta `przedszkole` | decyzja klienta; bez zakładania nowego konta |
| Import 27 artykułów z kategorii 19 Joomli | 23 nigdy nie przeszły migracji, treść czysta |
| Kategoria 64 „Archiwum Logopedy" pominięta | 16 odcinków #zostańwdomu z lockdownu; przedszkole samo tę gałąź wyłączyło (`published = 0`) |

## Architektura

### 1. Model roku szkolnego — `inc/rok-szkolny.php`

Nowy plik. Jedno zagadnienie, jeden plik; `inc/helpers.php` zostaje workiem na drobiazgi.

| Funkcja | Zwraca |
|---|---|
| `przedszkole_rok_szkolny( $data = null )` | `'2026/2027'`; miesiąc ≥ 9 → `R/R+1`, inaczej `R-1/R` |
| `przedszkole_rok_slug( '2026/2027' )` | `'2026-2027'` — ukośnik nie przejdzie w adresie |
| `przedszkole_rok_z_slug( '2026-2027' )` | `'2026/2027'` |
| `przedszkole_lata_szkolne()` | tablica slugów, malejąco, od bieżącego do roku najstarszego wpisu |
| `przedszkole_rok_z_zapytania()` | slug z `?rok=` po walidacji; brak lub śmieci → rok bieżący |

`przedszkole_lata_szkolne()` — jedno zapytanie o `MIN(post_date)` opublikowanych
wpisów, wynik w transiencie `przedszkole_lata`, czyszczonym na `save_post`
i `deleted_post`.

**Walidacja jest wymogiem bezpieczeństwa, nie kosmetyką.** Wartość `?rok=` trafia
do `date_query`. Przyjmujemy wyłącznie slugi obecne w `przedszkole_lata_szkolne()`
— przynależność do listy, nie dopasowanie wzorca. Nic spoza listy nie dotyka zapytania.

### 2. Cięcie list — `pre_get_posts`

W tym samym pliku. Warunki wejścia:

- `! is_admin()` i `$zapytanie->is_main_query()`
- `is_home()` lub `is_category()`
- **nie** `is_category( 'logopeda' )`

Mechanizm: `date_query` od 1 września roku otwierającego 00:00:00 do 31 sierpnia
roku zamykającego 23:59:59, `inclusive => true`. Bez pól własnych — data
publikacji jest jedynym źródłem prawdy.

Nietknięte: wyszukiwarka, archiwa dat, pojedyncze wpisy, kanały RSS, cały panel.

**Dwa wyjątki dopisane podczas wdrożenia (2026-09-16).** Specyfikacja mówiła
„listy aktualności i archiwa kategorii" i milcząco zakładała widok HTML.
Implementacja pokazała, że to za mało:

* **Kanały RSS.** `/aktualnosci/feed/` i kanały wszystkich kategorii mają
  `is_home()` albo `is_category()` prawdziwe, więc dostawały cięcie i zwracały
  zero pozycji. Kanał niesie „co nowego", a nie „co w bieżącym roczniku" —
  i nie ma w nim ani przełącznika lat, ani komunikatu o pustym roku, więc
  obcięcie zamienia go w niewyjaśnioną pustkę. WordPress ogłasza kanały
  kategorii w `<head>` archiwów, więc to realny adres, nie martwy zakątek.
* **Archiwa dat połączone z kategorią.** `/2024/?cat=7` ma jednocześnie
  `is_date()` i `is_category()` prawdziwe — to nie są flagi wykluczające się.
  Cięcie doklejało `date_query` bieżącego rocznika do ograniczenia roku
  kalendarzowego, które WordPress buduje osobno w `WHERE`. Przecięcie „rok 2024"
  z „rocznik 2026/2027" jest zawsze puste, więc adres, który wcześniej zwracał
  19 wpisów, zaczął pokazywać „Brak wpisów".

Oba warunki siedzą w jednym predykacie `przedszkole_widok_podlega_rocznikowi(
WP_Query $zapytanie )`, nad którym stoją zarówno hak, jak i
`przedszkole_rok_aktywny()` używana przez szablony. Dwie funkcje z powielonym
warunkiem rozjechały się w ciągu jednego commita — predykat sprawia, że to
przestaje być możliwe.

### 3. Przełącznik lat — `template-parts/przelacznik-lat.php`

Formularz GET: widoczna `<label for="rok">`, `<select name="rok">` z latami
malejąco (`selected` na bieżącym), `<button>Pokaż</button>`. `action` to adres
bieżącego archiwum bez parametrów. Zero JS, pełna obsługa klawiatury.

Nie renderuje się przy jednym roku w serwisie ani na `/kategoria/logopeda/`.
Miejsce: `index.php`, tuż pod `filtr-kategorii`, w tej samej belce.

Paginacja nie wymaga kodu: `paginate_links()` scala parametry z bieżącego adresu
do odnośników stron (rdzeń, `wp-includes/general-template.php` — „Merge additional
query vars found in the original URL").

### 4. Pusty rok — `index.php`

Gałąź „Brak wpisów" rozgałęzia się. Gdy cięcie po roku było aktywne:
„W roku szkolnym 2026/2027 nie ma jeszcze wpisów" + odnośnik do poprzedniego
roku, o ile taki istnieje w `przedszkole_lata_szkolne()`. Poza cięciem — dzisiejszy
tekst bez zmian.

### 5. Bloki „3 ostatnie"

`template-parts/aktualnosci-grupy.php` → **`template-parts/wpisy-kategorii.php`**;
obsługuje teraz także logopedę, więc stara nazwa wprowadzałaby w błąd.

Dwie gałęzie, bez tablicy konfiguracyjnej:

- slug w `przedszkole_grupy()` — nagłówek „Aktualności grupy", cięcie po roku
  włączone; pusto = nagłówek + „Grupa nie dodała jeszcze wpisów w roku 2026/2027"
  + odnośnik do poprzedniego roku
- slug `logopeda` — nagłówek „Artykuły logopedy", bez cięcia; pusto = sekcja
  się nie pokazuje
- cokolwiek innego — `return`, jak dziś

`page.php` woła to po slugu strony i sam nie wymaga zmian.

**`front-page.php` zostaje nietknięty.**

### 6. Kategoria logopedy

Nazwa „Kącik logopedy", slug **`logopeda`**. Slug strony 48 zmienia się
z `kacik-logopedy` na `logopeda` z dwóch powodów: dopasowanie po slugu, którego
motyw już używa dla grup, oraz zgodność ze starym adresem Joomli
`dla-rodzicow/logopeda`, co upraszcza przekierowania 301 w Etapie 9.

W filtrze kategorii pojawi się sama — `filtr-kategorii.php` układa najpierw grupy
w kolejności z `przedszkole_grupy()`, resztę alfabetycznie. Pigułka bez koloru,
tak jak „Ogłoszenia".

### 7. Odzyskanie treści ze zrzutu Joomli

Skrypt jednorazowy w `tools/`. Źródło: lokalny zrzut zaimportowany do bazy
roboczej `joomla_tmp`.

Bilans w zrzucie (kategoria 19 „Logopeda", kategoria 64 „Archiwum Logopedy"):

| Zbiór | Sztuk | Zakres | Los |
|---|---|---|---|
| kat. 19, opublikowane, już w WP jako `ogloszenia` | 4 | 2024–2026 | przepiąć |
| kat. 19, opublikowane, nieobecne w WP | 23 | 2015–2023 | zaimportować |
| kat. 19, w koszu (`state = -2`) | 4 | 2020-03 | pominąć |
| kat. 64, kategoria niepublikowana | 16 | 2020–2021 | pominąć |

Kroki:

1. Przepięcie czterech istniejących wpisów z `Ogłoszenia` na `logopeda`,
   **po slugu, nie po ID** — identyfikatory WP nie są stabilne między instalacjami.
2. Import 23 brakujących: `catid = 19 AND state = 1`, z pominięciem tamtych czterech.
   `introtext` + `fulltext` → treść, `title` → tytuł, `alias` → slug,
   `created` → data publikacji, autor `przedszkole`, kategoria `logopeda`.
3. Czyszczenie HTML-a i konwersja na bloki tym samym trybem co poprzednia migracja
   (MIGRACJA.md:221–222 — style inline, `<font>`, puste `<p>`, atrybuty Joomli).
4. Dwa artykuły zawierają `<img>`; pliki przepadły ze starym serwerem, więc
   znaczniki wycinamy — zgodnie z decyzją „Obrazki wycięte z migrowanej treści".
   Żaden artykuł nie odwołuje się do PDF-a.

Kryteria odbioru:

- 27 wpisów w kategorii `logopeda`
- żaden ze slugów tych 27 nie został w `Ogłoszeniach`
- daty publikacji rozłożone 2015–2026, bez zbicia w dniu importu
- `/kategoria/logopeda/` pokazuje pełną listę, bez przełącznika lat
- strona „Kącik logopedy" pokazuje pod godzinami pracy 3 ostatnie artykuły

### 8. SEO — `inc/seo.php`

Obecność `?rok=` → `noindex, follow` filtrem `wp_robots`. Także dla roku bieżącego,
bo i on duplikuje adres bez parametru. `follow`, żeby wyszukiwarka nadal chodziła
po odnośnikach do wpisów. Mapa witryny bez zmian — wpisy są w niej osobno.

### 9. Higiena

- Podbicie `Version:` w `style.css` **razem** z `PRZEDSZKOLE_VERSION`
  w `functions.php` — CSS przełącznika to zmiana w motywie, a rozjazd tych dwóch
  kosztował już jedno śledztwo (CLAUDE.md, pułapka 6).
- Skasowanie bazy `joomla_tmp` po zakończeniu migracji. Zrzut zawiera dane
  osobowe i nie trafia do repo.
- PLAN.md: rejestr decyzji — wpis z 2026-09-11 „rok szkolny z daty publikacji,
  nie z kategorii" **zostaje w mocy**; dokładamy do niego filtrowanie, nie
  zastępujemy taksonomią. Plus odnotowanie, że 23 wpisy logopedki wróciły ze zrzutu.
- MIGRACJA.md: korekta bilansu — „Kącik Logopedy | 4" była liczbą zaniżoną.

## Dostępność

Strona placówki publicznej, obowiązuje WCAG 2.1 AA.

- `<select>` z widoczną etykietą `<label for="rok">`, nie samym `aria-label`
- przycisk „Pokaż" — formularz działa bez JS i bez zdarzenia `change`
- przełącznik w `<nav>` z `aria-label`, obok istniejącego filtra kategorii
- komunikat o pustym roku to zwykły tekst w `<h2>` + `<p>`, czytany normalnie
- pigułka kategorii `logopeda` bez własnego koloru — dziedziczy zweryfikowany
  kontrast wariantu domyślnego

## Czego świadomie nie robimy

Taksonomii `rok_szkolny` · reguł przepisania i `flush_rewrite_rules()` ·
zmian w panelu administracyjnym · nowego konta autora · importu kategorii 64 ·
JavaScriptu · własnych bloków Gutenberga.
