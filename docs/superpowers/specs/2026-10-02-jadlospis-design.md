# Blok „Jadłospis” — karta na każdy dzień tygodnia

Data: 2026-10-02 · Status: zatwierdzony, do zaplanowania

Powiązane: [PLAN.md](../../../PLAN.md) · [WDROZENIE.md](../../../WDROZENIE.md) · [CLAUDE.md](../../../CLAUDE.md)

---

## Problem

Intendent co tydzień przepisuje jadłospis na stronę „Jadłospis” (ID 45). Dziś
robi to ręcznie: najpierw zakładkami (`core/tabs`), ostatnio tabelą — za każdym
razem układ jest w treści strony, więc każda zmiana wyglądu to przepisanie
tygodnia, a jedno nieostrożne kliknięcie zostawia pół tabeli. Na telefonie
tabela z czterema kolumnami tekstu po 250 znaków jest nieczytelna.

Potrzebne: pięć kart (poniedziałek–piątek) w stałym układzie, do którego
intendent wpisuje tylko treść, a rodzic czyta wygodnie na każdym ekranie.

## Stan wyjściowy

- Strona `jadlospis` (ID 45) jest dzieckiem „Dla rodziców”. Treść: pusty blok
  `core/tabs`, tabela `core/table` z tygodniem 22–26.06.2026, trzy puste akapity.
- Komórki tabeli: śniadanie, obiad, podwieczorek; alergeny pogrubione (`<strong>`).
- Rola `intendent` (`inc/role.php`) redaguje wyłącznie tę stronę, bez
  `unfiltered_html`.
- Jedyny własny blok to `przedszkole/osoba` (`blocks/osoba/`) — wzór plików,
  rejestracji i edytora bez kroku budowania.
- Paleta grup w `style.css`: pary `--X-tlo` / `--X-tekst` dla sześciu grup.
- `.wrap` ma 1140px, nagłówek strony `.site-header` jest `sticky`.
- Nunito lokalnie jako font zmienny 400–800.

## Decyzje

| Decyzja | Uzasadnienie |
|---|---|
| Własny blok dynamiczny, nie wzorzec | przechodzi próg z CLAUDE.md: pięć kart co tydzień, układ psuł się w rękach pracownika (zakładki, potem tabela) |
| Jeden blok z danymi w atrybutach, bez bloków-dzieci | struktura jest stała, dzieci dokładałyby kontekst i blokady szablonu bez zysku |
| Jeden tydzień na stronie, intendent nadpisuje | bez przełącznika tygodni, bez JS do nawigacji, bez starych tygodni do sprzątania |
| `multiple: false` | drugiego jadłospisu na stronie nie da się wstawić przez pomyłkę |
| Bez blokady (`lock`) | decyzja klienta; usunięty blok wraca z rewizji |
| Kalendarz: klikalne tylko poniedziałki | błędu nie da się popełnić; wynik równy klikniętemu |
| Nowy blok startuje z poniedziałkiem bieżącego tygodnia (w weekend następnego) | stan „bez daty” praktycznie nie istnieje |
| Dzień wolny: stały napis „Dzień wolny” | obrazek z podpisem w osobnej sesji |
| Dzień wolny zachowuje wpisaną treść w atrybutach | odznaczenie przywraca dane, front je pomija |
| Formatowanie pól jak w zwykłym akapicie | decyzja klienta |
| Pusty posiłek — sekcja znika | karta pokazuje tylko to, co wpisano |
| Dzień bez posiłków (i nie wolny) — „Jadłospis w przygotowaniu” | pusta karta wyglądałaby jak błąd |
| Notka o alergenach jako zwykły akapit pod blokiem | edytowalna przez intendenta, poza komponentem; WCAG 1.3.1 — znaczenie pogrubienia podane tekstem |
| Kolory dni: Pn Żabki, Wt Zajączki, Śr Kotki, Cz Misie, Pt Wiewiórki | najbliżej grafiki; Jeżyki (czerwień) odpadają, fiolet z grafiki zastąpiony pomarańczem |
| Etykiety posiłków: śniadanie zielony, obiad pomarańcz, podwieczorek niebieski | z palety grup, kontrast policzony niżej |
| Zakres tygodnia jako `<h2>` nad kartami | jak pod tytułem na grafice; h1 zostaje z szablonu |
| Blok szerszy niż treść, do 1400px | przy 1140px karta ma ~210px na ~250 znaków na posiłek |
| Tablet: 2 kolumny, piątek wyśrodkowany | decyzja klienta |
| „Dziś” wyróżnione, na telefonie przewinięcie do karty | rodzic otwiera stronę w czwartek i nie przewija trzech dni |
| Tydzień, który minął — bez zmian, bez komunikatu | zakres dat w nagłówku mówi sam za siebie |
| Bez druku i PDF | decyzja klienta; druk z menu przeglądarki |
| Dane z tabeli przeniesione do bloku | praktyczny wygląd od pierwszej minuty |

## Architektura

### 1. Pliki

```
theme/przedszkole/
├── blocks/jadlospis/
│   ├── block.json
│   ├── edytor.js           widok w edytorze, wp.element.createElement
│   ├── edytor.asset.php    zależności edytora, ręcznie
│   ├── render.php          cały front
│   ├── widok.js            „Dziś” — viewScript, tylko na stronie z blokiem
│   └── widok.asset.php     zależności widoku (pusta lista), ręcznie
├── inc/blok-jadlospis.php  funkcje pomocnicze: zakres dat, dni tygodnia
└── assets/img/posilek-{sniadanie,obiad,podwieczorek}.svg
```

- Rejestracja: dopisanie katalogu do istniejącej `przedszkole_rejestruj_bloki()`
  w `inc/blok-osoba.php`. Kategoria „Przedszkole” już jest.
- `inc/blok-jadlospis.php` dołączany w `functions.php` jak pozostałe pliki `inc/`.
- CSS: nowa numerowana sekcja w `style.css`. Style wyłącznie panelu (przycisk
  kalendarza, położenie przełącznika) w `assets/css/editor.css`.

### 2. `block.json`

- `name`: `przedszkole/jadlospis`, `category`: `przedszkole`, `icon`: `food`
- `keywords`: jadłospis, menu, posiłki, obiad
- `supports`: `html: false`, `multiple: false`, `reusable: false`, bez `align`
- `attributes`:
  - `poczatek` — `string`, data poniedziałku `RRRR-MM-DD`, domyślnie `""`
  - `dni` — `array`, 5 obiektów `{ wolny: bool, sniadanie: string, obiad: string, podwieczorek: string }`;
    pola tekstowe to HTML z `RichText`
- `example`: jeden wypełniony dzień
- `editorScript`: `file:./edytor.js`, `viewScript`: `file:./widok.js`, `render`: `file:./render.php`

`save` zwraca `null` — blok jest w pełni dynamiczny, w treści strony stoi sam
komentarz z atrybutami.

### 3. Dane poza atrybutami — `inc/blok-jadlospis.php`

| Funkcja | Zwraca |
|---|---|
| `przedszkole_jadlospis_dni()` | 5 pozycji `{ nazwa, grupa }`: Poniedziałek/`zabki`, Wtorek/`zajaczki`, Środa/`kotki`, Czwartek/`misie`, Piątek/`wiewiorki` |
| `przedszkole_jadlospis_zakres( $poczatek )` | tekst `<h2>`: „22 – 26 czerwca 2026”, „29 września – 3 października 2025”, „29 grudnia 2025 – 2 stycznia 2026” |

- Daty kart: `poczatek` + 0…4 dni, `DateTimeImmutable` w strefie `wp_timezone()`.
- Format „6 października” przez `wp_date( 'j F' )` — rdzeń odmienia miesiąc
  w polskiej lokalizacji (`wp_maybe_decline_date`), bez własnej tablicy miesięcy.
- Nieprawidłowa lub pusta data: karty bez dat, bez `<h2>`.
- Nazwy dni i kolory stoją drugi raz w `edytor.js` (podgląd w przeglądarce,
  bez kroku budowania nie ma jak współdzielić). Komentarz w obu miejscach:
  „zmieniając jedno, popraw drugie” — jak inicjały w `osoba`.

## Edytor — `edytor.js`

Bez JSX, `wp.element.createElement`. Karty mają te same klasy co front;
`style.css` wchodzi do edytora przez `add_editor_style`, więc intendent widzi
to, co rodzic. Siatka reaguje na szerokość płótna.

**Góra bloku:**
- `<h2>` z zakresem dat, przed nim przycisk z ikoną kalendarza „Zmień tydzień” —
  pierwszy element interaktywny bloku.
- Przycisk otwiera `Dropdown` z `DatePicker`: `startOfWeek: 1`,
  `isInvalidDate` odrzuca wszystko poza poniedziałkiem.
- Pusty `poczatek` przy pierwszym renderze (świeżo wstawiony blok) → ustawiany
  na poniedziałek bieżącego tygodnia; sobota i niedziela → następny poniedziałek.

**Każda karta:**
1. Nagłówek: nazwa dnia i data — nieedytowalne.
2. `ToggleControl` „Dzień wolny” na górze treści karty.
3. Trzy `RichText` (`tagName: 'p'`) pod etykietami z ikonami. Placeholder,
   np. „Wpisz śniadanie — alergeny pogrub (Ctrl+B)”. Bez `allowedFormats` —
   pełne formatowanie rdzenia.
4. Dzień wolny włączony → pola ukryte, wyśrodkowany napis „Dzień wolny”;
   przełącznik zostaje, żeby dało się cofnąć.

Panel boczny pusty.

## Front — `render.php`

### Znaczniki

```html
<section class="jadlospis" aria-labelledby="jadlospis-zakres">
  <h2 class="jadlospis__zakres" id="jadlospis-zakres">22 – 26 czerwca 2026</h2>
  <ol class="jadlospis__dni">
    <li class="jadlospis__dzien jadlospis__dzien--zabki" data-data="2026-06-22">
      <header class="jadlospis__naglowek">
        <h3 class="jadlospis__nazwa">Poniedziałek</h3>
        <p class="jadlospis__data"><time datetime="2026-06-22">22 czerwca</time></p>
      </header>
      <div class="jadlospis__posilek jadlospis__posilek--sniadanie">
        <h4 class="jadlospis__etykieta">Śniadanie</h4>
        <p>…</p>
      </div>
      …
    </li>
  </ol>
</section>
```

- Nagłówki: h1 (strona) → h2 (tydzień) → h3 (dzień) → h4 (posiłek).
- Wielkie litery wyłącznie przez `text-transform: uppercase` — w źródle
  „Śniadanie”, żeby czytnik ekranu nie literował.
- Treść pól przez `wp_kses_post()`, opakowana w `get_block_wrapper_attributes()`.
- Dzień wolny: nagłówek bez zmian, w treści `<p class="jadlospis__wolne">Dzień wolny</p>`.
- Pusty posiłek: sekcja pominięta. Brak wszystkich trzech (i nie wolny):
  `<p class="jadlospis__pusty">Jadłospis w przygotowaniu</p>`.

### CSS

- Karta: białe tło, zaokrąglenie, obramowanie `--wp--preset--color--border`
  i cień jak `.card`. Cienka linia między posiłkami. Równa wysokość kart w rzędzie.
- Nagłówek karty: tło `--X-tlo`, nazwa dnia pogrubiona i data w `--X-tekst`.
- Etykiety: `text-transform: uppercase`, `font-weight: 600`, ikona w `::before`
  przez `mask-image: url(assets/img/posilek-*.svg)` i `background-color`
  w jasnym kolorze grupy (`zabki`, `accent`, `zajaczki`). Ikona ozdobna —
  znaczenie niesie tekst. Jedno źródło ikon dla frontu i edytora.
- Siatka:
  - < 700px — 1 kolumna,
  - 700–1199px — `repeat(4, 1fr)`, karta `span 2`, ostatnia nieparzysta
    od kolumny 2 (piątek na środku),
  - ≥ 1200px — `repeat(5, 1fr)`, `span 1`.
- Szerokość: do 1400px, wyśrodkowana, wychodzi poza `.wrap` (1140px).
  **Uwaga:** `100vw` liczy pasek przewijania — sprawdzić brak przewijania
  w poziomie.
- **Pułapki z `.entry__content`:** `ol { padding-left }`, `li + li { margin-top }`,
  `h2`/`h3 { margin-top }` trafią w znaczniki bloku — wyzerować w sekcji jadłospisu.
- Przewijanie: `scroll-margin-top` na karcie pod wysokość `.site-header` (sticky).

### Kontrast (policzony)

| Element | Kolory | Kontrast |
|---|---|---|
| Nazwa dnia / data na tle nagłówka | `--X-tekst` / `--X-tlo` | 4.78 (Wiewiórki) – 5.83 (Zajączki) |
| ŚNIADANIE na białym | `--zabki-tekst` `#2E6B26` | 6.47 |
| OBIAD na białym | `--wiewiorki-tekst` `#96540F` | 5.88 |
| PODWIECZOREK na białym | `--zajaczki-tekst` `#175F80` | 7.04 |
| „Dzień wolny”, „w przygotowaniu” | `--ink-soft` / biały | 6.80 |
| Plakietka „Dziś” | biały / `primary` `#3D2FB5` | 9.24 |

Przy zmianie kolorów w implementacji — policzyć ponownie.

### „Dziś” — `widok.js`

Zwykły JavaScript, bez zależności, ok. 30 linii. Data liczona w przeglądarce
(lokalna), więc działa przy stronie z cache.

- Karta z `data-data` równym dzisiejszej dacie: klasa `jadlospis__dzien--dzis`,
  ramka 3px w `primary`, plakietka „Dziś” w nagłówku, `aria-current="date"`.
- Przewinięcie do karty, gdy **wszystkie** warunki:
  - jedna kolumna (`matchMedia( '(max-width: 699.98px)' )`),
  - wejście zwykłą nawigacją (`performance.getEntriesByType( 'navigation' )[0].type === 'navigate'`),
    nie „wstecz” ani odświeżenie,
  - brak `#kotwicy` w adresie,
  - `scrollY === 0`,
  - karta zaczyna się poniżej pierwszego ekranu, czyli
    `getBoundingClientRect().top > innerHeight` (w poniedziałek nic się nie
    dzieje, tytuł zostaje widoczny).
- Skok natychmiastowy (`behavior: 'auto'`), bez animacji.
- Tydzień, który minął: żadna karta nie pasuje — skrypt nic nie robi.

## Treść strony „Jadłospis”

- Jednorazowo przez `wp eval`: blok budowany przez `serialize_block()` rdzenia
  (escapowanie `<`, `>`, `--` w atrybutach robi rdzeń, nie ręczny JSON).
- `poczatek = 2026-06-22`; komórki z tabeli (śniadanie, obiad, podwieczorek)
  przeniesione bez zmian, z pogrubieniami. Literówek oryginału nie poprawiamy.
- Nowa treść: blok jadłospisu + akapit z notką:

  > Zupy podawane są z natką pietruszki. Jarzynka do zupy zawiera seler.
  > Pieczywo ciemne może zawierać ziarna sezamu, słonecznika. Dzieci między
  > posiłkami otrzymują czystą wodę do picia. Alergeny są napisane grubszą
  > czcionką. Zupy gotowane są na wywarze drobiowym z jarzynami, dwa razy
  > w tygodniu zupa jest na samym wywarze warzywnym, drugie danie bez mięsa.
  > Ograniczony jest cukier do wszystkich napojów.

- Pusty `core/tabs`, tabela i puste akapity usunięte; poprzednia wersja w rewizjach.
- Baza lokalna jest źródłem produkcji — **zrzut `produkcja.sql.gz` do powtórzenia**
  (WDROZENIE.md, krok 4).

## Bezpieczeństwo

- Intendent bez `unfiltered_html` → przy zapisie rdzeń filtruje wartości
  atrybutów bloku przez kses (`filter_block_kses`).
- Front wypisuje pola przez `wp_kses_post()` niezależnie od tego.
- `poczatek` walidowany w PHP (`DateTimeImmutable::createFromFormat( '!Y-m-d', … )`)
  przed użyciem; śmieci → brak dat.
- Do sprawdzenia: pogrubienia przeżywają zapis z konta `intendent`.

## Weryfikacja

Projekt nie ma testów automatycznych — sprawdzenie ręczne:

1. `php -l` na nowych plikach PHP.
2. `wp eval` + `render_block()`: dzień wolny, pusty posiłek, pusty dzień,
   tydzień na przełomie miesięcy i lat, data nieprawidłowa.
3. Front przez `tools/podglad.py` przy 375 / 768 / 1280 / 1440px:
   1 kolumna, 2 kolumny z piątkiem na środku, 5 kolumn; brak przewijania w poziomie.
4. Zoom 200% i A++ z paska dostępności — bez utraty treści, bez przewijania w poziomie.
5. „Dziś”: tymczasowo bieżący tydzień — ramka, plakietka, `aria-current`;
   przewijanie tylko w jednej kolumnie, nie w poniedziałek, nie po „wstecz”.
6. Struktura nagłówków h1 → h2 → h3 → h4.
7. **Edytor — sprawdza użytkownik** (agent nie loguje się na `*.ddev.site`):
   wstawienie bloku z inserteru (kategoria „Przedszkole”), domyślna data,
   tylko poniedziałki w kalendarzu, przełącznik dnia wolnego i powrót danych,
   Ctrl+B, zapis z konta `intendent`, drugi blok niewstawialny.

## Dokumentacja i wersja

- CLAUDE.md, „Czego nie robimy”: drugi wyjątek `przedszkole/jadlospis`
  z uzasadnieniem; próg dotyczy odtąd trzeciego bloku.
- PLAN.md: wpis o bloku. WDROZENIE.md: punkt „Jadłospis” na liście kontrolnej,
  ponowny zrzut.
- `languages/przedszkole.pot` odświeżony.
- `Version:` w `style.css` i `PRZEDSZKOLE_VERSION` razem: 1.0.0 → 1.1.0.

## Poza zakresem

- Obrazek z podpisem dla dnia wolnego (osobna sesja).
- Druk, PDF, przełącznik tygodni, archiwum jadłospisów.
- Wyróżnienie „Dziś” po stronie serwera.
