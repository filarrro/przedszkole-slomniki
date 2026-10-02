# Konwencje projektu

Projekt tworzony w dużej mierze z pomocą agentów AI. Ten plik ma sprawić,
że kolejny agent nie będzie musiał odkrywać wszystkiego od nowa.

## Zasada nadrzędna

To prosta strona jednego przedszkola, nie idealny CMS.
**Jeśli WordPress już coś potrafi — użyj tego, nie pisz własnego.**
Wtyczkę dodaj, gdy jest szybsza i stabilniejsza niż własny kod.
Nie pisz własnego kodu tylko po to, by uniknąć wtyczki.

Przed napisaniem funkcji sprawdź, czy WordPress jej nie ma.

## Język

- Kod, komentarze, commity, dokumentacja: **polski**
- Nazwy funkcji i zmiennych PHP: polskie, z prefiksem `przedszkole_`
- Komentarze w plikach `.css`, `.js`, `.svg` bez polskich znaków — unikamy
  problemów z kodowaniem w narzędziach, które ich nie obsługują

## PHP i WordPress

- Standard WordPress Coding Standards: tabulatory, spacje w nawiasach, Yoda nie jest wymagany
- Każdy plik zaczyna się od `defined( 'ABSPATH' ) || exit;`
- Escapowanie zawsze: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Tłumaczenia przez `__()` / `esc_html_e()` z domeną `przedszkole`
- Bez abstrakcji na wyrost — płaskie funkcje, nie klasy, dopóki nie ma powodu

## CSS

- Wszystko w jednym `style.css` — jedno żądanie zamiast kilku.
  Wyjątek: `assets/css/editor.css` wchodzi wyłącznie w panelu, więc frontu nie obciąża
- Kolory i typografia z `theme.json` przez zmienne `--wp--preset--*`
- Zmienne własne tylko na to, czego `theme.json` nie obsługuje
- Sekcje numerowane komentarzem, żeby dało się nawigować po pliku
- Zero frameworków, zero resetów z zewnątrz
- Blok szerszy niż kolumna treści: w edytorze kontener układu rdzenia wymusza
  na każdym bloku poza `.alignfull` szerokość treści i `margin: auto !important`.
  Taki blok dostaje klasę `alignfull`, a jego siatka — `@container` zamiast
  `@media`, bo w płótnie edytora okno ma inną szerokość niż blok
  (wzór: sekcja 29 `style.css`)

## Czego nie robimy

- Zewnętrznych zapytań (CDN, Google Fonts, biblioteki) — wszystko lokalnie
- jQuery i frameworków JS
- Kroku budowania (npm, webpack, JSX) — kod bloków to zwykły JavaScript
  z `wp.element.createElement`. Cena: plik `*.asset.php` z zależnościami
  skryptu piszemy ręcznie, bo nie generuje go `@wordpress/scripts`
- Page builderów
- Własnego systemu logowania
- Własnych bloków Gutenberga bez wyraźnej potrzeby — zwykle wystarczają
  wzorce z `patterns/` i warianty stylów (`register_block_style`).
  Dwa wyjątki, oba z tego samego powodu — układ siedział w treści, więc
  każda poprawka wyglądu szła przez wszystkie kopie, a jedno nieostrożne
  kliknięcie rozsypywało całość:
  - `przedszkole/osoba` (`blocks/osoba/`) — kafelek kadry powtarzany
    kilkanaście razy na jednej stronie,
  - `przedszkole/jadlospis` (`blocks/jadlospis/`) — tydzień jako pięć kart,
    przepisywany co tydzień; wcześniej w zakładkach, potem w tabeli.

  Próg dla trzeciego bloku jest ten sam: układ powtarzany wielokrotnie
  i psujący się w rękach pracownika, nie „byłoby wygodniej".
- Edycji plików w `wp/` — to nie nasz kod

## Role i uprawnienia

Natywne role WP: Editor (dyrekcja), Author (konta grupowe nauczycieli).
Dwie własne role w `theme/przedszkole/inc/role.php`, obie w jednym kształcie
„konto specjalisty = jedna strona”:

- `intendent` — wyłącznie strona „Jadłospis”,
- `pedagog` — strona „Kącik pedagoga” plus wpisy wymuszane do kategorii
  `pedagog` (rdzeń nie umie ograniczyć autora do jednej kategorii, więc
  przypisanie nadpisujemy przy zapisie).

Nie zastępuj ich wtyczką od uprawnień bez powodu: to dwie reguły na jednej
tablicy, a wtyczka to tabele, ekran ustawień i konfiguracja poza repozytorium,
którą przy wdrożeniu trzeba odtworzyć z pamięci. **Trzeci taki przypadek —
wtedy przelicz na nowo.** Nowa rola tego samego kształtu to pozycja w
`przedszkole_role_wlasne()` i podbicie `PRZEDSZKOLE_WERSJA_ROL` (definicje ról
siedzą w bazie, `add_role()` na istniejącej roli nic nie robi).

**Pułapka rdzenia:** rola z `edit_pages` bez `edit_posts` dostaje 403 na liście
stron, jeśli w podmenu „Strony” zostanie jedna pozycja. Uzasadnienie i naprawa
w PLAN.md, Etap 6.

## Dostępność

Strona placówki publicznej. Ustawa o dostępności cyfrowej wymaga WCAG 2.1 AA.
- Kontrast tekstu minimum 4.5 — **licz go, nie zgaduj**
- Widoczny focus, skip link, `aria-expanded` na przełącznikach
- `prefers-reduced-motion`
- Semantyczne znaczniki, jedno `<h1>` na stronę

## Praca z wp-cli

Docroot to `wp/`, a kontener startuje w katalogu projektu:

```bash
ddev exec wp --path=wp <komenda>
```

**Pułapki, na które już wpadliśmy:**

1. `ddev exec` czyta stdin i **zjada wejście pętli** `while read`.
   W pętlach dodawaj `</dev/null`:
   ```bash
   for x in a b c; do ddev exec wp --path=wp ... </dev/null; done
   ```

2. Nie używaj zmiennej `HOME` w skryptach — nadpisuje katalog domowy,
   a `ddev` tworzy wtedy śmieciowy katalog z własną konfiguracją.

3. Ścieżki w `wp media import` są względne wobec katalogu projektu
   w kontenerze (`/var/www/html`), nie wobec docroota.

4. Pod PHP 8.5 wp-cli zalewa stderr ostrzeżeniami `Deprecated: Using null as an
   array offset` z własnej biblioteki rysującej tabelki. Dodawaj `--format=csv`
   — inny renderer, czysty wynik:
   ```bash
   ddev exec wp --path=wp post list --fields=ID,post_title --format=csv
   ```
   Nie tłum tego przez `2>/dev/null` w skryptach z `set -e` — razem z szumem
   znikają prawdziwe błędy i skrypt pada bez śladu. `wp eval-file` nie ma
   `--format`, więc jego wynik dostaje ten sam szum `Deprecated` — filtruj go
   przez `| grep -v '^Deprecated:'`, nigdy przez `2>/dev/null`.

5. Slug menu wylicza WP z nazwy, a nie ty. „Menu w stopce" → `menu-w-stopce`.
   Sprawdź `wp menu list`, zanim odwołasz się do sluga w skrypcie.

6. **Wzorce bloków są cache'owane pod wersją motywu z nagłówka `style.css`.**
   Nowy plik w `patterns/` nie pojawi się w edytorze, dopóki nie podbijesz
   `Version:` w `style.css`. Wersja w nagłówku i stała `PRZEDSZKOLE_VERSION`
   w `functions.php` muszą iść razem — rozjazd kosztował już jedno śledztwo.

7. `wp/wp-config.php` nie ma znacznika `#ddev-generated` — zdjęliśmy go, żeby
   ddev nie kasował `WP_DEBUG` przy każdym starcie. Nie przywracaj. Dane dostępowe
   do bazy nadal przychodzą z `wp-config-ddev.php`, którym ddev zarządza.

8. `wp post list --name=<slug> --post_status=any` **nie widzi szkiców.**
   Ten sam slug z `--post_status=draft` znajduje wpis bez problemu. Skrypt
   szukający strony po slugu wyliczy więc statusy jawnie:
   ```bash
   ddev exec wp --path=wp post list --post_type=page --name=pedagog \
     --post_status=publish,draft,pending,private --field=ID --format=csv
   ```
   Bez tego skrypt idempotentny przestaje być idempotentny — nie znajduje
   szkicu i zakłada go drugi raz.

9. **Pozycja menu prowadząca do szkicu nie znika z frontu.** Rdzeń ukrywa
   pozycje wskazujące na kosz, ale szkic zostaje i daje odwiedzającemu 404.
   Stronę odkładaną „na potem" trzymaj poza menu, nie licz na WordPressa.

10. **`wp_update_post()` odcina ukośniki.** JSON atrybutów bloku ma ich pełno
    (`\u003cstrong\u003e`), więc treść z blokami zapisywana ze skryptu idzie
    przez `wp_slash()`. Bez tego pogrubienia zamieniają się w `u003cstrongu003e`:
    ```php
    wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $tresc ) ) );
    ```

## Podgląd wizualny

Panel podglądu w aplikacji blokuje pliki podrzędne z `*.ddev.site`
(`ERR_BLOCKED_BY_CLIENT`) — strona otwiera się bez stylów.

```bash
python3 tools/podglad.py / podglad.html    # sklejona strona do zrzutów
```

Gdy porty 80/443 zajmuje inny proces, ddev przenosi router (np. na 33001) —
adres i certyfikat trzeba wtedy podać jawnie:

```bash
PODGLAD_BAZA=https://przedszkole.ddev.site:33001 SSL_CERT_FILE="$(mkcert -CAROOT)/rootCA.pem" python3 tools/podglad.py /dla-rodzicow/jadlospis/ j.podglad.html
```

Do zwykłej pracy wystarczy http://przedszkole.ddev.site w przeglądarce.

## Bezpieczeństwo

- Zrzuty baz (`*.sql.gz`) **nigdy** do repo — zawierają hashe haseł i adresy e-mail
- `wp/wp-config.php` poza repo — dane dostępowe do bazy
- Konto `dev` / `dev12345` jest wyłącznie lokalne, nie przenosimy go na produkcję

## Przed wdrożeniem

W lokalnej instalacji jest treść testowa do usunięcia — patrz PLAN.md, Etap 9.
