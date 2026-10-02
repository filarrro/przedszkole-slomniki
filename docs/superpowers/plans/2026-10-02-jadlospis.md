# Blok „Jadłospis” — plan wdrożenia

> **Dla agentów:** WYMAGANY SUB-SKILL: `superpowers:subagent-driven-development`
> (zalecany) albo `superpowers:executing-plans`. Kroki mają składnię checkboxów
> (`- [ ]`) do odhaczania.

**Cel:** strona „Jadłospis” pokazuje tydzień jako pięć kart (poniedziałek–piątek)
w stałym układzie, a intendent wpisuje tylko datę poniedziałku i treść posiłków.

**Podejście:** jeden blok dynamiczny `przedszkole/jadlospis`. Dane (data
poniedziałku i pięć dni) siedzą w atrybutach, cały front rysuje `render.php`,
edytor powtarza te same klasy. Dzisiejszy dzień wyróżnia mały skrypt w przeglądarce.

**Stack:** WordPress 7.1, PHP 8.5, motyw `theme/przedszkole`, wp-cli przez
`ddev exec wp --path=wp`, bez kroku budowania (`wp.element.createElement`).
`node` służy wyłącznie do uruchomienia testu edytora — nie buduje niczego.

**Spec:** [2026-10-02-jadlospis-design.md](../specs/2026-10-02-jadlospis-design.md)

---

## Jak weryfikujemy — przeczytaj przed pierwszym zadaniem

**W projekcie nie ma PHPUnit, composera ani npm.** Pętla TDD zostaje, tylko
narzędziami są dwa skrypty bez zależności, które powstają w tym planie:

- `tools/test_jadlospis.php` — funkcje pomocnicze i front, przez `wp eval-file`:
  ```bash
  ddev exec wp --path=wp eval-file tools/test_jadlospis.php
  ```
- `tools/test_jadlospis_edytor.js` — widok edytora z podstawionym `window.wp`:
  ```bash
  node tools/test_jadlospis_edytor.js
  ```

Każde zadanie ma krok „sprawdź, że **nie** działa” przed implementacją. Jeśli
przechodzi, zanim cokolwiek napisałeś — zatrzymaj się.

**Adres strony.** Router ddev stoi dziś na portach 33000/33001 (80 i 443 zajmuje
inny proces), więc `http://przedszkole.ddev.site` prowadzi do cudzego serwera.
Adres zawsze bierz z WordPressa, nie wpisuj z pamięci:

```bash
ddev exec wp --path=wp eval 'echo get_permalink( przedszkole_id_strony( "jadlospis" ) );' </dev/null
```

Wynik (dziś `https://przedszkole.ddev.site:33001/dla-rodzicow/jadlospis/`)
w poleceniach niżej oznaczony jako `$JADLOSPIS`. Każde wywołanie Basha to nowa
powłoka — ustawiaj zmienną w tym samym poleceniu:

```bash
JADLOSPIS=$(ddev exec wp --path=wp eval 'echo get_permalink( przedszkole_id_strony( "jadlospis" ) );' </dev/null | tr -d '\r')
```

**Pułapki wp-cli** (CLAUDE.md): `--format=csv` przy tabelkach, `</dev/null`
w pętlach, ścieżki w `eval-file` względne wobec `/var/www/html` (katalog projektu).

**Edytora agent nie sprawdzi po zalogowaniu** — nie loguje się na `*.ddev.site`.
Test w `node` łapie logikę i literówki; kliknięcia w panelu sprawdza użytkownik
według listy w „Odbiorze całości”.

---

## Korekta wobec specu — przeczytaj, zanim zaczniesz

Przy zbieraniu szczegółów z kodu rdzenia wyszło sześć rzeczy, które zmieniają
wykonanie, ale nie zachowanie widziane przez rodzica i intendenta:

1. **Siatka na `@container`, nie `@media`.** W edytorze kontener układu rdzenia
   (`is-layout-constrained`, motyw ma `useRootPaddingAwareAlignments`) wymusza
   na każdym dziecku poza `.alignfull` `max-width: 760px` i
   `margin-inline: auto !important` (`wp-includes/block-supports/layout.php`).
   Blok dostaje więc klasę `alignfull`, a siatka liczy się od szerokości
   **bloku** — w płótnie edytora okno ma inną szerokość niż blok. Progi
   przeliczone na szerokość bloku: 2 kolumny od `41.25rem` (660px = okno 700px),
   5 kolumn od `72.5rem` (1160px = okno 1200px). Progi w `rem` rosną przy A+/A++,
   więc powiększony tekst dostaje mniej, ale szerszych kolumn.
2. **Nazwy dni, kolory, posiłki i miesiące idą do edytora z PHP**
   (`window.przedszkoleJadlospis`). Pakiet `@wordpress/date` zna tylko mianownik
   („czerwiec”), więc „22 czerwca” i tak wymagało odmiany z PHP — przy okazji
   reszta też ma jedno źródło. W JS zostaje tylko reguła zakresu dat, a jej
   zgodność z PHP pilnuje test (te same trzy przypadki w obu testach).
3. **Plakietka „Dziś” stoi w znacznikach z atrybutem `hidden`**, skrypt tylko
   ją odsłania — front nie potrzebuje `wp-i18n` do jednego słowa.
4. **Przewijanie:** `html` ma już `scroll-padding-top` pod przypięty nagłówek
   (sekcja 2 `style.css`), więc `scroll-margin-top` jest zbędny. Skok przez
   `behavior: 'instant'`, nie `'auto'` — `html` ma `scroll-behavior: smooth`,
   a `'auto'` poszłoby za nim. „Jedna kolumna” odczytana z policzonej siatki
   (`gridTemplateColumns`), nie z `matchMedia` — z tego samego powodu co punkt 1.
5. **Rejestracja we własnym `inc/blok-jadlospis.php`**, nie dopisek
   w `przedszkole_rejestruj_bloki()` — pliki zmieniane razem leżą razem.
   Kategoria „Przedszkole” zostaje w `inc/blok-osoba.php`.
6. **Wysoki kontrast (sekcja 28 `style.css`)** — spec go pominął. Blok bierze
   wszystkie kolory ze zmiennych, więc tryb przestawia je sam; sprawdzenie
   dopisane do Zadania 4.

Dodatkowo: treść karty w opakowaniu `.jadlospis__tresc` (kolumna flex, żeby
„Dzień wolny” stanął na środku wolnego miejsca) i `tools/podglad.py` czyta adres
z `PODGLAD_BAZA` — bez tego nie działa przy zajętym porcie 80.

---

## Struktura plików

```
theme/przedszkole/
├── blocks/jadlospis/
│   ├── block.json          metadane, atrybuty, skrypty
│   ├── render.php          cały front
│   ├── edytor.js           widok w edytorze
│   ├── edytor.asset.php    zależności edytora (ręcznie)
│   ├── widok.js            „Dziś” na froncie
│   └── widok.asset.php     zależności widoku (ręcznie, pusta lista)
├── inc/blok-jadlospis.php  rejestracja, dni, posiłki, daty, dane edytora
├── assets/img/posilek-{sniadanie,obiad,podwieczorek}.svg
├── assets/css/editor.css   + pasek kalendarza i przełącznik (tylko panel)
├── style.css               + sekcja 29. Jadlospis; Version
└── functions.php           + require_once; PRZEDSZKOLE_VERSION
tools/
├── test_jadlospis.php          test PHP (wp eval-file)
├── test_jadlospis_edytor.js    test edytora (node)
├── podglad.py                  + PODGLAD_BAZA
└── README.md                   + opis trzech narzędzi
```

---

## Zadanie 1: Funkcje pomocnicze i test PHP

**Pliki:**
- Nowy: `tools/test_jadlospis.php`
- Nowy: `theme/przedszkole/inc/blok-jadlospis.php`
- Zmiana: `theme/przedszkole/functions.php:18` (pod `require_once … blok-osoba.php`)

- [ ] **Krok 1: Utwórz `tools/test_jadlospis.php`**

```php
<?php
/**
 * Sprawdzenie bloku „Jadłospis” — funkcje pomocnicze i front.
 *
 * Projekt nie ma PHPUnit. To zwykły skrypt dla `wp eval-file`: wypisuje
 * OK albo BŁĄD przy każdym sprawdzeniu i kończy się błędem wp-cli (kod 1),
 * jeśli choć jedno nie przeszło.
 *
 *   ddev exec wp --path=wp eval-file tools/test_jadlospis.php
 *
 * Front sprawdzany przez `render_block()` na sztucznych atrybutach — bez
 * dotykania treści strony „Jadłospis”. Zakresy dat są te same co
 * w `tools/test_jadlospis_edytor.js`: reguła stoi w PHP i w JS, oba testy
 * pilnują, żeby mówiły to samo.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'przedszkole_jadlospis_poczatek' ) ) {
	WP_CLI::error( 'Brak funkcji pomocniczych — czy inc/blok-jadlospis.php jest dołączony w functions.php?' );
}

$bledy = 0;

$sprawdz = function ( $opis, $warunek ) use ( &$bledy ) {
	if ( $warunek ) {
		WP_CLI::log( 'OK    ' . $opis );
		return;
	}

	++$bledy;
	WP_CLI::log( 'BŁĄD  ' . $opis );
};

$zakres = function ( $tekst ) {
	$poczatek = przedszkole_jadlospis_poczatek( $tekst );
	return $poczatek ? przedszkole_jadlospis_zakres( $poczatek ) : null;
};

$renderuj = function ( $atrybuty ) {
	return render_block(
		array(
			'blockName'    => 'przedszkole/jadlospis',
			'attrs'        => $atrybuty,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
};

// --- Daty ---

$sprawdz( 'zakres w jednym miesiącu', '22 – 26 czerwca 2026' === $zakres( '2026-06-22' ) );
$sprawdz( 'zakres przez granicę miesiąca', '29 września – 3 października 2025' === $zakres( '2025-09-29' ) );
$sprawdz( 'zakres przez granicę roku', '29 grudnia 2025 – 2 stycznia 2026' === $zakres( '2025-12-29' ) );
$sprawdz( 'przepełniona data odrzucona', null === przedszkole_jadlospis_poczatek( '2026-02-31' ) );
$sprawdz( 'śmieci odrzucone', null === przedszkole_jadlospis_poczatek( 'jutro' ) );
$sprawdz( 'pusta data odrzucona', null === przedszkole_jadlospis_poczatek( '' ) );
$sprawdz( 'nie-tekst odrzucony', null === przedszkole_jadlospis_poczatek( array() ) );
$sprawdz( 'dzień inny niż poniedziałek odrzucony', null === przedszkole_jadlospis_poczatek( '2026-06-24' ) );

// --- Dni i posiłki ---

$dni = przedszkole_jadlospis_dni();
$sprawdz( 'pięć dni', 5 === count( $dni ) );
$sprawdz( 'poniedziałek w kolorze Żabek', 'zabki' === $dni[0]['grupa'] );
$sprawdz( 'piątek w kolorze Wiewiórek', 'wiewiorki' === $dni[4]['grupa'] );
$sprawdz( 'trzy posiłki w kolejności', array( 'sniadanie', 'obiad', 'podwieczorek' ) === array_keys( przedszkole_jadlospis_posilki() ) );

$dzien = przedszkole_jadlospis_dzien(
	array(
		'sniadanie'    => '<strong>mleko</strong>',
		'obiad'        => '<br>',
		'podwieczorek' => '&nbsp; ',
	)
);
$sprawdz( 'dzień uzupełniony o „wolny”', false === $dzien['wolny'] );
$sprawdz( 'pusty HTML nie liczy się jako posiłek', array( 'sniadanie' ) === array_keys( przedszkole_jadlospis_wpisane( $dzien ) ) );
$sprawdz( 'śmieci zamiast dnia dają pusty dzień', array() === przedszkole_jadlospis_wpisane( przedszkole_jadlospis_dzien( 'x' ) ) );

// --- Rejestracja ---

$sprawdz( 'blok zarejestrowany', WP_Block_Type_Registry::get_instance()->is_registered( 'przedszkole/jadlospis' ) );

// --- Front ---

$html = $renderuj(
	array(
		'poczatek' => '2026-06-22',
		'dni'      => array(
			array( 'sniadanie' => 'Kakao /<strong>mleko</strong>/<script>alert(1)</script>' ),
			array(
				'wolny' => true,
				'obiad' => 'UKRYTY OBIAD',
			),
		),
	)
);

$sprawdz( 'pięć kart', 5 === substr_count( $html, '<li class="jadlospis__dzien ' ) );
$sprawdz( 'klasa alignfull na bloku', false !== strpos( $html, 'alignfull' ) );
$sprawdz( 'nagłówek z zakresem', false !== strpos( $html, '>22 – 26 czerwca 2026</h2>' ) );
$sprawdz( 'sekcja opisana nagłówkiem', 1 === preg_match( '/aria-labelledby="(jadlospis-zakres-\d+)".*id="\1"/s', $html ) );
$sprawdz( 'data pierwszej karty', false !== strpos( $html, 'data-data="2026-06-22"' ) );
$sprawdz( 'data ostatniej karty', false !== strpos( $html, 'data-data="2026-06-26"' ) );
$sprawdz( 'data w dopełniaczu', false !== strpos( $html, '>22 czerwca</time>' ) );
$sprawdz( 'kolor poniedziałku', false !== strpos( $html, 'jadlospis__dzien--zabki' ) );
$sprawdz( 'pogrubienie zostaje', false !== strpos( $html, '<strong>mleko</strong>' ) );
$sprawdz( 'skrypt wycięty', false === strpos( $html, '<script' ) );
$sprawdz( 'pusty obiad bez sekcji', false === strpos( $html, 'jadlospis__posilek--obiad' ) );
$sprawdz( 'dzień wolny', 1 === substr_count( $html, 'class="jadlospis__wolne"' ) );
$sprawdz( 'treść dnia wolnego ukryta', false === strpos( $html, 'UKRYTY OBIAD' ) );
$sprawdz( 'trzy dni w przygotowaniu', 3 === substr_count( $html, 'class="jadlospis__pusty"' ) );
$sprawdz( 'plakietki „Dziś” ukryte', 5 === substr_count( $html, 'class="jadlospis__dzis" hidden' ) );

$bez_daty = $renderuj( array( 'poczatek' => 'zle' ) );
$sprawdz( 'bez daty: pięć kart', 5 === substr_count( $bez_daty, '<li class="jadlospis__dzien ' ) );
$sprawdz( 'bez daty: bez nagłówka', false === strpos( $bez_daty, '<h2' ) );
$sprawdz( 'bez daty: bez aria-labelledby', false === strpos( $bez_daty, 'aria-labelledby' ) );
$sprawdz( 'bez daty: bez data-data', false === strpos( $bez_daty, 'data-data' ) );
$sprawdz( 'bez daty: bez plakietek', false === strpos( $bez_daty, 'jadlospis__dzis' ) );

// --- Skrypty ---

if ( $bledy ) {
	WP_CLI::error( $bledy . ' sprawdzeń nie przeszło.' );
}

WP_CLI::success( 'Wszystko przeszło.' );
```

- [ ] **Krok 2: Uruchom — ma paść na braku funkcji**

```bash
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
```

Oczekiwane: `Error: Brak funkcji pomocniczych — czy inc/blok-jadlospis.php jest dołączony w functions.php?`

- [ ] **Krok 3: Utwórz `theme/przedszkole/inc/blok-jadlospis.php`**

```php
<?php
/**
 * Blok „Jadłospis” — tydzień posiłków jako pięć kart.
 *
 * Drugi własny blok motywu, po `przedszkole/osoba`, z tego samego powodu:
 * układ powtarzany co tydzień psuł się w rękach pracownika. Intendent
 * przepisywał tydzień najpierw do zakładek, potem do tabeli — za każdym
 * razem razem z układem. Blok trzyma w treści strony same dane (data
 * poniedziałku i pięć dni), a wygląd rysuje `blocks/jadlospis/render.php`.
 *
 * Tutaj leży to, co wspólne dla frontu i edytora: nazwy dni z kolorami
 * grup, nazwy posiłków i daty. Edytor dostaje je z PHP gotowe, więc nie
 * stoją drugi raz w JavaScripcie.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dni tygodnia z kolorami grup.
 *
 * Kolor dnia to para `--{grupa}-tlo` / `--{grupa}-tekst` ze `style.css` —
 * kontrast policzony tam raz dla wszystkich komponentów. Jeżyki odpadają:
 * dni jest pięć, grup sześć, a czerwień czyta się jak ostrzeżenie.
 *
 * Kolejność jest znacząca: indeks to przesunięcie od poniedziałku.
 *
 * @return array<int, array{nazwa: string, grupa: string}>
 */
function przedszkole_jadlospis_dni() {
	return array(
		array(
			'nazwa' => __( 'Poniedziałek', 'przedszkole' ),
			'grupa' => 'zabki',
		),
		array(
			'nazwa' => __( 'Wtorek', 'przedszkole' ),
			'grupa' => 'zajaczki',
		),
		array(
			'nazwa' => __( 'Środa', 'przedszkole' ),
			'grupa' => 'kotki',
		),
		array(
			'nazwa' => __( 'Czwartek', 'przedszkole' ),
			'grupa' => 'misie',
		),
		array(
			'nazwa' => __( 'Piątek', 'przedszkole' ),
			'grupa' => 'wiewiorki',
		),
	);
}

/**
 * Posiłki w kolejności na karcie.
 *
 * Klucz to nazwa pola w atrybucie `dni` i część klasy CSS
 * (`jadlospis__posilek--{klucz}`) — zmiana klucza gubi zapisane dane.
 *
 * @return array<string, string> Klucz => etykieta.
 */
function przedszkole_jadlospis_posilki() {
	return array(
		'sniadanie'    => __( 'Śniadanie', 'przedszkole' ),
		'obiad'        => __( 'Obiad', 'przedszkole' ),
		'podwieczorek' => __( 'Podwieczorek', 'przedszkole' ),
	);
}

/**
 * Data poniedziałku z atrybutu bloku.
 *
 * `createFromFormat()` przepuszcza „2026-02-31” jako 3 marca, więc wynik
 * porównujemy z wejściem — przepełniona data odpada razem ze śmieciami.
 * Strefa czasowa strony, nie serwera: inaczej `wp_date()` przesunęłoby
 * północ na poprzedni dzień.
 * Dzień inny niż poniedziałek też odpada — karty podpisałyby środę „Poniedziałek”.
 * Kalendarz w edytorze przepuszcza tylko poniedziałki, ale atrybut można
 * poprawić ręcznie w edytorze kodu.
 *
 * @param mixed $tekst Data `RRRR-MM-DD`.
 * @return DateTimeImmutable|null
 */
function przedszkole_jadlospis_poczatek( $tekst ) {
	if ( ! is_string( $tekst ) ) {
		return null;
	}

	$data = DateTimeImmutable::createFromFormat( '!Y-m-d', $tekst, wp_timezone() );

	if ( ! $data || $data->format( 'Y-m-d' ) !== $tekst || '1' !== $data->format( 'N' ) ) {
		return null;
	}

	return $data;
}

/**
 * Zakres tygodnia do nagłówka: „22 – 26 czerwca 2026”.
 *
 * Miesiąc i rok stoją raz, chyba że tydzień przechodzi przez granicę:
 * „29 września – 3 października 2025”, „29 grudnia 2025 – 2 stycznia 2026”.
 * Odmianę miesiąca robi rdzeń — `wp_date()` z formatem „j F” zwraca
 * w polskiej lokalizacji dopełniacz (`wp_maybe_decline_date()`).
 *
 * Ta sama reguła stoi drugi raz w `blocks/jadlospis/edytor.js` — edytor
 * pokazuje zakres od razu po wyborze daty. Zmieniając jedno, popraw drugie;
 * oba testy (`tools/test_jadlospis*`) sprawdzają te same przypadki.
 *
 * @param DateTimeImmutable $poczatek Poniedziałek.
 * @return string
 */
function przedszkole_jadlospis_zakres( DateTimeImmutable $poczatek ) {
	$koniec = $poczatek->modify( '+4 days' );

	if ( $poczatek->format( 'Y' ) !== $koniec->format( 'Y' ) ) {
		$format_od = 'j F Y';
	} elseif ( $poczatek->format( 'n' ) !== $koniec->format( 'n' ) ) {
		$format_od = 'j F';
	} else {
		$format_od = 'j';
	}

	return wp_date( $format_od, $poczatek->getTimestamp() ) . ' – ' . wp_date( 'j F Y', $koniec->getTimestamp() );
}

/**
 * Jeden dzień z atrybutu `dni`, uzupełniony do pełnego kształtu.
 *
 * Atrybut może przyjść niepełny: przykład w `block.json` ma jeden dzień,
 * a ręcznie poprawiony komentarz bloku — cokolwiek.
 *
 * @param mixed $surowy Element tablicy `dni`.
 * @return array{wolny: bool, sniadanie: string, obiad: string, podwieczorek: string}
 */
function przedszkole_jadlospis_dzien( $surowy ) {
	$surowy = is_array( $surowy ) ? $surowy : array();
	$dzien  = array( 'wolny' => ! empty( $surowy['wolny'] ) );

	foreach ( array_keys( przedszkole_jadlospis_posilki() ) as $klucz ) {
		$dzien[ $klucz ] = is_string( $surowy[ $klucz ] ?? null ) ? $surowy[ $klucz ] : '';
	}

	return $dzien;
}

/**
 * Posiłki, które coś zawierają — puste sekcje karta pomija.
 *
 * „Puste” liczymy po tekście, nie po HTML-u: pole `RichText` po skasowaniu
 * treści potrafi zostawić `<br>` albo twardą spację, a to nie jest jadłospis.
 *
 * @param array $dzien Dzień z {@see przedszkole_jadlospis_dzien()}.
 * @return array<string, string> Klucz => HTML posiłku.
 */
function przedszkole_jadlospis_wpisane( array $dzien ) {
	$wpisane = array();

	foreach ( array_keys( przedszkole_jadlospis_posilki() ) as $klucz ) {
		$tekst = html_entity_decode( wp_strip_all_tags( $dzien[ $klucz ] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( '' !== preg_replace( '/[\s\x{00A0}]+/u', '', $tekst ) ) {
			$wpisane[ $klucz ] = $dzien[ $klucz ];
		}
	}

	return $wpisane;
}
```

- [ ] **Krok 4: Dołącz plik w `functions.php`**

Pod linią `require_once get_theme_file_path( 'inc/blok-osoba.php' );` dopisz:

```php
require_once get_theme_file_path( 'inc/blok-jadlospis.php' );
```

- [ ] **Krok 5: Uruchom test — daty i dni przechodzą, blok jeszcze nie**

```bash
ddev exec php -l theme/przedszkole/inc/blok-jadlospis.php
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
```

Oczekiwane: `No syntax errors detected`; potem `OK` przy wszystkich
sprawdzeniach z sekcji „Daty” i „Dni i posiłki”, `BŁĄD` co najmniej przy
„blok zarejestrowany” i „pięć kart”, na końcu `Error: … sprawdzeń nie przeszło.`

Jeśli któryś zakres dat daje `BŁĄD` — sprawdź `wp option get WPLANG`
(ma być `pl_PL`); bez polskiej lokalizacji rdzeń nie odmienia miesięcy.

- [ ] **Krok 6: Commit**

```bash
git add tools/test_jadlospis.php theme/przedszkole/inc/blok-jadlospis.php theme/przedszkole/functions.php
git commit -m "feat: dni, posilki i zakres dat dla bloku jadlospisu" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Zadanie 2: Rejestracja bloku i front

**Pliki:**
- Nowy: `theme/przedszkole/blocks/jadlospis/block.json`
- Nowy: `theme/przedszkole/blocks/jadlospis/render.php`
- Zmiana: `theme/przedszkole/inc/blok-jadlospis.php` (rejestracja, na początku po `defined`)

- [ ] **Krok 1: Utwórz `blocks/jadlospis/block.json`**

Skrypty (`editorScript`, `viewScript`) dojdą w Zadaniach 5 i 6 — wpisane teraz
wskazywałyby na nieistniejące pliki, a rdzeń zgłosiłby to przy rejestracji.

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "przedszkole/jadlospis",
	"title": "Jadłospis",
	"category": "przedszkole",
	"icon": "food",
	"description": "Jadłospis na tydzień: pięć kart od poniedziałku do piątku, z dniem wolnym i wyróżnieniem dzisiejszego dnia.",
	"keywords": [ "jadłospis", "menu", "posiłki", "obiad" ],
	"textdomain": "przedszkole",
	"attributes": {
		"poczatek": { "type": "string", "default": "" },
		"dni": {
			"type": "array",
			"default": [
				{ "wolny": false, "sniadanie": "", "obiad": "", "podwieczorek": "" },
				{ "wolny": false, "sniadanie": "", "obiad": "", "podwieczorek": "" },
				{ "wolny": false, "sniadanie": "", "obiad": "", "podwieczorek": "" },
				{ "wolny": false, "sniadanie": "", "obiad": "", "podwieczorek": "" },
				{ "wolny": false, "sniadanie": "", "obiad": "", "podwieczorek": "" }
			]
		}
	},
	"supports": {
		"html": false,
		"multiple": false,
		"reusable": false
	},
	"example": {
		"attributes": {
			"poczatek": "2026-06-22",
			"dni": [
				{
					"wolny": false,
					"sniadanie": "Pieczywo pszenno-żytnie z masłem /<strong>mąka pszenna, masło</strong>/, herbata z <strong>cytryną</strong>",
					"obiad": "Zupa brokułowa z ryżem /<strong>śmietana</strong>/, kompot wieloowocowy",
					"podwieczorek": "Jogurt do picia, wafle /<strong>mąka pszenna, mleko</strong>/"
				}
			]
		}
	},
	"render": "file:./render.php"
}
```

- [ ] **Krok 2: Utwórz `blocks/jadlospis/render.php`**

```php
<?php
/**
 * Front bloku „Jadłospis”.
 *
 * Cały układ tygodnia powstaje tutaj, a w treści strony zostają same dane
 * w atrybutach bloku. Poprawka wyglądu to jeden plik, a intendent nie ma
 * czego rozsypać w edytorze.
 *
 * Klasa `alignfull` nie jest wyborem wyrównania: w edytorze kontener układu
 * rdzenia wymusza na każdym innym bloku szerokość kolumny treści
 * i `margin: auto !important`. Front wychodzi poza kolumnę własną regułą
 * w `style.css` (sekcja 29), edytor — dzięki tej klasie.
 *
 * Dostępne zmienne: $attributes, $content (pusty — blok nie ma dzieci), $block.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

$poczatek   = przedszkole_jadlospis_poczatek( $attributes['poczatek'] ?? '' );
$dni        = is_array( $attributes['dni'] ?? null ) ? $attributes['dni'] : array();
$posilki    = przedszkole_jadlospis_posilki();
$id_zakresu = wp_unique_id( 'jadlospis-zakres-' );

$dodatkowe = array( 'class' => 'jadlospis alignfull' );

// Nazwa sekcji dla czytnika ekranu to zakres tygodnia — bez daty nie ma
// nagłówka, więc nie ma też czego wskazać.
if ( $poczatek ) {
	$dodatkowe['aria-labelledby'] = $id_zakresu;
}

$atrybuty = get_block_wrapper_attributes( $dodatkowe );
?>
<section <?php echo $atrybuty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — get_block_wrapper_attributes escapuje samo. ?>>
	<?php if ( $poczatek ) : ?>
		<h2 class="jadlospis__zakres" id="<?php echo esc_attr( $id_zakresu ); ?>"><?php echo esc_html( przedszkole_jadlospis_zakres( $poczatek ) ); ?></h2>
	<?php endif; ?>
	<ol class="jadlospis__dni">
		<?php
		foreach ( przedszkole_jadlospis_dni() as $i => $opis ) :
			$dzien   = przedszkole_jadlospis_dzien( $dni[ $i ] ?? array() );
			$wpisane = przedszkole_jadlospis_wpisane( $dzien );
			$data    = $poczatek ? $poczatek->modify( '+' . $i . ' days' ) : null;
			?>
			<li class="jadlospis__dzien jadlospis__dzien--<?php echo esc_attr( $opis['grupa'] ); ?>"<?php echo $data ? ' data-data="' . esc_attr( $data->format( 'Y-m-d' ) ) . '"' : ''; ?>>
				<header class="jadlospis__naglowek">
					<h3 class="jadlospis__nazwa"><?php echo esc_html( $opis['nazwa'] ); ?></h3>
					<?php if ( $data ) : ?>
						<p class="jadlospis__data"><time datetime="<?php echo esc_attr( $data->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( 'j F', $data->getTimestamp() ) ); ?></time></p>
						<?php
						/*
						 * Plakietka stoi w znacznikach od razu, ukryta. Który dzień jest
						 * „dziś”, wie dopiero przeglądarka (strona może wyjść z cache),
						 * więc `widok.js` tylko zdejmuje `hidden` — bez tłumaczeń w JS.
						 */
						?>
						<p class="jadlospis__dzis" hidden><?php esc_html_e( 'Dziś', 'przedszkole' ); ?></p>
					<?php endif; ?>
				</header>
				<div class="jadlospis__tresc">
					<?php if ( $dzien['wolny'] ) : ?>
						<p class="jadlospis__wolne"><?php esc_html_e( 'Dzień wolny', 'przedszkole' ); ?></p>
					<?php elseif ( ! $wpisane ) : ?>
						<p class="jadlospis__pusty"><?php esc_html_e( 'Jadłospis w przygotowaniu', 'przedszkole' ); ?></p>
					<?php else : ?>
						<?php foreach ( $wpisane as $klucz => $tresc ) : ?>
							<div class="jadlospis__posilek jadlospis__posilek--<?php echo esc_attr( $klucz ); ?>">
								<h4 class="jadlospis__etykieta"><?php echo esc_html( $posilki[ $klucz ] ); ?></h4>
								<p><?php echo wp_kses_post( $tresc ); ?></p>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
```

- [ ] **Krok 3: Dopisz rejestrację w `inc/blok-jadlospis.php`**

Zaraz pod `defined( 'ABSPATH' ) || exit;`:

```php
/**
 * Rejestracja bloku z katalogu `blocks/jadlospis` (metadane w `block.json`).
 *
 * Kategoria „Przedszkole” jest już zarejestrowana w `inc/blok-osoba.php`.
 */
function przedszkole_rejestruj_jadlospis() {
	register_block_type( get_theme_file_path( 'blocks/jadlospis' ) );
}
add_action( 'init', 'przedszkole_rejestruj_jadlospis' );
```

- [ ] **Krok 4: Uruchom test — całość ma przejść**

```bash
ddev exec php -l theme/przedszkole/blocks/jadlospis/render.php
ddev exec php -l theme/przedszkole/inc/blok-jadlospis.php
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
```

Oczekiwane: dwa razy `No syntax errors detected`, wszystkie linie `OK`,
na końcu `Success: Wszystko przeszło.`

- [ ] **Krok 5: Commit**

```bash
git add theme/przedszkole/blocks/jadlospis/block.json theme/przedszkole/blocks/jadlospis/render.php theme/przedszkole/inc/blok-jadlospis.php
git commit -m "feat: blok jadlospisu - rejestracja i front" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Zadanie 3: Treść strony „Jadłospis”

**Pliki:** żadne — zmiana w lokalnej bazie (strona `jadlospis`, dziś ID 45).

Tydzień 22–26.06.2026 z tabeli przechodzi do bloku, pod blokiem staje notka
o alergenach. Puste `core/tabs`, tabela i puste akapity znikają; poprzednia
wersja zostaje w rewizjach.

- [ ] **Krok 1: Sprawdź stan wyjściowy**

```bash
ddev exec wp --path=wp eval 'echo implode( ",", array_filter( array_column( parse_blocks( get_post_field( "post_content", przedszkole_id_strony( "jadlospis" ) ) ), "blockName" ) ) ), "\n";' </dev/null
```

Oczekiwane: `core/tabs,core/table,core/paragraph,core/paragraph,core/paragraph`.
Jeśli wynik jest inny — ktoś zmienił stronę; zatrzymaj się i pokaż wynik użytkownikowi.

- [ ] **Krok 2: Przepisz stronę**

Skrypt idzie przez stdin (`eval-file -`) — jednorazowy, nie trafia do repo.
Dwie rzeczy, które łatwo zepsuć:

- znaczniki bloku buduje `serialize_block()` rdzenia — sam escapuje `<`, `>`
  i `--` w JSON-ie atrybutów jako `<` itd.;
- `wp_update_post()` **odcina ukośniki** z danych, więc treść idzie przez
  `wp_slash()`. Bez tego `<strong>` zamienia się w `u003cstrongu003e`
  i pogrubienia przepadają.

```bash
ddev exec wp --path=wp eval-file - <<'PHP'
<?php
$id = przedszkole_id_strony( 'jadlospis' );
if ( ! $id ) {
	WP_CLI::error( 'Nie ma strony o slugu jadlospis.' );
}

$tabela = null;
foreach ( parse_blocks( get_post_field( 'post_content', $id ) ) as $blok ) {
	if ( 'core/table' === $blok['blockName'] ) {
		$tabela = $blok;
		break;
	}
}
if ( ! $tabela ) {
	WP_CLI::error( 'Na stronie nie ma tabeli — treść już przeniesiona?' );
}

$dokument = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><meta charset="utf-8">' . $tabela['innerHTML'], LIBXML_NOERROR );
$dni      = array();

foreach ( $dokument->querySelectorAll( 'tbody tr' ) as $wiersz ) {
	$komorki = $wiersz->querySelectorAll( 'td' );
	if ( 4 !== $komorki->length ) {
		WP_CLI::error( 'Wiersz tabeli nie ma czterech komórek.' );
	}
	$dni[] = array(
		'wolny'        => false,
		'sniadanie'    => trim( $komorki->item( 1 )->innerHTML ),
		'obiad'        => trim( $komorki->item( 2 )->innerHTML ),
		'podwieczorek' => trim( $komorki->item( 3 )->innerHTML ),
	);
	if ( 1 === count( $dni ) && ! str_starts_with( trim( $komorki->item( 0 )->textContent ), '22.06.2026' ) ) {
		WP_CLI::error( 'Pierwszy wiersz nie jest z 22.06.2026 — tabela się zmieniła.' );
	}
}
if ( 5 !== count( $dni ) ) {
	WP_CLI::error( 'Oczekiwano 5 dni, jest ' . count( $dni ) . '.' );
}

$notka  = 'Zupy podawane są z natką pietruszki. Jarzynka do zupy zawiera seler. Pieczywo ciemne może zawierać ziarna sezamu, słonecznika. Dzieci między posiłkami otrzymują czystą wodę do picia. Alergeny są napisane grubszą czcionką. Zupy gotowane są na wywarze drobiowym z jarzynami, dwa razy w tygodniu zupa jest na samym wywarze warzywnym, drugie danie bez mięsa. Ograniczony jest cukier do wszystkich napojów.';
$akapit = "\n<p>" . esc_html( $notka ) . "</p>\n";

$tresc = implode(
	"\n\n",
	array(
		serialize_block(
			array(
				'blockName'    => 'przedszkole/jadlospis',
				'attrs'        => array(
					'poczatek' => '2026-06-22',
					'dni'      => $dni,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		),
		serialize_block(
			array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => $akapit,
				'innerContent' => array( $akapit ),
			)
		),
	)
);

$wynik = wp_update_post(
	wp_slash(
		array(
			'ID'           => $id,
			'post_content' => $tresc,
		)
	),
	true
);
if ( is_wp_error( $wynik ) ) {
	WP_CLI::error( $wynik );
}

WP_CLI::success( 'Strona ' . $id . ' przepisana.' );
PHP
```

Oczekiwane: `Success: Strona 45 przepisana.`

- [ ] **Krok 3: Sprawdź zapis — bloki, data, pogrubienia**

```bash
ddev exec wp --path=wp eval-file - <<'PHP'
<?php
$b = parse_blocks( get_post_field( 'post_content', przedszkole_id_strony( 'jadlospis' ) ) );
$j = $b[0];
echo implode( ',', array_filter( array_column( $b, 'blockName' ) ) ), "\n";
echo $j['attrs']['poczatek'], ' | dni: ', count( $j['attrs']['dni'] ), "\n";
echo 'strong w śniadaniu pn: ', substr_count( $j['attrs']['dni'][0]['sniadanie'], '<strong>' ), "\n";
echo 'piątek, podwieczorek: ', $j['attrs']['dni'][4]['podwieczorek'], "\n";
PHP
```

Oczekiwane:
```
przedszkole/jadlospis,core/paragraph
2026-06-22 | dni: 5
strong w śniadaniu pn: 3
piątek, podwieczorek: Bułeczka maślana, sok jabłkowy/<strong>mąka pszenna, jajka, mleko</strong>/
```

`0` przy `strong` znaczy, że ukośniki przepadły — wróć do kroku 2 (`wp_slash`),
przywróciwszy najpierw poprzednią rewizję.

- [ ] **Krok 4: Sprawdź front**

```bash
JADLOSPIS=$(ddev exec wp --path=wp eval 'echo get_permalink( przedszkole_id_strony( "jadlospis" ) );' </dev/null | tr -d '\r')
curl -s "$JADLOSPIS" | grep -o '<li class="jadlospis__dzien ' | wc -l
curl -s "$JADLOSPIS" | grep -o '22 – 26 czerwca 2026' | head -1
curl -s "$JADLOSPIS" | grep -c 'natką pietruszki'
curl -s "$JADLOSPIS" | grep -ci 'fatal error\|warning:'
curl -s "$JADLOSPIS" | grep -c 'class="wp-block-table\|class="wp-block-tabs'
```

Oczekiwane: `5`, `22 – 26 czerwca 2026`, `1`, `0`, `0`. Ostatnie szuka klas
w znacznikach, nie samej nazwy — selektory `.wp-block-table` mogą stać
w osadzonych stylach globalnych i dałyby fałszywy alarm.

Bez commita — repozytorium się nie zmieniło. Zmianę bazy odnotowuje Zadanie 7
(ponowny zrzut w WDROZENIE.md).

---

## Zadanie 4: Wygląd — ikony i CSS

**Pliki:**
- Nowy: `theme/przedszkole/assets/img/posilek-sniadanie.svg`
- Nowy: `theme/przedszkole/assets/img/posilek-obiad.svg`
- Nowy: `theme/przedszkole/assets/img/posilek-podwieczorek.svg`
- Zmiana: `theme/przedszkole/style.css` (nowa sekcja 29 na końcu pliku)
- Zmiana: `tools/podglad.py:1-2`

- [ ] **Krok 1: `tools/podglad.py` — adres z otoczenia**

Zamień dwie pierwsze linie:

```python
import re,sys,urllib.request,pathlib
base='http://przedszkole.ddev.site'
```

na:

```python
import re,sys,urllib.request,pathlib,os
# Adres lokalnego WordPressa. Gdy porty 80/443 zajmuje inny proces, ddev
# przenosi router na inne porty - wtedy podaj adres w PODGLAD_BAZA.
base=os.environ.get('PODGLAD_BAZA','http://przedszkole.ddev.site')
```

Sprawdź, że podgląd strony działa (certyfikat ddev pochodzi z `mkcert`,
Python sam mu nie ufa — stąd `SSL_CERT_FILE`):

```bash
PODGLAD_BAZA=https://przedszkole.ddev.site:33001 SSL_CERT_FILE="$(mkcert -CAROOT)/rootCA.pem" python3 tools/podglad.py /dla-rodzicow/jadlospis/ jadlospis.podglad.html
```

Oczekiwane: `OK: jadlospis.podglad.html … bajtow`. Plik jest w `.gitignore`.

- [ ] **Krok 2: Obejrzyj stan przed CSS — karty bez stylu**

Otwórz `file:///Users/mateusz/Documents/projects/przedszkole-wp/jadlospis.podglad.html`
w panelu przeglądarki (`preview_start` z `url`). Oczekiwane: nagłówek „22 – 26
czerwca 2026” i zwykła numerowana lista pięciu dni, bez kart. To jest „test
czerwony” dla wyglądu.

- [ ] **Krok 3: Utwórz trzy ikony**

`assets/img/posilek-sniadanie.svg`:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
	<!-- Ikona sniadania: slonce. Wchodzi do style.css jako maska
	     (`mask-image`), wiec liczy sie tylko ksztalt - kolor nadaje CSS,
	     a tryb wysokiego kontrastu przestawia go razem z reszta strony. -->
	<circle cx="12" cy="12" r="4.5"/>
	<path d="M12 1.75v2.5M12 19.75v2.5M1.75 12h2.5M19.75 12h2.5M4.75 4.75l1.75 1.75M17.5 17.5l1.75 1.75M4.75 19.25l1.75-1.75M17.5 6.5l1.75-1.75" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round"/>
</svg>
```

`assets/img/posilek-obiad.svg`:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
	<!-- Ikona obiadu: miska z para. Maska w style.css - liczy sie
	     tylko ksztalt, kolor nadaje CSS. -->
	<path d="M2.5 11h19a9.5 8 0 0 1-19 0z"/>
	<path d="M8.5 21.5h7" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round"/>
	<path d="M8 3.5c-1 1 1 2 0 3.5M12 2c-1 1 1 2 0 3.5M16 3.5c-1 1 1 2 0 3.5" fill="none" stroke="#000" stroke-width="1.75" stroke-linecap="round"/>
</svg>
```

`assets/img/posilek-podwieczorek.svg`:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
	<!-- Ikona podwieczorku: babeczka z wisienka. Maska w style.css -
	     liczy sie tylko ksztalt, kolor nadaje CSS. Krem to trzy kola
	     i prostokat, ktore w masce zlewaja sie w jeden ksztalt. -->
	<circle cx="12" cy="3.25" r="1.75"/>
	<circle cx="7" cy="10" r="3"/>
	<circle cx="12" cy="8.5" r="4"/>
	<circle cx="17" cy="10" r="3"/>
	<rect x="4" y="10" width="16" height="3" rx="1"/>
	<path d="M5 14.5h14l-2 7H7z"/>
</svg>
```

```bash
xmllint --noout theme/przedszkole/assets/img/posilek-*.svg && echo poprawne
```

Oczekiwane: `poprawne`.

- [ ] **Krok 4: Dopisz sekcję 29 na końcu `style.css`**

```css
/* ==========================================================================
   29. Jadlospis
   Wlasny blok `przedszkole/jadlospis`: tydzien jako piec kart, po jednej
   na dzien. Uklad rysuje `blocks/jadlospis/render.php`, edytor powtarza
   te same klasy - jeden komplet regul obsluguje front i podglad.

   Kolory wylacznie ze zmiennych: tryb wysokiego kontrastu (sekcja 28)
   przestawia je sam, bez osobnych regul.

   Siatka liczy sie od szerokosci bloku (`@container`), nie okna. W edytorze
   blok stoi w plotnie o innej szerokosci niz okno, a przy powiekszonym
   tekscie (sekcja 27) progi w `rem` rosna razem z litera - przy A++
   na laptopie zostaja dwie szersze kolumny zamiast pieciu waskich.

   Selektory z prefiksem `.jadlospis`: reguly `.entry__content ol`,
   `li + li` i `h2` (sekcje 10 i 18) waza wiecej niz pojedyncza klasa.
   ========================================================================== */

.jadlospis {
	container: jadlospis / inline-size;
}

/* Na froncie blok wychodzi poza kolumne tresci (760px) i poza `.wrap`
   (1140px) - przy pieciu kolumnach 1140px daje karte ~210px na ~250 znakow
   posilku. Srodek liczony od rodzica, ktory sam stoi na srodku okna.
   `100vw` wlicza pasek przewijania, ale odstep 2 x 1.25rem jest od niego
   szerszy, wiec przewijania w poziomie nie ma. W edytorze te sama role
   pelni klasa `alignfull` - patrz `assets/css/editor.css`. */
.entry__content > .jadlospis {
	--jadlospis-szerokosc: min(1400px, 100vw - 2.5rem);
	width: var(--jadlospis-szerokosc);
	margin-inline: calc(50% - var(--jadlospis-szerokosc) / 2);
}

.jadlospis .jadlospis__zakres {
	margin: 0 0 1.25rem;
	font-size: var(--wp--preset--font-size--large);
}

.jadlospis .jadlospis__dni {
	display: grid;
	grid-template-columns: 1fr;
	gap: 1.25rem;
	margin: 0;
	padding: 0;
	list-style: none;
}

/* 41.25rem = 660px przy domyslnych 16px - tyle ma blok w oknie 700px.
   Dwie karty w rzedzie na siatce czterokolumnowej: samotny piatek
   w ostatnim rzedzie staje wtedy na srodku, a nie przy lewej krawedzi. */
@container jadlospis (min-width: 41.25rem) {
	.jadlospis .jadlospis__dni { grid-template-columns: repeat(4, 1fr); }
	.jadlospis .jadlospis__dzien { grid-column: span 2; }
	.jadlospis .jadlospis__dzien:last-child:nth-child(odd) { grid-column: 2 / span 2; }
}

/* 72.5rem = 1160px - blok w oknie 1200px. Caly tydzien w jednym rzedzie. */
@container jadlospis (min-width: 72.5rem) {
	.jadlospis .jadlospis__dni {
		grid-template-columns: repeat(5, 1fr);
		gap: 1rem;
	}
	.jadlospis .jadlospis__dzien,
	.jadlospis .jadlospis__dzien:last-child:nth-child(odd) { grid-column: auto; }
}

/* Kolor dnia z pary tlo/tekst grupy - kontrast policzony w sekcji 1,
   od 4.78 (Wiewiorki) do 5.83 (Zajaczki). */
.jadlospis__dzien--zabki     { --dzien-tlo: var(--zabki-tlo);     --dzien-tekst: var(--zabki-tekst); }
.jadlospis__dzien--zajaczki  { --dzien-tlo: var(--zajaczki-tlo);  --dzien-tekst: var(--zajaczki-tekst); }
.jadlospis__dzien--kotki     { --dzien-tlo: var(--kotki-tlo);     --dzien-tekst: var(--kotki-tekst); }
.jadlospis__dzien--misie     { --dzien-tlo: var(--misie-tlo);     --dzien-tekst: var(--misie-tekst); }
.jadlospis__dzien--wiewiorki { --dzien-tlo: var(--wiewiorki-tlo); --dzien-tekst: var(--wiewiorki-tekst); }

.jadlospis .jadlospis__dzien {
	display: flex;
	flex-direction: column;
	margin: 0;
	background: var(--wp--preset--color--surface);
	border: 1px solid var(--wp--preset--color--border);
	border-radius: var(--radius-lg);
	box-shadow: var(--shadow);
	overflow: hidden;
}

/* Dzisiejszy dzien - klase i `aria-current` dopisuje `widok.js`.
   Obrys zamiast ramki: nie przesuwa tresci i nie obcina go `overflow`. */
.jadlospis .jadlospis__dzien--dzis {
	outline: 3px solid var(--wp--preset--color--primary);
	outline-offset: 2px;
}

.jadlospis .jadlospis__naglowek {
	padding: 1rem 1.25rem .9rem;
	background: var(--dzien-tlo);
	color: var(--dzien-tekst);
	border-bottom: 1px solid var(--wp--preset--color--border);
}
.jadlospis .jadlospis__nazwa {
	margin: 0;
	font-size: var(--wp--preset--font-size--large);
	font-weight: 800;
	line-height: 1.2;
	color: inherit;
}
.jadlospis .jadlospis__data {
	margin: .15rem 0 0;
	font-size: var(--wp--preset--font-size--small);
	font-weight: 600;
}

/* Plakietka "Dzis". W znacznikach stoi z atrybutem `hidden` - odslania
   ja `widok.js`. Jawna regula `[hidden]`, bo `display` ponizej nadpisalby
   domyslne ukrycie przegladarki i plakietka wisialaby na kazdej karcie.
   Biel na granacie: 9.24. */
.jadlospis .jadlospis__dzis {
	display: inline-block;
	margin: .5rem 0 0;
	padding: .1rem .65rem;
	font-size: var(--wp--preset--font-size--small);
	font-weight: 700;
	line-height: 1.5;
	color: var(--tekst-na-ciemnym);
	background: var(--wp--preset--color--primary);
	border-radius: 999px;
}
.jadlospis .jadlospis__dzis[hidden] { display: none; }

/* Kolumna flex - "Dzien wolny" staje na srodku wolnego miejsca karty,
   a karty w rzedzie i tak maja rowna wysokosc z siatki. */
.jadlospis .jadlospis__tresc {
	display: flex;
	flex: 1;
	flex-direction: column;
	padding: 0 1.25rem 1rem;
}

.jadlospis .jadlospis__posilek { padding-block: 1rem; }
.jadlospis .jadlospis__posilek + .jadlospis__posilek {
	border-top: 1px solid var(--wp--preset--color--border);
}
.jadlospis .jadlospis__posilek p {
	margin: 0;
	font-size: var(--wp--preset--font-size--small);
	line-height: 1.55;
}

/* Etykieta posilku: wielkie litery wylacznie z CSS - w zrodle stoi
   "Sniadanie", zeby czytnik ekranu nie literowal wyrazu.
   Tekst na bieli: zielony 6.47, pomaranczowy 5.88, niebieski 7.04. */
.jadlospis .jadlospis__etykieta {
	display: flex;
	align-items: center;
	gap: .45rem;
	margin: 0 0 .35rem;
	font-size: var(--wp--preset--font-size--small);
	font-weight: 600;
	line-height: 1.3;
	letter-spacing: .04em;
	text-transform: uppercase;
	color: var(--posilek-tekst);
}

/* Ikona przez maske: plik SVG daje ksztalt, kolor nadaje `background-color`.
   Jeden plik na front i edytor, bez kopii SVG w PHP i JS. Ikona jest
   ozdobna - znaczenie niesie napis obok. Adres pliku przy klasie posilku,
   nie w zmiennej: `url()` w zmiennej przegladarki rozwiazuja roznie. */
.jadlospis .jadlospis__etykieta::before {
	content: "";
	flex: none;
	width: 1.4em;
	height: 1.4em;
	background-color: var(--posilek-ikona);
	-webkit-mask-position: center;
	mask-position: center;
	-webkit-mask-size: contain;
	mask-size: contain;
	-webkit-mask-repeat: no-repeat;
	mask-repeat: no-repeat;
}

.jadlospis__posilek--sniadanie {
	--posilek-tekst: var(--zabki-tekst);
	--posilek-ikona: var(--wp--preset--color--zabki);
}
.jadlospis__posilek--obiad {
	--posilek-tekst: var(--wiewiorki-tekst);
	--posilek-ikona: var(--wp--preset--color--wiewiorki);
}
.jadlospis__posilek--podwieczorek {
	--posilek-tekst: var(--zajaczki-tekst);
	--posilek-ikona: var(--wp--preset--color--zajaczki);
}
.jadlospis__posilek--sniadanie .jadlospis__etykieta::before {
	-webkit-mask-image: url("assets/img/posilek-sniadanie.svg");
	mask-image: url("assets/img/posilek-sniadanie.svg");
}
.jadlospis__posilek--obiad .jadlospis__etykieta::before {
	-webkit-mask-image: url("assets/img/posilek-obiad.svg");
	mask-image: url("assets/img/posilek-obiad.svg");
}
.jadlospis__posilek--podwieczorek .jadlospis__etykieta::before {
	-webkit-mask-image: url("assets/img/posilek-podwieczorek.svg");
	mask-image: url("assets/img/posilek-podwieczorek.svg");
}

/* Dzien wolny i dzien bez wpisanych posilkow: napis na srodku wolnego
   miejsca karty. `--ink-soft` na bieli: 6.80. Pod "Dzien wolny" wejdzie
   pozniej obrazek z podpisem. */
.jadlospis .jadlospis__wolne,
.jadlospis .jadlospis__pusty {
	margin: auto 0;
	padding-block: 2rem;
	text-align: center;
	color: var(--wp--preset--color--ink-soft);
}
.jadlospis .jadlospis__wolne {
	font-size: var(--wp--preset--font-size--large);
	font-weight: 700;
}
```

- [ ] **Krok 5: Odśwież podgląd i sprawdź układ przy czterech szerokościach**

```bash
PODGLAD_BAZA=https://przedszkole.ddev.site:33001 SSL_CERT_FILE="$(mkcert -CAROOT)/rootCA.pem" python3 tools/podglad.py /dla-rodzicow/jadlospis/ jadlospis.podglad.html
```

W panelu przeglądarki ustaw szerokość (`resize_window`) i po każdej wykonaj
(`javascript_tool`):

```js
(() => {
  const lista = document.querySelector('.jadlospis__dni');
  const karty = [...lista.children];
  const ost = karty[4].getBoundingClientRect();
  const l = lista.getBoundingClientRect();
  return {
    sciezki: getComputedStyle(lista).gridTemplateColumns.split(' ').length,
    poziomeprzewijanie: document.documentElement.scrollWidth > document.documentElement.clientWidth,
    piatekNaSrodku: Math.abs((ost.left + ost.right) / 2 - (l.left + l.right) / 2) < 2,
    szerokoscKarty: Math.round(karty[0].getBoundingClientRect().width),
  };
})()
```

| Szerokość | `sciezki` | `piatekNaSrodku` | `poziomeprzewijanie` |
|---|---|---|---|
| 375 | 1 | true | false |
| 768 | 4 | true | false |
| 1280 | 5 | — | false |
| 1440 | 5 | — | false |

Obejrzyj zrzut ekranu przy 1440 i 375: nagłówki dni w kolorach grup, ikony
przed etykietami, środa z kartą „Jadłospis w przygotowaniu” **nie** występuje
(wszystkie pięć dni z tabeli ma treść). Po sprawdzeniu przywróć `preset: "desktop"`.

- [ ] **Krok 6: Kontrast — tryb zwykły i wysoki kontrast**

Przy 1440 wykonaj:

```js
(() => {
  const lum = c => { const [r, g, b] = c.match(/[\d.]+/g).slice(0, 3).map(v => { v /= 255; return v <= .03928 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; }); return .2126 * r + .7152 * g + .0722 * b; };
  const k = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return +((x + .05) / (y + .05)).toFixed(2); };
  const wynik = [];
  document.querySelectorAll('.jadlospis__dzien').forEach(li => {
    const n = li.querySelector('.jadlospis__naglowek');
    wynik.push([li.className, k(getComputedStyle(li.querySelector('.jadlospis__nazwa')).color, getComputedStyle(n).backgroundColor)]);
    li.querySelectorAll('.jadlospis__etykieta').forEach(e => wynik.push([e.textContent, k(getComputedStyle(e).color, getComputedStyle(li).backgroundColor)]));
  });
  return { ponizejProgu: wynik.filter(w => w[1] < 4.5), najnizszy: Math.min(...wynik.map(w => w[1])) };
})()
```

Oczekiwane: `ponizejProgu: []`, `najnizszy: 4.78`.

Potem `document.documentElement.dataset.kontrast = 'wysoki'` i ten sam skrypt.
Oczekiwane: `ponizejProgu: []`. Zrzut ekranu: czarne karty z białym obramowaniem,
żółte nazwy dni i etykiety, białe ikony — żadnej jasnej plamy.
Na koniec `delete document.documentElement.dataset.kontrast`.

- [ ] **Krok 7: Powiększony tekst**

Przy 1440: `document.documentElement.dataset.rozmiar = 'bardzo-duzy'`, potem
skrypt z kroku 5. Oczekiwane: `sciezki: 4` (dwie szersze kolumny zamiast pięciu),
`poziomeprzewijanie: false`. Potem 640px (odpowiednik zoomu 200% na 1280px):
`sciezki: 1`, `poziomeprzewijanie: false`. Na koniec
`delete document.documentElement.dataset.rozmiar`.

- [ ] **Krok 8: Commit**

```bash
git add theme/przedszkole/assets/img/posilek-*.svg theme/przedszkole/style.css tools/podglad.py
git commit -m "feat: karty jadlospisu - siatka, kolory dni i ikony posilkow" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Zadanie 5: „Dziś” — `widok.js`

**Pliki:**
- Nowy: `theme/przedszkole/blocks/jadlospis/widok.js`
- Nowy: `theme/przedszkole/blocks/jadlospis/widok.asset.php`
- Zmiana: `theme/przedszkole/blocks/jadlospis/block.json` (klucz `viewScript`)
- Zmiana: `tools/test_jadlospis.php` (sekcja „Skrypty”)

- [ ] **Krok 1: Dopisz sprawdzenia do testu**

W `tools/test_jadlospis.php`, pod linią `// --- Skrypty --- …`:

```php
$widok = generate_block_asset_handle( 'przedszkole/jadlospis', 'viewScript' );
$sprawdz( 'skrypt widoku zarejestrowany', wp_script_is( $widok, 'registered' ) );
$sprawdz( 'skrypt widoku odroczony', 'defer' === wp_scripts()->get_data( $widok, 'strategy' ) );
$sprawdz( 'skrypt widoku bez zależności', wp_script_is( $widok, 'registered' ) && array() === wp_scripts()->registered[ $widok ]->deps );
$sprawdz( 'skrypt widoku w wersji motywu', wp_script_is( $widok, 'registered' ) && PRZEDSZKOLE_VERSION === wp_scripts()->registered[ $widok ]->ver );
```

- [ ] **Krok 2: Uruchom — cztery nowe sprawdzenia padają**

```bash
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
```

Oczekiwane: `BŁĄD` przy czterech „skrypt widoku …”, reszta `OK`.

- [ ] **Krok 3: Utwórz `blocks/jadlospis/widok.asset.php`**

```php
<?php
/**
 * Zależności skryptu frontu bloku „Jadłospis”.
 *
 * Bez kroku budowania listę utrzymujemy ręcznie, jak w `edytor.asset.php`.
 * Skrypt nie ma zależności — plik istnieje dla wersji: bez niego rdzeń
 * doklejałby do adresu wersję WordPressa i po zmianie skryptu przeglądarki
 * trzymałyby starą kopię.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(),
	'version'      => PRZEDSZKOLE_VERSION,
);
```

- [ ] **Krok 4: Utwórz `blocks/jadlospis/widok.js`**

```js
/**
 * Blok "Jadlospis" - wyroznienie dzisiejszego dnia.
 *
 * Data liczona w przegladarce, nie w PHP: strona moze wyjsc z cache,
 * a "dzis" serwera z chwili zapisu cache nie jest "dzis" rodzica.
 * Plakietka "Dzis" stoi juz w znacznikach z atrybutem `hidden` -
 * skrypt tylko ja odslania, wiec nie potrzebuje `wp-i18n`.
 *
 * Bez zaleznosci, ladowany przez `viewScript` z `defer` - tylko na stronie,
 * na ktorej stoi blok.
 */
( function () {
	'use strict';

	function dzis() {
		var teraz = new Date();
		var miesiac = teraz.getMonth() + 1;
		var dzien = teraz.getDate();

		return teraz.getFullYear() + '-' + ( miesiac < 10 ? '0' : '' ) + miesiac + '-' + ( dzien < 10 ? '0' : '' ) + dzien;
	}

	/**
	 * Przewiniecie do dzisiejszej karty - tylko gdy rodzic inaczej musialby
	 * jej szukac, i nigdy wbrew niemu:
	 * - jedna kolumna (odczytana z policzonej siatki, nie z okna - siatka
	 *   liczy sie od szerokosci bloku),
	 * - zwykle wejscie na strone, nie "wstecz" ani odswiezenie,
	 * - bez kotwicy w adresie i bez przewiniecia, ktore juz sie stalo,
	 * - karta zaczyna sie ponizej pierwszego ekranu (w poniedzialek nic sie
	 *   nie dzieje, tytul zostaje widoczny).
	 *
	 * Skok natychmiastowy: `html` ma `scroll-behavior: smooth`, a `auto`
	 * poszloby za nim. Miejsce pod przypietym naglowkiem zostawia
	 * `scroll-padding-top` z sekcji 2 `style.css`.
	 */
	function przewin( karta ) {
		var nawigacja = window.performance && performance.getEntriesByType
			? performance.getEntriesByType( 'navigation' )[ 0 ]
			: null;

		if ( getComputedStyle( karta.parentNode ).gridTemplateColumns.split( ' ' ).length > 1 ) {
			return;
		}
		if ( ! nawigacja || 'navigate' !== nawigacja.type ) {
			return;
		}
		if ( window.location.hash || window.scrollY > 0 ) {
			return;
		}
		if ( karta.getBoundingClientRect().top <= window.innerHeight ) {
			return;
		}

		karta.scrollIntoView( { block: 'start', behavior: 'instant' } );
	}

	var karta = document.querySelector( '.jadlospis__dzien[data-data="' + dzis() + '"]' );

	if ( ! karta ) {
		return;
	}

	karta.classList.add( 'jadlospis__dzien--dzis' );
	karta.setAttribute( 'aria-current', 'date' );

	var plakietka = karta.querySelector( '.jadlospis__dzis' );

	if ( plakietka ) {
		plakietka.hidden = false;
	}

	przewin( karta );
} )();
```

- [ ] **Krok 5: Dopisz skrypt w `block.json`**

Przed linią `"render": "file:./render.php"`:

```json
	"viewScript": "file:./widok.js",
```

- [ ] **Krok 6: Uruchom test**

```bash
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
node --check theme/przedszkole/blocks/jadlospis/widok.js && echo skladnia-ok
```

Oczekiwane: `Success: Wszystko przeszło.`, `skladnia-ok`.

- [ ] **Krok 7: Sprawdź w przeglądarce — dzisiejszy piątek i poniedziałek**

Skrypt na froncie musi trafić **na dół** strony — `podglad.py` zamienia
`<script src>` na wklejony kod i gubi `defer`, więc w `<head>` szukałby kart,
zanim powstaną:

```bash
JADLOSPIS=$(ddev exec wp --path=wp eval 'echo get_permalink( przedszkole_id_strony( "jadlospis" ) );' </dev/null | tr -d '\r')
curl -s "$JADLOSPIS" | grep -n 'jadlospis-view-script\|</body>'
```

Oczekiwane: linia ze skryptem tuż przed `</body>`, nie w `<head>` (blok
dokłada skrypt po wypisaniu nagłówka, więc rdzeń przenosi go do stopki).

Podmień daty w podglądzie na dzisiejszą (piątek = ostatnia karta, poniedziałek = pierwsza):

```bash
PODGLAD_BAZA=https://przedszkole.ddev.site:33001 SSL_CERT_FILE="$(mkcert -CAROOT)/rootCA.pem" python3 tools/podglad.py /dla-rodzicow/jadlospis/ jadlospis.podglad.html
sed "s/data-data=\"2026-06-26\"/data-data=\"$(date +%F)\"/" jadlospis.podglad.html > dzis-piatek.podglad.html
sed "s/data-data=\"2026-06-22\"/data-data=\"$(date +%F)\"/" jadlospis.podglad.html > dzis-poniedzialek.podglad.html
```

Dla każdego pliku: `resize_window` z `preset: "mobile"`, otwórz plik, wykonaj:

```js
(() => {
  const k = document.querySelector('.jadlospis__dzien--dzis');
  return {
    karta: k && k.querySelector('.jadlospis__nazwa').textContent,
    ariaCurrent: k && k.getAttribute('aria-current'),
    plakietkaWidoczna: k && !k.querySelector('.jadlospis__dzis').hidden,
    ukryteNaInnych: document.querySelectorAll('.jadlospis__dzis:not([hidden])').length,
    scrollY: Math.round(window.scrollY),
  };
})()
```

| Plik | `karta` | `ariaCurrent` | `plakietkaWidoczna` | `ukryteNaInnych` | `scrollY` |
|---|---|---|---|---|---|
| dzis-piatek, mobile | Piątek | date | true | 1 | > 0 |
| dzis-poniedzialek, mobile | Poniedziałek | date | true | 1 | 0 |
| dzis-piatek, desktop | Piątek | date | true | 1 | 0 |

Przy „dzis-piatek, mobile” zrzut ekranu: karta piątku tuż pod przypiętym
nagłówkiem, nie schowana pod nim. Potem w tej samej karcie
`window.scrollTo(0, 0); location.reload()` i znów skrypt — `scrollY: 0`
(odświeżenie nie przewija). Na koniec `preset: "desktop"`.

- [ ] **Krok 8: Commit**

```bash
git add theme/przedszkole/blocks/jadlospis/widok.js theme/przedszkole/blocks/jadlospis/widok.asset.php theme/przedszkole/blocks/jadlospis/block.json tools/test_jadlospis.php
git commit -m "feat: wyroznienie dzisiejszego dnia w jadlospisie" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Zadanie 6: Edytor

**Pliki:**
- Nowy: `tools/test_jadlospis_edytor.js`
- Nowy: `theme/przedszkole/blocks/jadlospis/edytor.js`
- Nowy: `theme/przedszkole/blocks/jadlospis/edytor.asset.php`
- Zmiana: `theme/przedszkole/blocks/jadlospis/block.json` (klucz `editorScript`)
- Zmiana: `theme/przedszkole/inc/blok-jadlospis.php` (dane edytora, na końcu)
- Zmiana: `theme/przedszkole/assets/css/editor.css` (na końcu)
- Zmiana: `tools/test_jadlospis.php` (sekcja „Skrypty”)

- [ ] **Krok 1: Utwórz `tools/test_jadlospis_edytor.js`**

```js
/**
 * Test widoku edytora bloku "Jadlospis" - bez przegladarki i bez logowania.
 *
 * Projekt nie ma kroku budowania ani npm; ten plik tez nie ma zaleznosci.
 * Podstawia minimalne `window.wp` (createElement zwraca zwykly obiekt),
 * laduje `edytor.js`, renderuje `edit` i sprawdza drzewo oraz zapisy
 * atrybutow. Lapie literowki i bledy logiki; wygladu nie sprawdza.
 *
 *   node tools/test_jadlospis_edytor.js
 *
 * Zakresy dat te same co w `tools/test_jadlospis.php` - regula stoi
 * w PHP i w JS, oba testy pilnuja, zeby mowily to samo.
 * Dane `przedszkoleJadlospis` przepisane z `inc/blok-jadlospis.php`.
 */
'use strict';

var path = require( 'path' );

var zarejestrowany = null;
var efekty = [];
var oznaczenia = 0;
var bledy = 0;

function el( typ, wlasciwosci ) {
	var dzieci = Array.prototype.slice.call( arguments, 2 ).flat( Infinity ).filter( function ( d ) {
		return null !== d && undefined !== d && false !== d;
	} );

	return { typ: typ, props: wlasciwosci || {}, dzieci: dzieci };
}

global.window = {
	przedszkoleJadlospis: {
		dni: [
			{ nazwa: 'Poniedziałek', grupa: 'zabki' },
			{ nazwa: 'Wtorek', grupa: 'zajaczki' },
			{ nazwa: 'Środa', grupa: 'kotki' },
			{ nazwa: 'Czwartek', grupa: 'misie' },
			{ nazwa: 'Piątek', grupa: 'wiewiorki' },
		],
		posilki: [
			{ klucz: 'sniadanie', etykieta: 'Śniadanie' },
			{ klucz: 'obiad', etykieta: 'Obiad' },
			{ klucz: 'podwieczorek', etykieta: 'Podwieczorek' },
		],
		miesiace: [ 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia' ],
	},
	wp: {
		element: {
			createElement: el,
			useEffect: function ( fn ) {
				efekty.push( fn );
			},
		},
		i18n: {
			__: function ( tekst ) {
				return tekst;
			},
		},
		data: {
			useDispatch: function () {
				return {
					__unstableMarkNextChangeAsNotPersistent: function () {
						oznaczenia++;
					},
				};
			},
		},
		blockEditor: {
			useBlockProps: function ( p ) {
				return p;
			},
			RichText: 'RichText',
			store: 'core/block-editor',
		},
		components: {
			Button: 'Button',
			DatePicker: 'DatePicker',
			Dropdown: 'Dropdown',
			ToggleControl: 'ToggleControl',
		},
		blocks: {
			registerBlockType: function ( nazwa, definicja ) {
				zarejestrowany = { nazwa: nazwa, definicja: definicja };
			},
		},
	},
};

require( path.join( __dirname, '../theme/przedszkole/blocks/jadlospis/edytor.js' ) );

// --- Narzedzia ---

function sprawdz( opis, warunek ) {
	if ( warunek ) {
		console.log( 'OK    ' + opis );
		return;
	}
	bledy++;
	console.log( 'BLAD  ' + opis );
}

function wszystkie( wezel, warunek, wynik ) {
	wynik = wynik || [];
	if ( ! wezel || 'object' !== typeof wezel ) {
		return wynik;
	}
	if ( warunek( wezel ) ) {
		wynik.push( wezel );
	}
	wezel.dzieci.forEach( function ( dziecko ) {
		wszystkie( dziecko, warunek, wynik );
	} );
	return wynik;
}

function tekst( wezel ) {
	return wezel.dzieci.map( function ( d ) {
		return 'object' === typeof d ? tekst( d ) : String( d );
	} ).join( '' );
}

function zKlasa( klasa ) {
	return function ( w ) {
		return ( ' ' + ( w.props.className || '' ) + ' ' ).indexOf( ' ' + klasa + ' ' ) > -1;
	};
}

function typu( nazwa ) {
	return function ( w ) {
		return nazwa === w.typ;
	};
}

function pusteDni() {
	return [ 0, 1, 2, 3, 4 ].map( function () {
		return { wolny: false, sniadanie: '', obiad: '', podwieczorek: '' };
	} );
}

function renderuj( atrybuty ) {
	var zapisy = [];
	efekty = [];
	var drzewo = zarejestrowany.definicja.edit( {
		attributes: atrybuty,
		setAttributes: function ( zmiana ) {
			zapisy.push( zmiana );
		},
	} );
	return { drzewo: drzewo, zapisy: zapisy, ostatni: function () {
		return zapisy[ zapisy.length - 1 ];
	} };
}

// --- Rejestracja ---

sprawdz( 'blok zarejestrowany pod wlasciwa nazwa', null !== zarejestrowany && 'przedszkole/jadlospis' === zarejestrowany.nazwa );
sprawdz( 'save zwraca null - blok dynamiczny', null === zarejestrowany.definicja.save() );

// --- Uklad ---

var w = renderuj( { poczatek: '2026-06-22', dni: pusteDni() } );
var naglowki = wszystkie( w.drzewo, zKlasa( 'jadlospis__zakres' ) );

sprawdz( 'klasy bloku', zKlasa( 'jadlospis' )( w.drzewo ) && zKlasa( 'alignfull' )( w.drzewo ) );
sprawdz( 'zakres w jednym miesiacu', 1 === naglowki.length && '22 – 26 czerwca 2026' === tekst( naglowki[ 0 ] ) );
sprawdz( 'piec kart', 5 === wszystkie( w.drzewo, zKlasa( 'jadlospis__dzien' ) ).length );
sprawdz( 'poniedzialek w kolorze Zabek', 1 === wszystkie( w.drzewo, zKlasa( 'jadlospis__dzien--zabki' ) ).length );
sprawdz( 'data w dopelniaczu', '22 czerwca' === tekst( wszystkie( w.drzewo, zKlasa( 'jadlospis__data' ) )[ 0 ] ) );
sprawdz( 'pietnascie pol posilkow', 15 === wszystkie( w.drzewo, typu( 'RichText' ) ).length );
sprawdz( 'piec przelacznikow dnia wolnego', 5 === wszystkie( w.drzewo, typu( 'ToggleControl' ) ).length );

efekty.forEach( function ( f ) {
	f();
} );
sprawdz( 'blok z data nic nie zapisuje przy montowaniu', 0 === w.zapisy.length );
sprawdz( 'blok z data nic nie oznacza jako nietrwale', 0 === oznaczenia );

[
	[ '2025-09-29', '29 września – 3 października 2025' ],
	[ '2025-12-29', '29 grudnia 2025 – 2 stycznia 2026' ],
].forEach( function ( przypadek ) {
	var naglowek = wszystkie( renderuj( { poczatek: przypadek[ 0 ], dni: pusteDni() } ).drzewo, zKlasa( 'jadlospis__zakres' ) )[ 0 ];
	sprawdz( 'zakres od ' + przypadek[ 0 ], undefined !== naglowek && przypadek[ 1 ] === tekst( naglowek ) );
} );

// Data, ktorej front by nie przyjal (PHP: przedszkole_jadlospis_poczatek):
// nie poniedzialek albo przepelniona. Niepusty atrybut zostaje nietkniety.
[
	[ '2026-06-24', 'dzien inny niz poniedzialek' ],
	[ '2026-02-31', 'przepelniona data' ],
].forEach( function ( przypadek ) {
	var odrzucony = renderuj( { poczatek: przypadek[ 0 ], dni: pusteDni() } );

	efekty.forEach( function ( f ) {
		f();
	} );
	sprawdz( przypadek[ 1 ] + ' bez naglowka i dat', 0 === wszystkie( odrzucony.drzewo, zKlasa( 'jadlospis__zakres' ) ).length && 0 === wszystkie( odrzucony.drzewo, zKlasa( 'jadlospis__data' ) ).length );
	sprawdz( przypadek[ 1 ] + ' - atrybut nienadpisany', 0 === odrzucony.zapisy.length );
} );

w = renderuj( { poczatek: '2026-06-22', dni: [ { sniadanie: 'x' } ] } );
sprawdz( 'niepelny atrybut dni daje piec kart', 5 === wszystkie( w.drzewo, zKlasa( 'jadlospis__dzien' ) ).length );

// --- Dzien wolny ---

var dni = pusteDni();
dni[ 1 ].wolny = true;
dni[ 1 ].obiad = 'zostaje w atrybucie';
w = renderuj( { poczatek: '2026-06-22', dni: dni } );

sprawdz( 'dzien wolny chowa pola', 12 === wszystkie( w.drzewo, typu( 'RichText' ) ).length );
sprawdz( 'dzien wolny pokazuje napis', 1 === wszystkie( w.drzewo, zKlasa( 'jadlospis__wolne' ) ).length );

wszystkie( w.drzewo, typu( 'ToggleControl' ) )[ 1 ].props.onChange( false );
sprawdz( 'odznaczenie zapisuje piec dni', 5 === w.ostatni().dni.length );
sprawdz( 'odznaczenie przywraca dane dnia', false === w.ostatni().dni[ 1 ].wolny && 'zostaje w atrybucie' === w.ostatni().dni[ 1 ].obiad );

// Pierwsze pola: poniedzialek - sniadanie, obiad, podwieczorek.
wszystkie( w.drzewo, typu( 'RichText' ) )[ 1 ].props.onChange( '<strong>mleko</strong>' );
sprawdz( 'pole posilku zapisuje sie w swoim dniu', '<strong>mleko</strong>' === w.ostatni().dni[ 0 ].obiad );
sprawdz( 'zapis pola nie rusza innych dni', true === w.ostatni().dni[ 1 ].wolny );

// --- Data ---

w = renderuj( { poczatek: '', dni: pusteDni() } );
sprawdz( 'bez daty bez naglowka', 0 === wszystkie( w.drzewo, zKlasa( 'jadlospis__zakres' ) ).length );
oznaczenia = 0;
efekty.forEach( function ( f ) {
	f();
} );
sprawdz( 'domyslna data poprzedzona oznaczeniem nietrwalym', 1 === oznaczenia && 1 === w.zapisy.length );

var domyslny = w.zapisy[ 0 ] && w.zapisy[ 0 ].poczatek;
var data = domyslny ? new Date( domyslny + 'T00:00:00' ) : null;
var roznica = data ? ( data - new Date() ) / 864e5 : NaN;

sprawdz( 'swiezy blok dostaje poniedzialek', null !== data && 1 === data.getDay() );
sprawdz( 'poniedzialek z biezacego albo nastepnego tygodnia', roznica > -7 && roznica < 3 );

w = renderuj( { poczatek: '2026-06-22', dni: pusteDni() } );

var rozwijane = wszystkie( w.drzewo, typu( 'Dropdown' ) )[ 0 ];
var zamkniete = false;
var przycisk = rozwijane.props.renderToggle( { onToggle: function () {}, isOpen: false } );
var kalendarz = rozwijane.props.renderContent( {
	onClose: function () {
		zamkniete = true;
	},
} );

sprawdz( 'przycisk "Zmień tydzień"', 'Button' === przycisk.typ && 'Zmień tydzień' === tekst( przycisk ) );
sprawdz( 'ikona kalendarza to svg, nie dashicon', !! przycisk.props.icon && 'svg' === przycisk.props.icon.typ );
sprawdz( 'kalendarz pokazuje zapisany tydzien', '2026-06-22T00:00:00' === kalendarz.props.currentDate );
sprawdz( 'tydzien od poniedzialku', 1 === kalendarz.props.startOfWeek );
sprawdz( 'poniedzialek klikalny', false === kalendarz.props.isInvalidDate( new Date( 2026, 5, 22 ) ) );
sprawdz( 'wtorek nieklikalny', true === kalendarz.props.isInvalidDate( new Date( 2026, 5, 23 ) ) );
sprawdz( 'niedziela nieklikalna', true === kalendarz.props.isInvalidDate( new Date( 2026, 5, 28 ) ) );

kalendarz.props.onChange( '2026-06-29T00:00:00' );
sprawdz( 'wybor daty zapisuje sam dzien', '2026-06-29' === w.ostatni().poczatek );
sprawdz( 'wybor daty zamyka kalendarz', zamkniete );

if ( bledy ) {
	console.log( bledy + ' sprawdzen nie przeszlo.' );
	process.exit( 1 );
}

console.log( 'Wszystko przeszlo.' );
```

- [ ] **Krok 2: Uruchom — ma paść na braku pliku**

```bash
node tools/test_jadlospis_edytor.js
```

Oczekiwane: `Error: Cannot find module '…/blocks/jadlospis/edytor.js'`, kod wyjścia 1.

- [ ] **Krok 3: Utwórz `blocks/jadlospis/edytor.asset.php`**

```php
<?php
/**
 * Zależności skryptu edytora bloku „Jadłospis”.
 *
 * Normalnie ten plik generuje `@wordpress/scripts` przy budowaniu paczki.
 * Motyw nie ma kroku budowania — `edytor.js` to zwykły JavaScript bez JSX,
 * więc listę zależności utrzymujemy ręcznie. Bez `wp-components` zabrakłoby
 * kalendarza, bez `wp-block-editor` — pól tekstowych.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-data',
		'wp-element',
		'wp-i18n',
	),
	'version'      => PRZEDSZKOLE_VERSION,
);
```

- [ ] **Krok 4: Utwórz `blocks/jadlospis/edytor.js`**

```js
/**
 * Blok "Jadlospis" - widok w edytorze.
 *
 * Zwykly JavaScript, bez JSX i bez kroku budowania: `wp.element.createElement`
 * zamiast znacznikow, zaleznosci wyliczone recznie w `edytor.asset.php`.
 *
 * Karty w edytorze maja te same klasy co front, wiec `style.css`
 * (dolaczony przez `add_editor_style`) rysuje je bez osobnej stylistyki.
 * Wyjatkiem jest pasek z kalendarzem i przelacznik dnia wolnego - istnieja
 * tylko w panelu, ich wyglad siedzi w `assets/css/editor.css`.
 *
 * Nazwy dni, kolory grup, posilki i miesiace w dopelniaczu przychodza z PHP
 * (`window.przedszkoleJadlospis`, patrz `inc/blok-jadlospis.php`) - stoja
 * w jednym miejscu. Drugi raz stoi tu tylko regula zakresu dat.
 *
 * Test bez przegladarki: `node tools/test_jadlospis_edytor.js`.
 */
( function ( wp, dane ) {
	'use strict';

	var el = wp.element.createElement;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;

	var useBlockProps = wp.blockEditor.useBlockProps;
	var RichText = wp.blockEditor.RichText;

	var Button = wp.components.Button;
	var DatePicker = wp.components.DatePicker;
	var Dropdown = wp.components.Dropdown;
	var ToggleControl = wp.components.ToggleControl;

	// Ikona jako SVG, nie nazwa dashicona: edytor stoi w iframe, do ktorego
	// rdzen nie laduje arkusza dashicons - przycisk zostalby bez ikony.
	// Ksztalt z ikony kalendarza rdzenia.
	var IKONA_KALENDARZA = el(
		'svg',
		{ xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: 24, height: 24, 'aria-hidden': 'true', focusable: 'false' },
		el( 'path', { d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z' } )
	);

	var PUSTY_DZIEN = { wolny: false, sniadanie: '', obiad: '', podwieczorek: '' };

	// Daty w czasie lokalnym przegladarki. Liczymy dni kalendarzowe,
	// nie chwile, wiec strefa czasowa nie ma tu znaczenia.

	function dwieCyfry( liczba ) {
		return ( liczba < 10 ? '0' : '' ) + liczba;
	}

	// Ta sama regula co w PHP (przedszkole_jadlospis_poczatek): inaczej
	// edytor pokazalby daty, ktorych front nie wyswietli.
	function zTekstu( tekst ) {
		var czesci = /^(\d{4})-(\d{2})-(\d{2})$/.exec( tekst || '' );

		if ( ! czesci ) {
			return null;
		}

		var data = new Date( +czesci[ 1 ], czesci[ 2 ] - 1, +czesci[ 3 ] );

		return naTekst( data ) === tekst && 1 === data.getDay() ? data : null;
	}

	function naTekst( data ) {
		return data.getFullYear() + '-' + dwieCyfry( data.getMonth() + 1 ) + '-' + dwieCyfry( data.getDate() );
	}

	function dodajDni( data, ile ) {
		return new Date( data.getFullYear(), data.getMonth(), data.getDate() + ile );
	}

	function dzienIMiesiac( data ) {
		return data.getDate() + ' ' + dane.miesiace[ data.getMonth() ];
	}

	/**
	 * Zakres tygodnia: "22 - 26 czerwca 2026"; miesiac i rok powtorzone
	 * tylko na granicy. Ta sama regula stoi w `przedszkole_jadlospis_zakres()`
	 * w `inc/blok-jadlospis.php` - zmieniajac jedno, popraw drugie.
	 */
	function zakres( poczatek ) {
		var koniec = dodajDni( poczatek, 4 );
		var od = String( poczatek.getDate() );

		if ( poczatek.getFullYear() !== koniec.getFullYear() ) {
			od = dzienIMiesiac( poczatek ) + ' ' + poczatek.getFullYear();
		} else if ( poczatek.getMonth() !== koniec.getMonth() ) {
			od = dzienIMiesiac( poczatek );
		}

		return od + ' – ' + dzienIMiesiac( koniec ) + ' ' + koniec.getFullYear();
	}

	/**
	 * Poniedzialek dla swiezo wstawionego bloku: biezacy tydzien, a w sobote
	 * i niedziele juz nastepny - jadlospis wpisuje sie z wyprzedzeniem.
	 */
	function domyslnyPoniedzialek() {
		var dzis = new Date();
		var dzien = dzis.getDay(); // 0 - niedziela, 6 - sobota.
		var przesuniecie = 1 - dzien;

		if ( 0 === dzien ) {
			przesuniecie = 1;
		} else if ( 6 === dzien ) {
			przesuniecie = 2;
		}

		return naTekst( dodajDni( dzis, przesuniecie ) );
	}

	/**
	 * Dzien z atrybutu uzupelniony do pelnego ksztaltu - przyklad w `block.json`
	 * ma jeden dzien, a recznie poprawiony komentarz bloku moze miec cokolwiek.
	 */
	function dzien( dni, indeks ) {
		return Object.assign( {}, PUSTY_DZIEN, ( dni && dni[ indeks ] ) || {} );
	}

	/**
	 * Zmiana jednego dnia. Atrybut `dni` to tablica, wiec zmiana pola
	 * to nowa tablica - zawsze pelne piec dni, nawet gdy w atrybucie bylo mniej.
	 */
	function zmienDzien( props, indeks, zmiana ) {
		var dni = dane.dni.map( function ( opis, i ) {
			var obecny = dzien( props.attributes.dni, i );

			return i === indeks ? Object.assign( obecny, zmiana ) : obecny;
		} );

		props.setAttributes( { dni: dni } );
	}

	function kalendarz( props ) {
		var poczatek = props.attributes.poczatek;

		return el( Dropdown, {
			className: 'jadlospis__kalendarz',
			popoverProps: { placement: 'bottom-start' },
			renderToggle: function ( przelacznik ) {
				return el(
					Button,
					{
						variant: 'secondary',
						icon: IKONA_KALENDARZA,
						onClick: przelacznik.onToggle,
						'aria-expanded': przelacznik.isOpen,
					},
					__( 'Zmień tydzień', 'przedszkole' )
				);
			},
			renderContent: function ( okno ) {
				return el( DatePicker, {
					currentDate: poczatek ? poczatek + 'T00:00:00' : undefined,
					startOfWeek: 1,
					// Wylacznie poniedzialki - reszte tygodnia blok liczy sam.
					isInvalidDate: function ( data ) {
						return 1 !== data.getDay();
					},
					onChange: function ( wartosc ) {
						props.setAttributes( { poczatek: wartosc.slice( 0, 10 ) } );
						okno.onClose();
					},
				} );
			},
		} );
	}

	function posilek( props, indeks, opis, tresc ) {
		return el(
			'div',
			{ key: opis.klucz, className: 'jadlospis__posilek jadlospis__posilek--' + opis.klucz },
			el( 'h4', { className: 'jadlospis__etykieta' }, opis.etykieta ),
			el( RichText, {
				tagName: 'p',
				// Kilka pol w jednym bloku - identyfikator pozwala edytorowi
				// odtworzyc kursor we wlasciwym polu po zapisie.
				identifier: 'dzien-' + indeks + '-' + opis.klucz,
				value: tresc,
				placeholder: __( 'Wpisz menu — alergeny pogrub (Ctrl+B)', 'przedszkole' ),
				onChange: function ( wartosc ) {
					var zmiana = {};

					zmiana[ opis.klucz ] = wartosc;
					zmienDzien( props, indeks, zmiana );
				},
			} )
		);
	}

	function karta( props, poczatek, opis, indeks ) {
		var biezacy = dzien( props.attributes.dni, indeks );
		var data = poczatek ? dodajDni( poczatek, indeks ) : null;

		return el(
			'li',
			{ key: opis.grupa, className: 'jadlospis__dzien jadlospis__dzien--' + opis.grupa },
			el(
				'header',
				{ className: 'jadlospis__naglowek' },
				el( 'h3', { className: 'jadlospis__nazwa' }, opis.nazwa ),
				data ? el( 'p', { className: 'jadlospis__data' }, dzienIMiesiac( data ) ) : null
			),
			el(
				'div',
				{ className: 'jadlospis__tresc' },
				el( ToggleControl, {
					className: 'jadlospis__przelacznik',
					label: __( 'Dzień wolny', 'przedszkole' ),
					checked: biezacy.wolny,
					onChange: function ( wartosc ) {
						zmienDzien( props, indeks, { wolny: wartosc } );
					},
					__nextHasNoMarginBottom: true,
				} ),
				biezacy.wolny
					? el( 'p', { className: 'jadlospis__wolne' }, __( 'Dzień wolny', 'przedszkole' ) )
					: dane.posilki.map( function ( opisPosilku ) {
						return posilek( props, indeks, opisPosilku, biezacy[ opisPosilku.klucz ] );
					} )
			)
		);
	}

	wp.blocks.registerBlockType( 'przedszkole/jadlospis', {
		edit: function ( props ) {
			var a = props.attributes;
			var poczatek = zTekstu( a.poczatek );

			// `alignfull` z tego samego powodu co w `render.php`: bez tej klasy
			// kontener ukladu rdzenia scisnalby blok do kolumny tresci.
			var blockProps = useBlockProps( { className: 'jadlospis alignfull' } );

			// Hook zawsze wywolany, przed useEffect - stala kolejnosc hookow.
			var oznaczNietrwala = wp.data.useDispatch( wp.blockEditor.store ).__unstableMarkNextChangeAsNotPersistent;

			// Swiezo wstawiony blok od razu dostaje poniedzialek - stanu
			// "bez daty" intendent w praktyce nie zobaczy.
			useEffect( function () {
				if ( ! a.poczatek ) {
					// Wstawienie bloku i domyslna data to jeden krok cofania:
					// bez tego Ctrl+Z po wstawieniu czyscilby sama date,
					// a efekt (puste zaleznosci) juz by jej nie przywrocil.
					oznaczNietrwala();
					props.setAttributes( { poczatek: domyslnyPoniedzialek() } );
				}
			}, [] );

			return el(
				'section',
				blockProps,
				el(
					'div',
					{ className: 'jadlospis__tydzien' },
					kalendarz( props ),
					poczatek ? el( 'h2', { className: 'jadlospis__zakres' }, zakres( poczatek ) ) : null
				),
				el(
					'ol',
					{ className: 'jadlospis__dni' },
					dane.dni.map( function ( opis, indeks ) {
						return karta( props, poczatek, opis, indeks );
					} )
				)
			);
		},

		// Blok jest dynamiczny - front rysuje `render.php`, w tresci strony
		// zostaje sam komentarz z atrybutami.
		save: function () {
			return null;
		},
	} );
} )( window.wp, window.przedszkoleJadlospis );
```

- [ ] **Krok 5: Uruchom test edytora**

```bash
node --check theme/przedszkole/blocks/jadlospis/edytor.js && echo skladnia-ok
node tools/test_jadlospis_edytor.js
```

Oczekiwane: `skladnia-ok`, wszystkie linie `OK`, na końcu `Wszystko przeszlo.`

- [ ] **Krok 6: Dopisz sprawdzenia edytora do testu PHP**

W `tools/test_jadlospis.php`, pod sprawdzeniami skryptu widoku:

```php
$edytor = generate_block_asset_handle( 'przedszkole/jadlospis', 'editorScript' );
$sprawdz( 'skrypt edytora zarejestrowany', wp_script_is( $edytor, 'registered' ) );
$sprawdz( 'skrypt edytora ma kalendarz (wp-components)', wp_script_is( $edytor, 'registered' ) && in_array( 'wp-components', wp_scripts()->registered[ $edytor ]->deps, true ) );

if ( function_exists( 'przedszkole_jadlospis_dane_edytora' ) ) {
	przedszkole_jadlospis_dane_edytora();
}
$przed = implode( "\n", (array) wp_scripts()->get_data( $edytor, 'before' ) );
$sprawdz( 'dane edytora wstrzyknięte', false !== strpos( $przed, 'window.przedszkoleJadlospis' ) );
$sprawdz( 'miesiące w dopełniaczu', false !== strpos( $przed, 'czerwca' ) );
```

```bash
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
```

Oczekiwane: `BŁĄD` przy czterech nowych sprawdzeniach, reszta `OK`.

- [ ] **Krok 7: Dopisz `editorScript` w `block.json`**

Przed linią `"viewScript": "file:./widok.js",`:

```json
	"editorScript": "file:./edytor.js",
```

- [ ] **Krok 8: Dopisz dane edytora na końcu `inc/blok-jadlospis.php`**

```php
/**
 * Dane dla edytora: dni, posiłki i miesiące w dopełniaczu.
 *
 * Pakiet `@wordpress/date` zna tylko mianownik („czerwiec”), więc
 * „22 czerwca” w podglądzie wymaga odmiany z PHP (`$wp_locale->month_genitive`).
 * Przy okazji dni i posiłki też idą stąd — nazwy i kolory stoją w jednym
 * miejscu, a `edytor.js` ich nie powtarza.
 */
function przedszkole_jadlospis_dane_edytora() {
	global $wp_locale;

	$posilki = array();

	foreach ( przedszkole_jadlospis_posilki() as $klucz => $etykieta ) {
		$posilki[] = array(
			'klucz'    => $klucz,
			'etykieta' => $etykieta,
		);
	}

	$dane = array(
		'dni'      => przedszkole_jadlospis_dni(),
		'posilki'  => $posilki,
		'miesiace' => array_values( $wp_locale->month_genitive ),
	);

	wp_add_inline_script(
		generate_block_asset_handle( 'przedszkole/jadlospis', 'editorScript' ),
		'window.przedszkoleJadlospis = ' . wp_json_encode( $dane ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'przedszkole_jadlospis_dane_edytora' );
```

- [ ] **Krok 9: Dopisz style panelu na końcu `assets/css/editor.css`**

```css
/* Jadlospis. Na froncie blok wychodzi poza kolumne tresci regula pod
   `.entry__content` - w edytorze robi to klasa `alignfull`, ktora uklad
   rdzenia rozciaga na cale plotno, az do krawedzi. Tu odsuniecie kart od
   tych krawedzi, a na szerokim plotnie rosnace, zeby karty nie byly szersze
   niz na froncie (front: min(1400px, 100vw - 2.5rem)). 100% to szerokosc
   kontenera glownego; blok jest o 2.5rem szerszy przez ujemne marginesy
   `alignfull`, stad 100% + 2.5rem. */
.editor-styles-wrapper .jadlospis { padding-inline: max(1.25rem, calc((100% + 2.5rem - 1400px) / 2)); }

/* Pasek nad kartami: przycisk kalendarza i zakres tygodnia w jednym rzedzie.
   Istnieje wylacznie w edytorze - na froncie stoi sam naglowek. */
.jadlospis__tydzien {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: .75rem 1.25rem;
	margin-bottom: 1.25rem;
}
.editor-styles-wrapper .jadlospis__tydzien .jadlospis__zakres { margin: 0; }

/* Przelacznik dnia wolnego nad polami posilkow. */
.jadlospis__przelacznik { margin-block: 1rem .25rem; }
```

- [ ] **Krok 10: Uruchom oba testy**

```bash
ddev exec php -l theme/przedszkole/inc/blok-jadlospis.php
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
node tools/test_jadlospis_edytor.js
```

Oczekiwane: `No syntax errors detected`, `Success: Wszystko przeszło.`, `Wszystko przeszlo.`

- [ ] **Krok 11: Commit**

```bash
git add tools/test_jadlospis_edytor.js tools/test_jadlospis.php theme/przedszkole/blocks/jadlospis/edytor.js theme/przedszkole/blocks/jadlospis/edytor.asset.php theme/przedszkole/blocks/jadlospis/block.json theme/przedszkole/inc/blok-jadlospis.php theme/przedszkole/assets/css/editor.css
git commit -m "feat: edytor jadlospisu - kalendarz poniedzialkow i karty dni" -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Zadanie 7: Wersja, tłumaczenia, dokumentacja

**Pliki:**
- Zmiana: `theme/przedszkole/style.css` (nagłówek `Version:`)
- Zmiana: `theme/przedszkole/functions.php:10` (`PRZEDSZKOLE_VERSION`)
- Zmiana: `theme/przedszkole/languages/przedszkole.pot`
- Zmiana: `CLAUDE.md`, `PLAN.md`, `WDROZENIE.md`, `tools/README.md`

- [ ] **Krok 1: Podbij wersję w obu miejscach naraz**

```bash
grep -n "^Version:" theme/przedszkole/style.css
grep -n "PRZEDSZKOLE_VERSION'" theme/przedszkole/functions.php
```

Oba mają pokazywać `1.0.0`. Zmień na `1.1.0`:
- `style.css`: `Version: 1.0.0` → `Version: 1.1.0`
- `functions.php`: `define( 'PRZEDSZKOLE_VERSION', '1.0.0' );` → `define( 'PRZEDSZKOLE_VERSION', '1.1.0' );`

Skrypty bloku mają wersję z `PRZEDSZKOLE_VERSION` — bez podbicia przeglądarki
trzymałyby starą kopię (CLAUDE.md, pułapka 6).

- [ ] **Krok 2: Odśwież szablon tłumaczeń**

```bash
ddev exec wp --path=wp i18n make-pot theme/przedszkole theme/przedszkole/languages/przedszkole.pot --domain=przedszkole </dev/null
grep -c '^msgid' theme/przedszkole/languages/przedszkole.pot
grep -n 'Dzień wolny\|Zmień tydzień\|Jadłospis w przygotowaniu\|Project-Id-Version' theme/przedszkole/languages/przedszkole.pot
```

Oczekiwane: więcej niż 124 ciągi (tyle było), wszystkie trzy napisy obecne,
`Project-Id-Version: Przedszkole 1.1.0`.

- [ ] **Krok 3: `CLAUDE.md` — wyjątek od zasady „bez własnych bloków”**

W sekcji „Czego nie robimy” zamień akapit:

```markdown
- Własnych bloków Gutenberga bez wyraźnej potrzeby — zwykle wystarczają
  wzorce z `patterns/` i warianty stylów (`register_block_style`).
  Jeden wyjątek: `przedszkole/osoba` (`blocks/osoba/`) — kafelek kadry
  powtarzany kilkanaście razy na jednej stronie. Wzorzec trzymał układ
  w treści, więc każda poprawka wyglądu szła przez wszystkie kopie,
  a jedno nieostrożne kliknięcie zostawiało pół kafelka. Próg dla drugiego
  bloku jest ten sam: układ powtarzany wielokrotnie i psujący się w rękach
  pracownika, nie „byłoby wygodniej".
```

na:

```markdown
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
```

W sekcji „CSS” dopisz punkt:

```markdown
- Blok szerszy niż kolumna treści: w edytorze kontener układu rdzenia wymusza
  na każdym bloku poza `.alignfull` szerokość treści i `margin: auto !important`.
  Taki blok dostaje klasę `alignfull`, a jego siatka — `@container` zamiast
  `@media`, bo w płótnie edytora okno ma inną szerokość niż blok
  (wzór: sekcja 29 `style.css`)
```

W „Praca z wp-cli”, po pułapce 9, dopisz:

````markdown
10. **`wp_update_post()` odcina ukośniki.** JSON atrybutów bloku ma ich pełno
    (`<strong>`), więc treść z blokami zapisywana ze skryptu idzie
    przez `wp_slash()`. Bez tego pogrubienia zamieniają się w `u003cstrongu003e`:
    ```php
    wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $tresc ) ) );
    ```
````

W „Podgląd wizualny” pod blokiem z `podglad.py` dopisz:

````markdown
Gdy porty 80/443 zajmuje inny proces, ddev przenosi router (np. na 33001) —
adres i certyfikat trzeba wtedy podać jawnie:

```bash
PODGLAD_BAZA=https://przedszkole.ddev.site:33001 SSL_CERT_FILE="$(mkcert -CAROOT)/rootCA.pem" python3 tools/podglad.py /dla-rodzicow/jadlospis/ j.podglad.html
```
````

- [ ] **Krok 4: `PLAN.md` — rejestr decyzji**

Na końcu tabeli w sekcji „Rejestr decyzji” (ostatni wiersz przed `## Pytania otwarte`):

```markdown
| 2026-10-02 | Jadłospis jako własny blok `przedszkole/jadlospis` | tydzień przepisywany co tydzień rozsypywał się najpierw w zakładkach, potem w tabeli — ten sam próg co kafelek osoby. Dane w atrybutach (data poniedziałku i pięć dni), układ w `blocks/jadlospis/render.php` |
| 2026-10-02 | Jeden tydzień na stronie, bez przełącznika i archiwum | intendent nadpisuje tydzień; zero JS do nawigacji, zero starych tygodni do sprzątania. Kalendarz w edytorze przepuszcza wyłącznie poniedziałki |
| 2026-10-02 | Siatka jadłospisu na `@container`, blok z klasą `alignfull` | w edytorze kontener układu rdzenia wymusza `margin: auto !important` i szerokość treści na wszystkim poza `.alignfull`, a `@media` liczyłoby okno zamiast bloku. Progi w `rem` — przy A++ zostają dwie szersze kolumny |
| 2026-10-02 | „Dziś” liczone w przeglądarce, plakietka w znacznikach z `hidden` | strona może wyjść z cache; skrypt nie potrzebuje `wp-i18n` na froncie. Przewija tylko w jednej kolumnie, przy zwykłym wejściu i gdy karta jest poza pierwszym ekranem |
| 2026-10-02 | Nazwy dni, posiłków i miesiące w dopełniaczu podawane edytorowi z PHP | `@wordpress/date` zna tylko mianownik; jedno źródło zamiast kopii w JS. W JS zostaje reguła zakresu dat, a zgodność z PHP pilnują `tools/test_jadlospis.php` i `tools/test_jadlospis_edytor.js` |
| 2026-10-02 | Notka o alergenach jako zwykły akapit pod blokiem | edytowalna przez intendenta bez zmian w kodzie; znaczenie pogrubienia podane tekstem (WCAG 1.3.1) |
```

- [ ] **Krok 5: `WDROZENIE.md` — zrzut nieaktualny, dług treściowy, testy**

W sekcji „2. Dług treściowy”, przed akapitem `**Reszta długu …**`:

```markdown
**Jadłospis — przepisany na blok 2026-10-02.** Strona pokazuje tydzień
22–26.06.2026, przeniesiony z dawnej tabeli jako przykład wyglądu. Stary
jadłospis nie znika sam: przed startem albo pierwszego dnia intendent wpisuje
bieżący tydzień (kalendarz w bloku, potem pola posiłków).
```

W sekcji „4. Eksport bazy…”, pod zdaniem `Do powtórzenia, jeśli treść zmieni się
przed wdrożeniem — zrzut jest fotografią bazy, nie dokumentem.`:

```markdown
**Nieaktualny od 2026-10-02** — strona „Jadłospis” przepisana na blok. Zrzut
z 2026-09-21 do powtórzenia razem z próbnym importem.
```

W sekcji „12. Testy odbiorcze”, pod linią `- [ ] Strony grup, Kadra …, Jadłospis, Kontakt z mapą`:

```markdown
- [ ] Jadłospis: pięć kart na desktopie, dwie w rzędzie na tablecie (piątek
      na środku), jedna na telefonie; w bieżącym tygodniu karta „Dziś”;
      konto `intendent` zmienia tydzień i zapisuje stronę
```

- [ ] **Krok 6: `tools/README.md` — trzy narzędzia**

W sekcji `## podglad.py`, pod blokiem z przykładami, dopisz:

````markdown
Adres lokalnego WordPressa można nadpisać zmienną `PODGLAD_BAZA` — potrzebne,
gdy ddev stoi na innych portach niż 80/443. Certyfikat ddev pochodzi z `mkcert`,
więc dla `https` trzeba też wskazać jego CA:

```bash
PODGLAD_BAZA=https://przedszkole.ddev.site:33001 SSL_CERT_FILE="$(mkcert -CAROOT)/rootCA.pem" python3 tools/podglad.py /dla-rodzicow/jadlospis/ j.podglad.html
```
````

Na końcu pliku:

````markdown
## `test_jadlospis.php`

Sprawdzenie bloku „Jadłospis” bez PHPUnit: funkcje dat i dni, rejestracja,
front renderowany przez `render_block()` na sztucznych atrybutach (treści
strony nie dotyka) i rejestracja skryptów.

```bash
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
```

Wypisuje `OK` / `BŁĄD` przy każdym sprawdzeniu, kończy się kodem 1, jeśli
coś nie przeszło.

## `test_jadlospis_edytor.js`

Widok edytora bloku „Jadłospis” bez przeglądarki i bez logowania: podstawia
minimalne `window.wp`, renderuje `edit` i sprawdza drzewo oraz zapisy
atrybutów — kalendarz, dzień wolny, pola posiłków, zakres dat.

```bash
node tools/test_jadlospis_edytor.js
```

Wymaga samego `node`, bez npm i bez zależności — to nie jest krok budowania.
Zakresy dat są te same co w `test_jadlospis.php`: reguła stoi w PHP i w JS,
oba testy pilnują, żeby mówiły to samo. Wyglądu nie sprawdza.
````

- [ ] **Krok 7: Sprawdź całość**

```bash
ddev exec wp --path=wp eval 'echo PRZEDSZKOLE_VERSION, "\n";' </dev/null
ddev exec wp --path=wp eval-file tools/test_jadlospis.php
node tools/test_jadlospis_edytor.js
JADLOSPIS=$(ddev exec wp --path=wp eval 'echo get_permalink( przedszkole_id_strony( "jadlospis" ) );' </dev/null | tr -d '\r')
curl -s -o /dev/null -w "%{http_code}\n" "$JADLOSPIS"
curl -s "$JADLOSPIS" | grep -o 'widok.js?ver=[0-9.]*'
curl -s "$JADLOSPIS" | grep -ci "fatal error\|warning:"
git status --short
```

Oczekiwane: `1.1.0`, oba testy przechodzą, `200`, `widok.js?ver=1.1.0`, `0`;
w `git status` wyłącznie pliki z tego zadania (podglądy `*.podglad.html`
są w `.gitignore`).

- [ ] **Krok 8: Commit**

```bash
git add theme/przedszkole/style.css theme/przedszkole/functions.php theme/przedszkole/languages/przedszkole.pot CLAUDE.md PLAN.md WDROZENIE.md tools/README.md
git commit -m "docs: blok jadlospisu w konwencjach, rejestrze i wdrozeniu" -m "Wersja motywu 1.1.0, szablon tlumaczen odswiezony. Zrzut produkcyjny z 2026-09-21 oznaczony jako nieaktualny - strona Jadlospis zmienila tresc." -m "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

## Odbiór całości

**Agent** — każdy punkt musi przejść:

- [ ] `ddev exec wp --path=wp eval-file tools/test_jadlospis.php` → `Success`
- [ ] `node tools/test_jadlospis_edytor.js` → `Wszystko przeszlo.`
- [ ] strona „Jadłospis”: 5 kart, nagłówek „22 – 26 czerwca 2026”, notka pod kartami
- [ ] 375px — 1 kolumna; 768px — 2 kolumny, piątek na środku; 1280 i 1440px — 5 kolumn
- [ ] brak przewijania w poziomie przy każdej szerokości, przy A++ i przy 640px
- [ ] kontrast ≥ 4.5 w trybie zwykłym i wysokim kontraście (skrypt z Zadania 4)
- [ ] „Dziś”: ramka, plakietka i `aria-current` tylko na jednej karcie;
      przewijanie tylko w jednej kolumnie, nie w poniedziałek, nie po odświeżeniu
- [ ] nagłówki h1 → h2 → h3 → h4 (`read_page` na podglądzie)
- [ ] `Version:` w `style.css` i `PRZEDSZKOLE_VERSION` — oba `1.1.0`
- [ ] w treści strony nie ma `core/table` ani `core/tabs`

**Użytkownik** — w panelu (`$JADLOSPIS` z `/wp-admin/`), bo agent nie loguje się na `*.ddev.site`:

- [ ] blok „Jadłospis” jest w inserterze w kategorii „Przedszkole”
- [ ] na stronie z blokiem drugiego nie da się wstawić
- [ ] w nowym bloku (np. na szkicu testowym) od razu stoi bieżący tydzień
- [ ] „Zmień tydzień” otwiera kalendarz, klikalne są tylko poniedziałki,
      zakres nad kartami zmienia się od razu
- [ ] „Dzień wolny” chowa pola i pokazuje napis; odznaczenie przywraca wpisaną treść
- [ ] Ctrl+B pogrubia, Enter łamie wiersz w tym samym polu
- [ ] przy szerokim płótnie (panel boczny zamknięty) pięć kart, przy otwartym — dwie
- [ ] logowanie na konto `intendent`: zmiana jednego posiłku, „Aktualizuj”,
      front pokazuje zmianę **z zachowanym pogrubieniem**
- [ ] szkic testowy usunięty (do kosza i z kosza — patrz WDROZENIE.md, krok 2)

## Poza zakresem tego planu

- Obrazek z podpisem dla dnia wolnego (osobna sesja) — miejsce na niego
  to `.jadlospis__wolne` w `render.php` i `edytor.js`.
- Druk, PDF, przełącznik tygodni, archiwum jadłospisów.
- Ponowny zrzut produkcyjny — oznaczony w WDROZENIE.md, wykonuje się przy wdrożeniu.
