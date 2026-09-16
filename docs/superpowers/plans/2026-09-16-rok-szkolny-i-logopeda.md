# Rok szkolny na listach wpisów + kategoria logopedy — plan wdrożenia

> **Dla agentów:** WYMAGANY SUB-SKILL: `superpowers:subagent-driven-development`
> (zalecany) albo `superpowers:executing-plans`. Kroki mają składnię checkboxów
> (`- [ ]`) do odhaczania.

**Cel:** listy aktualności i archiwa kategorii pokazują domyślnie bieżący rok
szkolny, starsze roczniki są osiągalne przez `?rok=`, a artykuły logopedki żyją
we własnej kategorii poza tym podziałem.

**Podejście:** rok szkolny liczony z daty publikacji — bez nowej taksonomii, bez
zmian w panelu, bez reguł przepisania. Cięcie realizuje jeden `pre_get_posts`
z `date_query`. Treść logopedki wraca ze zrzutu Joomli skryptem, który
dokłada się do istniejącej maszynerii migracyjnej.

**Stack:** WordPress 7.1, PHP 8.5, motyw `theme/przedszkole`, wp-cli przez
`ddev exec wp --path=wp`, skrypty pomocnicze w `tools/` (bash + python3).

**Spec:** [2026-09-16-rok-szkolny-i-logopeda-design.md](../specs/2026-09-16-rok-szkolny-i-logopeda-design.md)

---

## Jak weryfikujemy — przeczytaj przed pierwszym zadaniem

**W tym projekcie nie ma PHPUnit, composera ani phpcs.** Nie instalujemy ich
przy okazji tej zmiany — to motyw jednej strony, a spec tego nie przewiduje.
Pętla TDD zostaje, tylko narzędziem jest to, czym projekt faktycznie dysponuje:
`wp eval` do funkcji PHP i `curl` + `grep` do wyjścia HTML.

Każde zadanie ma więc krok „sprawdź, że **nie** działa" przed implementacją
i „sprawdź, że działa" po niej. Jeśli krok weryfikacyjny przechodzi, zanim
cokolwiek napisałeś — zatrzymaj się, bo albo test jest bezużyteczny, albo
funkcja już istnieje.

**Pułapka wp-cli pod PHP 8.5** (CLAUDE.md): do poleceń zwracających tabelki
dodawaj `--format=csv`, inaczej stderr zaleje się ostrzeżeniami
`Deprecated: Using null as an array offset`. Nie tłum ich przez `2>/dev/null`.

**Pułapka pętli** (CLAUDE.md): `ddev exec` zjada stdin. W pętlach `while read`
i `for` dodawaj `</dev/null`.

**Prefiks archiwum kategorii to `/category/`, nie `/kategoria/`.** Opcja
`category_base` jest pusta, więc WordPress używa domyślnego angielskiego
segmentu. Co gorsza, `/kategoria/misie/` nie zwraca 404 tylko przekierowuje
301 na `/grupy/misie/` — bo strona grupy ma ten sam slug co kategoria,
a WordPress rozstrzyga kolizję na korzyść strony. Krok weryfikacyjny pod złym
adresem wygląda więc na działający i pokazuje treść, tylko nie tę, o którą
pytasz. Adres archiwum sprawdzaj przez `get_category_link()`, nie zgadując.

**Stan danych na 2026-09-16: w roczniku 2026/2027 nie ma ani jednego
opublikowanego wpisu.** Najnowszy wpis w bazie jest z 15 czerwca 2026.
Po Zadaniu 3 lista `/aktualnosci/` i wszystkie archiwa kategorii będą więc
puste aż do pierwszego wpisu nowego roku szkolnego — to zaprojektowane
zachowanie, nie usterka. Strona główna pozostaje wypełniona, bo zgodnie
z decyzją nie tnie swojego bloku po roczniku.

---

## Struktura plików

| Plik | Odpowiedzialność | Operacja |
|---|---|---|
| `theme/przedszkole/inc/rok-szkolny.php` | model roku szkolnego, walidacja `?rok=`, `pre_get_posts` | **nowy** |
| `theme/przedszkole/template-parts/przelacznik-lat.php` | formularz wyboru rocznika | **nowy** |
| `theme/przedszkole/template-parts/wpisy-kategorii.php` | blok „3 ostatnie" dla grup i logopedy | **nowy** (zastępuje `aktualnosci-grupy.php`) |
| `theme/przedszkole/template-parts/aktualnosci-grupy.php` | — | **kasowany** |
| `theme/przedszkole/functions.php` | wczytanie nowego pliku, wersja motywu | zmiana |
| `theme/przedszkole/index.php` | wpięcie przełącznika, komunikat o pustym roku | zmiana |
| `theme/przedszkole/page.php` | zmiana nazwy wołanego template partu | zmiana |
| `theme/przedszkole/inc/seo.php` | `noindex` na `?rok=` | zmiana |
| `theme/przedszkole/style.css` | style przełącznika, podbicie `Version:` | zmiana |
| `tools/logopeda.sh` | kategoria `logopeda` + slug strony, idempotentnie | **nowy** |
| `tools/migracja_logopedy.py` | import artykułów z kategorii 19 Joomli | **nowy** |
| `PLAN.md`, `MIGRACJA.md`, `tools/README.md` | rejestr decyzji, bilans migracji, opis narzędzi | zmiana |

Podział idzie za odpowiedzialnością, nie za warstwą: cały model roku szkolnego
razem z jego hakiem siedzi w jednym pliku, bo zmienia się razem. `inc/helpers.php`
zostaje workiem na drobiazgi, którym jest dziś — nie dokładamy do niego.

---

## Korekta wobec specu — przeczytaj, zanim zaczniesz Zadanie 8

Spec mówi o **27 artykułach do importu (23 nowe + 4 przepięcia)**. Przegląd
zrzutu przed rozpisaniem planu wykazał, że liczba jest o dwa za wysoka:

- **Artykuł 32 „Godziny pracy logopedy"** (2015) to pięć akapitów z godzinami
  `Poniedziałek 10:40 - 14:40`. Aktualne godziny stoją już w treści strony
  „Kącik logopedy" i mówią `poniedziałek 12:00 - 16:00`. Import dałby na stronie
  dwie sprzeczne wersje tej samej informacji. **Pomijamy świadomie.**
- **Artykuł 1224 „Co robimy na zajęciach logopedycznych?"** to wyłącznie
  `<img src="images/259377452_….jpg">` i nic poza tym. Plik przepadł ze starym
  serwerem, więc po wycięciu znacznika zostaje pustka. Istniejąca maszyneria
  odrzuci go sama (`if not tekst(kod)` w `zbuduj()`), ale liczba końcowa musi
  to uwzględniać.

**Oczekiwany wynik: 25 wpisów w kategorii `logopeda`** — 21 nowych
plus 4 zaktualizowane. Ta liczba obowiązuje w kryteriach odbioru.

Druga korekta, tym razem upraszczająca. Spec przewidywał osobny krok
„przepięcie czterech wpisów po slugu". Okazało się to zbędne: migracja
z Etapu 2b zapisuje przy każdym wpisie meta `_joomla_id`, a importer
(`IMPORTER` w `tools/migracja_wpisow.py`) po tym polu rozpoznaje wpis
i robi `wp_set_post_categories( $id, [...], false )` — czyli **zastępuje**
kategorie. Cztery istniejące wpisy (WP 333, 331, 592, 591 → Joomla 1897, 1899,
1613, 1614) przejdą z `Ogłoszeń` na `logopeda` w tym samym przebiegu co import.
Dopasowanie po `_joomla_id` jest przy tym trwalsze niż po slugu, więc intencja
specu („nie po ID WordPressa") zostaje spełniona z nawiązką.

Skutek uboczny do świadomej akceptacji: te cztery wpisy dostaną też tytuł, slug
i treść odtworzone ze zrzutu. Weszły automatem i nikt ich ręcznie nie poprawiał,
więc nie ma czego stracić — ale gdyby ktoś je w międzyczasie wyedytował,
zmiany przepadną. Sprawdź `wp post list --post_modified` przed Zadaniem 8.

---

## Zadanie 1: Model roku szkolnego

**Pliki:**
- Nowy: `theme/przedszkole/inc/rok-szkolny.php`
- Zmiana: `theme/przedszkole/functions.php` (sekcja `require_once`)

- [ ] **Krok 1: Sprawdź, że funkcji jeszcze nie ma**

```bash
ddev exec wp --path=wp eval 'var_dump( function_exists( "przedszkole_rok_szkolny" ) );'
```

Oczekiwane: `bool(false)`

- [ ] **Krok 2: Znajdź miejsce na `require_once`**

```bash
grep -n "require_once" theme/przedszkole/functions.php
```

Zapamiętaj numer ostatniej linii z `require_once` — dopiszesz pod nią w kroku 4.

- [ ] **Krok 3: Utwórz `theme/przedszkole/inc/rok-szkolny.php`**

```php
<?php
/**
 * Rok szkolny na listach wpisów.
 *
 * Przedszkole żyje rokiem szkolnym, nie kalendarzowym. Listy aktualności
 * pokazują domyślnie rok bieżący, starsze roczniki wchodzą przez `?rok=`.
 *
 * Rok wyliczamy z daty publikacji, a nie z taksonomii ani pola własnego.
 * Dzięki temu nauczycielka nic nie klika, nie ma kilkudziesięciu pustych
 * terminów, a przeniesienie wpisu do innego rocznika to zmiana daty
 * publikacji — coś, co WordPress i tak potrafi.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kategoria wyjęta spod podziału na lata.
 *
 * Artykuły logopedki to poradniki — „Rozwój mowy dziecka" z 2016 roku jest
 * tak samo aktualny jak wpis z wczoraj. Cięcie po roczniku schowałoby je
 * bez powodu.
 */
const PRZEDSZKOLE_LOGOPEDA = 'logopeda';

/**
 * Rok szkolny dla podanej daty.
 *
 * Granica to 1 września. Wrzesień zaczyna rok `R/R+1`, wszystko przed nim
 * należy jeszcze do `R-1/R`.
 *
 * @param string|null $data Data w formacie zrozumiałym dla `strtotime()`.
 *                          Null oznacza teraz.
 * @return string Na przykład `2026/2027`.
 */
function przedszkole_rok_szkolny( $data = null ) {
	$znacznik = null === $data ? time() : strtotime( $data );

	$rok     = (int) wp_date( 'Y', $znacznik );
	$miesiac = (int) wp_date( 'n', $znacznik );

	if ( $miesiac < 9 ) {
		--$rok;
	}

	return $rok . '/' . ( $rok + 1 );
}

/**
 * `2026/2027` → `2026-2027`. Ukośnik nie przejdzie w adresie.
 *
 * @param string $rok Rok szkolny z ukośnikiem.
 * @return string
 */
function przedszkole_rok_slug( $rok ) {
	return str_replace( '/', '-', $rok );
}

/**
 * `2026-2027` → `2026/2027`.
 *
 * @param string $slug Rok szkolny z myślnikiem.
 * @return string
 */
function przedszkole_rok_z_slug( $slug ) {
	return str_replace( '-', '/', $slug );
}

/**
 * Lista roczników, od bieżącego do najstarszego wpisu w serwisie.
 *
 * Lista jest wspólna dla całego serwisu, a nie liczona osobno dla każdej
 * kategorii — to jedno zapytanie zamiast siedmiu grupowań. Rocznik bez wpisów
 * w danej kategorii nie jest ślepym zaułkiem: użytkownik zobaczy komunikat
 * z Zadania 4.
 *
 * @return string[] Slugi, malejąco.
 */
function przedszkole_lata_szkolne() {
	$lata = get_transient( 'przedszkole_lata' );

	if ( is_array( $lata ) && $lata ) {
		return $lata;
	}

	global $wpdb;

	$najstarszy = $wpdb->get_var(
		"SELECT MIN( post_date ) FROM {$wpdb->posts}
		 WHERE post_type = 'post' AND post_status = 'publish'"
	);

	$do = (int) substr( przedszkole_rok_szkolny(), 0, 4 );
	$od = $najstarszy ? (int) substr( przedszkole_rok_szkolny( $najstarszy ), 0, 4 ) : $do;

	$lata = array();

	for ( $rok = $do; $rok >= $od; $rok-- ) {
		$lata[] = przedszkole_rok_slug( $rok . '/' . ( $rok + 1 ) );
	}

	set_transient( 'przedszkole_lata', $lata, DAY_IN_SECONDS );

	return $lata;
}

/**
 * Czyści listę roczników po zmianie wpisów.
 */
function przedszkole_zapomnij_lata() {
	delete_transient( 'przedszkole_lata' );
}
add_action( 'save_post', 'przedszkole_zapomnij_lata' );
add_action( 'deleted_post', 'przedszkole_zapomnij_lata' );

/**
 * Rocznik żądany w adresie, po walidacji.
 *
 * **To jest zabezpieczenie, nie kosmetyka.** Wartość trafia prosto do
 * `date_query`, więc przyjmujemy wyłącznie slugi obecne na liście roczników —
 * przynależność do zbioru, nie dopasowanie wzorca. Cokolwiek innego cicho
 * spada do roku bieżącego.
 *
 * @return string Slug rocznika.
 */
function przedszkole_rok_z_zapytania() {
	// Odczyt publicznego filtra listy, bez zmiany stanu — nonce nie ma tu sensu.
	$rok = isset( $_GET['rok'] ) ? sanitize_text_field( wp_unslash( $_GET['rok'] ) ) : '';

	if ( in_array( $rok, przedszkole_lata_szkolne(), true ) ) {
		return $rok;
	}

	return przedszkole_rok_slug( przedszkole_rok_szkolny() );
}

/**
 * Zakres dat rocznika w postaci `date_query`.
 *
 * @param string $slug Slug rocznika, na przykład `2026-2027`.
 * @return array
 */
function przedszkole_zakres_roku( $slug ) {
	$rok = (int) substr( $slug, 0, 4 );

	return array(
		array(
			'after'     => array(
				'year'  => $rok,
				'month' => 9,
				'day'   => 1,
			),
			'before'    => array(
				'year'  => $rok + 1,
				'month' => 8,
				'day'   => 31,
			),
			'inclusive' => true,
		),
	);
}
```

- [ ] **Krok 4: Podłącz plik w `functions.php`**

Pod ostatnim `require_once` znalezionym w kroku 2 dopisz:

```php
require_once get_theme_file_path( 'inc/rok-szkolny.php' );
```

Jeśli sąsiednie wiersze używają innej formy (na przykład `get_template_directory()`),
zachowaj ich zapis zamiast tego — spójność z plikiem jest ważniejsza.

- [ ] **Krok 5: Sprawdź, że funkcje liczą poprawnie**

```bash
ddev exec wp --path=wp eval '
echo przedszkole_rok_szkolny( "2026-09-01" ), "|",
     przedszkole_rok_szkolny( "2026-08-31" ), "|",
     przedszkole_rok_slug( "2026/2027" ), "|",
     przedszkole_rok_z_slug( "2026-2027" ), "\n";
'
```

Oczekiwane dokładnie: `2026/2027|2025/2026|2026-2027|2026/2027`

- [ ] **Krok 6: Sprawdź listę roczników**

```bash
ddev exec wp --path=wp eval 'echo implode( ",", przedszkole_lata_szkolne() ), "\n";'
```

Oczekiwane: `2026-2027,2025-2026,2024-2025,2023-2024` — najstarszy wpis
w bazie jest z 2023 roku, bieżący rocznik to 2026/2027.

- [ ] **Krok 7: Sprawdź, że śmieci w `?rok=` nie przechodzą**

```bash
ddev exec wp --path=wp eval '
$_GET["rok"] = "2026-2027";  echo przedszkole_rok_z_zapytania(), "|";
$_GET["rok"] = "1999-2000";  echo przedszkole_rok_z_zapytania(), "|";
$_GET["rok"] = "\" OR 1=1";  echo przedszkole_rok_z_zapytania(), "\n";
'
```

Oczekiwane: `2026-2027|2026-2027|2026-2027` — pierwszy jest prawdziwy,
dwa kolejne spadają do roku bieżącego, bo nie ma ich na liście.

- [ ] **Krok 8: Commit**

```bash
git add theme/przedszkole/inc/rok-szkolny.php theme/przedszkole/functions.php
git commit -m "feat: model roku szkolnego liczonego z daty publikacji"
```

---

### Poprawki naniesione podczas wdrożenia — 2026-09-16

Kod podany wyżej w Kroku 3 **zawiera trzy usterki**, wykryte przez przegląd
jakości już po pierwszym commicie. Obowiązuje wersja z repozytorium
(`theme/przedszkole/inc/rok-szkolny.php`), nie ta z planu. Jeśli odtwarzasz
zadanie od zera, nanieś je od razu:

**1. Podwójna konwersja strefy czasowej** (commit `b368a53`). `strtotime()`
czyta string jako UTC, bo WordPress ustawia domyślną strefę PHP na UTC,
a `post_date` jest zapisany w czasie lokalnym serwisu — `wp_date()` dokładał
offset drugi raz. Wpis z 31 sierpnia 23:30 trafiał do rocznika 2026/2027
zamiast 2025/2026, czyli błąd siedział dokładnie na granicy, którą ta funkcja
ma obsługiwać. Zamiast `strtotime()` + `wp_date()` używamy `mysql2date()`
dla stringa i `current_time()` dla `null`.

**2. Zerowa data MySQL** (commit `d59dc1b`). `mysql2date( 'Y', '0000-00-00
00:00:00' )` zwraca `-0001`, a nie `false`, więc fallback na `0 === $rok`
jej nie łapał i funkcja zwracała `-1/0`. Warunek obejmuje teraz `$rok < 1970`.
Druga warstwa siedzi w `przedszkole_lata_szkolne()`: pętla jest przycinana,
gdy `$od` wyjdzie większe od `$do` albo rozpiętość przekroczy sto lat —
inaczej jedna uszkodzona data zapisywała dwa tysiące roczników do transientu
na dobę. To nie jest hipotetyczne, bo treść tej strony pochodzi z importu.

**3. Nadgorliwe kasowanie transientu** (commit `b368a53`).
`przedszkole_zapomnij_lata()` reagowała na każdy `save_post`, czyli też
na rewizje, autozapisy i zapis stron, mediów czy pozycji menu — dobowy cache
nie miał szans przeżyć edycji czegokolwiek. Callback przyjmuje teraz `$post_id`
i wychodzi dla rewizji, autozapisów oraz typów innych niż `post`. Ten sam
callback obsługuje `deleted_post`, bo rdzeń odpala ten hak **przed**
`clean_post_cache()` — sprawdzone w `wp/wp-includes/post.php`.

**Uwaga do Kroku 7.** Polecenie `ddev exec wp --path=wp eval '...'` nie przejdzie
z ładunkiem `" OR 1=1`, bo powłoka rozwija zmienne i gubi cytowanie. Zapisz kod
do pliku i uruchom `ddev exec wp --path=wp eval-file <plik>`, tak jak robi
to `tools/migracja_wpisow.py`, a plik potem skasuj. Ta sama uwaga dotyczy
każdego kroku w planie, który woła `wp eval` z apostrofami w środku.

---

## Zadanie 2: Kategoria logopedy i slug strony

Kategoria musi istnieć, zanim `pre_get_posts` z Zadania 3 zacznie ją wyłączać.

**Pliki:**
- Nowy: `tools/logopeda.sh`

- [ ] **Krok 1: Sprawdź stan wyjściowy**

```bash
ddev exec wp --path=wp term list category --fields=name,slug --format=csv
ddev exec wp --path=wp post list --post_type=page --name=kacik-logopedy --fields=ID,post_name --format=csv
```

Oczekiwane: wśród kategorii **nie ma** `logopeda`; strona o slugu
`kacik-logopedy` istnieje.

- [ ] **Krok 2: Utwórz `tools/logopeda.sh`**

```bash
#!/usr/bin/env bash
# Kategoria „Kącik logopedy” i dopasowanie sluga strony.
#
# Motyw wiąże stronę z kategorią po slugu — tak samo jak strony grup
# (`page.php` woła template part z `post_name`). Dlatego strona i kategoria
# muszą mieć ten sam slug: `logopeda`.
#
# `logopeda`, nie `kacik-logopedy`, bo stary adres Joomli to
# `dla-rodzicow/logopeda` — przekierowania 301 z Etapu 9 robią się trywialne.
#
# Idempotentny: istniejącej kategorii nie zakłada drugi raz, sluga nie rusza,
# jeśli już jest poprawny.

set -euo pipefail

cd "$(dirname "$0")/.."

wp() { ddev exec wp --path=wp "$@" </dev/null; }

SLUG=logopeda
NAZWA="Kącik logopedy"
OPIS="Artykuły i porady logopedy przedszkolnego."

# Zwraca ID jedynej strony o danym slugu (puste, jeśli żadnej). Przerywa
# skrypt, jeśli slug pasuje do więcej niż jednej strony — zmiana sluga jest
# nieodwracalna, więc zgadywanie „która to ta właściwa” byłoby gorsze niż
# jawny błąd. `wc -l`, nie `grep -c`: ten drugi zwraca kod 1 przy zerze
# dopasowań, co pod `set -e` wywaliłoby skrypt bez czytelnego komunikatu.
id_strony() {
	local slug="$1" wyniki liczba
	wyniki=$(wp post list --post_type=page --name="$slug" --field=ID --format=csv)
	if [ -z "$wyniki" ]; then
		return 0
	fi
	liczba=$(printf '%s\n' "$wyniki" | wc -l | tr -d ' ')
	if [ "$liczba" -gt 1 ]; then
		echo "BŁĄD: $liczba stron ma slug „$slug” — powinna być jedna. Sprawdź ręcznie." >&2
		exit 1
	fi
	printf '%s' "$wyniki"
}

if [ -n "$(wp term list category --slug="$SLUG" --field=term_id --format=csv)" ]; then
	echo "Kategoria „$NAZWA” już jest — pomijam."
else
	wp term create category "$NAZWA" --slug="$SLUG" --description="$OPIS" >/dev/null
	echo "Utworzona kategoria „$NAZWA” ($SLUG)."
fi

ID=$(id_strony kacik-logopedy)

if [ -n "$ID" ]; then
	wp post update "$ID" --post_name="$SLUG" >/dev/null
	NOWY_SLUG=$(wp post get "$ID" --field=post_name)
	if [ "$NOWY_SLUG" != "$SLUG" ]; then
		echo "BŁĄD: strona $ID dostała slug „$NOWY_SLUG”, nie „$SLUG” (WordPress dokleił sufiks?). Sprawdź ręcznie." >&2
		exit 1
	fi
	echo "Strona $ID: slug zmieniony na $SLUG."
else
	ID=$(id_strony "$SLUG")
	if [ -n "$ID" ]; then
		echo "Strona $ID ma już slug $SLUG — pomijam."
	else
		echo "UWAGA: nie znalazłem strony „Kącik logopedy”. Sprawdź ręcznie." >&2
		exit 1
	fi
fi
```

Ten listing jest kopią `tools/logopeda.sh` z repozytorium, wziętą po przeglądzie
jakości. Pierwotna wersja w planie **nie parsowała się w bashu** — trzy wywołania
`echo` otwierały cudzysłów typograficzny „ a zamykały prostym ASCII `"`, co
przedwcześnie kończyło string. Dołożono też obsługę więcej niż jednego
dopasowania sluga, sprawdzenie faktycznego wyniku `wp post update` (WordPress
sam dokleja sufiks, gdy slug jest zajęty) oraz wyciszenie `stdout` wywołań
wp-cli, żeby zgadzała się obiecana niżej liczba linii potwierdzenia.

- [ ] **Krok 3: Nadaj prawa wykonywania i uruchom**

```bash
chmod +x tools/logopeda.sh
tools/logopeda.sh
```

Oczekiwane: dwie linie potwierdzenia — utworzona kategoria i zmieniony slug.

- [ ] **Krok 4: Sprawdź wynik**

```bash
ddev exec wp --path=wp term list category --slug=logopeda --fields=name,slug,count --format=csv
ddev exec wp --path=wp post list --post_type=page --name=logopeda --fields=ID,post_title,post_name --format=csv
```

Oczekiwane: kategoria `Kącik logopedy,logopeda,0` oraz strona ze slugiem `logopeda`.

- [ ] **Krok 5: Sprawdź idempotencję**

```bash
tools/logopeda.sh
```

Oczekiwane: „Kategoria … już jest — pomijam." i potwierdzenie sluga.
Żadnego błędu, żadnej drugiej kategorii.

- [ ] **Krok 6: Commit**

```bash
git add tools/logopeda.sh
git commit -m "feat: kategoria Kacik logopedy i dopasowanie sluga strony"
```

---

## Zadanie 3: Cięcie list po roczniku

**Pliki:**
- Zmiana: `theme/przedszkole/inc/rok-szkolny.php` (dopisanie na końcu)

- [ ] **Krok 1: Sprawdź, że listy pokazują wszystkie roczniki**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -c 'datetime="202[345]'
```

Oczekiwane: liczba większa od zera — na pierwszej stronie archiwum Misiów
stoją dziś wpisy sprzed bieżącego rocznika.

- [ ] **Krok 2: Dopisz hak na końcu `inc/rok-szkolny.php`**

```php
/**
 * Czy bieżący widok podlega cięciu po roczniku.
 *
 * Wydzielone, bo odpowiedzi potrzebują też przełącznik lat i komunikat
 * o pustym roczniku — a warunek musi być w trzech miejscach ten sam.
 *
 * @return bool
 */
function przedszkole_rok_aktywny() {
	if ( is_category( PRZEDSZKOLE_LOGOPEDA ) ) {
		return false;
	}

	return is_home() || is_category();
}

/**
 * Ogranicza listy wpisów do jednego rocznika.
 *
 * Wchodzi wyłącznie na listę aktualności i archiwa kategorii. Wyszukiwarka,
 * archiwa dat i pojedyncze wpisy zostają nietknięte — tam cięcie tylko
 * przeszkadzałoby. Panel również, bo redaktor musi widzieć całość.
 *
 * @param WP_Query $zapytanie Modyfikowane zapytanie.
 */
function przedszkole_tnij_po_roku( $zapytanie ) {
	if ( is_admin() || ! $zapytanie->is_main_query() ) {
		return;
	}

	if ( ! $zapytanie->is_home() && ! $zapytanie->is_category() ) {
		return;
	}

	if ( $zapytanie->is_category( PRZEDSZKOLE_LOGOPEDA ) ) {
		return;
	}

	$zapytanie->set( 'date_query', przedszkole_zakres_roku( przedszkole_rok_z_zapytania() ) );
}
add_action( 'pre_get_posts', 'przedszkole_tnij_po_roku' );
```

- [ ] **Krok 3: Sprawdź, że archiwum grupy pokazuje już tylko bieżący rocznik**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -c 'datetime="202[345]'
```

Oczekiwane: `0` — wpisy z roczników 2023/24–2025/26 zniknęły z domyślnego widoku.

- [ ] **Krok 4: Sprawdź, że starszy rocznik wchodzi przez adres**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/?rok=2024-2025" | grep -o 'datetime="20[0-9][0-9]' | sort -u
```

Oczekiwane: wyłącznie `datetime="2024` i `datetime="2025` — nic spoza rocznika
2024/2025.

- [ ] **Krok 5: Sprawdź, że logopeda jest wyjęta spod cięcia**

Kategoria jest jeszcze pusta (treść wchodzi w Zadaniu 8), więc sprawdzamy sam
warunek, nie wynik:

```bash
ddev exec wp --path=wp eval '
$q = new WP_Query( array( "category_name" => "logopeda" ) );
var_dump( $q->is_category( PRZEDSZKOLE_LOGOPEDA ) );
'
```

Oczekiwane: `bool(true)` — czyli `przedszkole_tnij_po_roku()` wyjdzie na tym
warunku bez ustawiania `date_query`.

- [ ] **Krok 6: Sprawdź, że wyszukiwarka nadal widzi całą historię**

```bash
curl -s "http://przedszkole.ddev.site/?s=przedszkole" | grep -o 'datetime="20[0-9][0-9]' | sort -u | wc -l
```

Oczekiwane: liczba większa niż 1 — wyniki obejmują więcej niż jeden rok
kalendarzowy, czyli cięcie tam nie sięgnęło.

- [ ] **Krok 7: Commit**

```bash
git add theme/przedszkole/inc/rok-szkolny.php
git commit -m "feat: listy aktualnosci tna sie do biezacego roku szkolnego"
```

---

### Poprawki naniesione podczas wdrożenia — 2026-09-16

Kod z Kroku 2 **przepuszcza dwa widoki, których ciąć nie wolno**. Obowiązuje
wersja z repozytorium.

**1. Kanały RSS** (commit `ba52edc`). `/aktualnosci/feed/` i kanały wszystkich
kategorii zwracały zero pozycji, bo mają `is_home()` albo `is_category()`
prawdziwe. Subskrybent dostawał pustkę bez wyjaśnienia — w czytniku nie ma
przełącznika lat ani komunikatu o pustym roczniku.

**2. Archiwum daty połączone z kategorią** (commit `51b6a89`). `/2024/?cat=7`
ma jednocześnie `is_date()` i `is_category()`. Cięcie doklejało `date_query`
do ograniczenia roku kalendarzowego, które rdzeń buduje osobno w `WHERE`
(`wp/wp-includes/class-wp-query.php`, okolice linii 2109). Przecięcie jest
zawsze puste: adres zwracający 19 wpisów Misiów z 2024 zaczął pokazywać
„Brak wpisów". To był żywy regres, nie teoria.

**3. Jeden predykat zamiast dwóch kopii warunku** (commit `51b6a89`).
`przedszkole_rok_aktywny()` i `przedszkole_tnij_po_roku()` miały w docbloku
obietnicę, że warunek jest ten sam — i rozjechały się w ciągu jednego commita,
gdy wyłączenie kanałów trafiło tylko do jednej z nich. Powstał
`przedszkole_widok_podlega_rocznikowi( WP_Query $zapytanie )`; hak woła go
z otrzymanym obiektem, `przedszkole_rok_aktywny()` z `$GLOBALS['wp_query']`.
Docbloki odsyłają do predykatu zamiast powtarzać listę wyjątków — powtórzona
lista rozjedzie się znowu.

**Uwaga do kroków weryfikacyjnych.** Dopisz do zestawu adresy, które muszą
pozostać nietknięte, bo bez nich obie usterki przechodzą niezauważone:

```bash
printf "/2024/?cat=7 -> "; curl -s "http://przedszkole.ddev.site/2024/?cat=7" | grep -c '<article'
for u in "/feed/" "/aktualnosci/feed/" "/category/misie/feed/"; do
  printf "%-26s -> " "$u"; curl -s "http://przedszkole.ddev.site$u" | grep -c "<item>"
done
```

---

## Zadanie 4: Przełącznik roczników

**Pliki:**
- Nowy: `theme/przedszkole/template-parts/przelacznik-lat.php`
- Zmiana: `theme/przedszkole/index.php`
- Zmiana: `theme/przedszkole/style.css`

- [ ] **Krok 1: Sprawdź, że przełącznika nie ma**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -c 'name="rok"'
```

Oczekiwane: `0`

- [ ] **Krok 2: Utwórz `theme/przedszkole/template-parts/przelacznik-lat.php`**

```php
<?php
/**
 * Wybór rocznika nad listą wpisów.
 *
 * Zwykły formularz GET: bez JavaScriptu, z klawiatury, z czytnikiem ekranu.
 * Przycisk „Pokaż" zamiast zdarzenia `change`, bo lista, która przeskakuje
 * przy strzałce w dół, jest dla użytkownika klawiatury pułapką.
 *
 * Paginacji nie obsługujemy sami — `paginate_links()` scala parametry
 * z bieżącego adresu do odnośników stron.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

if ( ! przedszkole_rok_aktywny() ) {
	return;
}

$przedszkole_lata = przedszkole_lata_szkolne();

// Przy jednym roczniku przełącznik nie ma czego przełączać.
if ( count( $przedszkole_lata ) < 2 ) {
	return;
}

$przedszkole_wybrany = przedszkole_rok_z_zapytania();

if ( is_category() ) {
	$przedszkole_adres = get_category_link( get_queried_object_id() );
} else {
	$przedszkole_strona = get_option( 'page_for_posts' );
	$przedszkole_adres  = $przedszkole_strona ? get_permalink( $przedszkole_strona ) : home_url( '/' );
}
?>
<nav class="lata" aria-label="<?php esc_attr_e( 'Wybór roku szkolnego', 'przedszkole' ); ?>">
	<form class="lata__form" method="get" action="<?php echo esc_url( $przedszkole_adres ); ?>">
		<label class="lata__etykieta" for="rok">
			<?php esc_html_e( 'Rok szkolny', 'przedszkole' ); ?>
		</label>

		<select class="lata__wybor" name="rok" id="rok">
			<?php foreach ( $przedszkole_lata as $przedszkole_rok ) : ?>
				<option value="<?php echo esc_attr( $przedszkole_rok ); ?>"
					<?php selected( $przedszkole_rok, $przedszkole_wybrany ); ?>>
					<?php echo esc_html( przedszkole_rok_z_slug( $przedszkole_rok ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<button class="lata__przycisk" type="submit">
			<?php esc_html_e( 'Pokaż', 'przedszkole' ); ?>
		</button>
	</form>
</nav>
```

- [ ] **Krok 3: Wepnij przełącznik w `index.php`**

Znajdź istniejący blok:

```php
	if ( is_home() || is_category() ) {
		get_template_part( 'template-parts/filtr-kategorii' );
	}
```

Zamień na:

```php
	if ( is_home() || is_category() ) {
		get_template_part( 'template-parts/filtr-kategorii' );
		get_template_part( 'template-parts/przelacznik-lat' );
	}
```

- [ ] **Krok 4: Dołóż style w `style.css`**

Znajdź sekcję ze stylami `.filtr` (szukaj `grep -n "\.filtr" theme/przedszkole/style.css`)
i pod jej końcem dopisz:

```css
/* Przelacznik rocznikow - stoi pod filtrem kategorii, w tej samej belce. */
.lata {
	margin: 0 0 2rem;
}

.lata__form {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 0.5rem;
}

.lata__etykieta {
	font-weight: 700;
}

.lata__wybor,
.lata__przycisk {
	font: inherit;
	padding: 0.4rem 0.75rem;
	border: 2px solid currentColor;
	border-radius: 999px;
	background: var(--wp--preset--color--base);
	color: inherit;
}

.lata__przycisk {
	cursor: pointer;
}

.lata__wybor:focus-visible,
.lata__przycisk:focus-visible {
	outline: 3px solid var(--wp--preset--color--contrast);
	outline-offset: 2px;
}
```

Jeśli nazwy zmiennych `--wp--preset--color--base` i `--contrast` nie występują
w `style.css`, sprawdź `theme.json` (`grep -n '"slug"' theme/przedszkole/theme.json`)
i użyj tych, które tam są. Własnych kolorów nie wprowadzamy.

- [ ] **Krok 5: Sprawdź, że przełącznik się pojawił i ma poprawnie zaznaczony rocznik**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -A2 'name="rok"' | head -5
curl -s "http://przedszkole.ddev.site/category/misie/?rok=2024-2025" | grep -o 'value="2024-2025" *selected'
```

Oczekiwane: pierwszy pokazuje `<select ... name="rok" id="rok">` z opcjami;
drugi zwraca niepustą linię z `selected`.

- [ ] **Krok 6: Sprawdź etykietę i przycisk — to są wymogi dostępności**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -o '<label[^>]*for="rok"[^>]*>' 
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -c 'class="lata__przycisk" type="submit"'
```

Oczekiwane: etykieta z `for="rok"` obecna, przycisk policzony jako `1`.
Brak któregokolwiek to błąd WCAG, nie drobiazg — popraw, zanim pójdziesz dalej.

- [ ] **Krok 7: Sprawdź, że przełącznika nie ma tam, gdzie nie powinno go być**

```bash
curl -s "http://przedszkole.ddev.site/category/logopeda/" | grep -c 'name="rok"'
curl -s "http://przedszkole.ddev.site/?s=dzieci" | grep -c 'name="rok"'
```

Oczekiwane: `0` i `0`.

- [ ] **Krok 8: Sprawdź, że paginacja nie gubi rocznika**

```bash
curl -s "http://przedszkole.ddev.site/aktualnosci/?rok=2024-2025" | grep -o 'href="[^"]*page/2[^"]*"' | head -1
```

Oczekiwane: adres zawiera `rok=2024-2025`. Jeśli nie zawiera, wpis w planie
o `paginate_links()` jest nieprawdziwy — zatrzymaj się i zgłoś.

- [ ] **Krok 9: Commit**

```bash
git add theme/przedszkole/template-parts/przelacznik-lat.php theme/przedszkole/index.php theme/przedszkole/style.css
git commit -m "feat: przelacznik rocznikow nad listami wpisow"
```

---

## Zadanie 5: Komunikat o pustym roczniku

**Pliki:**
- Zmiana: `theme/przedszkole/index.php` (gałąź `else` przy braku wpisów)

- [ ] **Krok 1: Sprawdź obecny komunikat**

Potrzebna jest kombinacja kategorii i rocznika, która nic nie zwraca.
Nie zgaduj — znajdź ją:

**Uwaga na dwie pułapki.** `wp post list` **nie obsługuje** `--after`
ani `--before` — przyjmuje je i po cichu ignoruje, więc licznik zawsze wyjdzie
pełny. A `ddev exec wp eval '...'` rozwija `$zmienne` w shellu, zanim kod
dojdzie do PHP. Obie omija `wp eval-file`, tak jak robi to `migracja_wpisow.py`:

```bash
cat > wp/_sprawdz.php <<'PHP'
<?php
foreach ( array( 'misie', 'zajaczki', 'zabki', 'kotki', 'wiewiorki', 'jezyki', 'ogloszenia' ) as $kat ) {
	foreach ( przedszkole_lata_szkolne() as $rok ) {
		$q = new WP_Query( array(
			'post_type'      => 'post',
			'category_name'  => $kat,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => przedszkole_zakres_roku( $rok ),
		) );
		if ( ! $q->have_posts() ) {
			printf( "PUSTE: %s / %s\n", $kat, $rok );
		}
	}
}
PHP
ddev exec wp --path=wp eval-file wp/_sprawdz.php
rm -f wp/_sprawdz.php
```

Ten test idzie tą samą ścieżką co kod produkcyjny — woła `przedszkole_zakres_roku()`
z Zadania 1, więc sprawdza przy okazji, czy zakres dat jest policzony poprawnie.

Weź pierwszą wypisaną parę i podstaw ją niżej zamiast `<kategoria>` i `<rocznik>`:

```bash
curl -s "http://przedszkole.ddev.site/category/<kategoria>/?rok=<rocznik>" | grep -A3 'class="notice"'
```

Oczekiwane: „Brak wpisów" i „Nie ma tu jeszcze żadnych treści." — komunikat
ogólny, nieinformujący, o który rocznik chodzi.

Na dzień pisania planu polecenie wypisuje **sześć par** — żadna z grup nie
ma jeszcze wpisu w roczniku 2026/2027, bo rok szkolny ruszył dwa tygodnie temu.
To nie jest usterka danych, tylko dokładnie ten scenariusz, pod który
projektowaliśmy komunikat. Jeśli mimo to lista wyjdzie pusta (bo grupy zdążyły
coś dodać), użyj starszego rocznika z listy.

- [ ] **Krok 2: Podmień gałąź `else` w `index.php`**

Znajdź:

```php
	<?php else : ?>

		<div class="notice">
			<h2><?php esc_html_e( 'Brak wpisów', 'przedszkole' ); ?></h2>
			<p><?php esc_html_e( 'Nie ma tu jeszcze żadnych treści.', 'przedszkole' ); ?></p>
		</div>

	<?php endif; ?>
```

Zamień na:

```php
	<?php else : ?>

		<?php
		/* Pusto z powodu cięcia po roczniku to inna sytuacja niż pusto
		   w ogóle. „Brak wpisów" kazałoby rodzicowi myśleć, że grupa nigdy
		   nic nie napisała, podczas gdy poprzedni rocznik jest o jedno
		   kliknięcie stąd. */
		$przedszkole_rok_pusty = '';
		$przedszkole_starszy   = '';

		if ( przedszkole_rok_aktywny() ) {
			$przedszkole_rok_pusty = przedszkole_rok_z_zapytania();
			$przedszkole_lata      = przedszkole_lata_szkolne();
			$przedszkole_poz       = array_search( $przedszkole_rok_pusty, $przedszkole_lata, true );

			// Lista jest malejąca, więc następny indeks to rocznik starszy.
			if ( false !== $przedszkole_poz && isset( $przedszkole_lata[ $przedszkole_poz + 1 ] ) ) {
				$przedszkole_starszy = $przedszkole_lata[ $przedszkole_poz + 1 ];
			}
		}
		?>

		<div class="notice">
			<?php if ( $przedszkole_rok_pusty ) : ?>

				<h2>
					<?php
					printf(
						/* translators: %s: rok szkolny, na przykład 2026/2027. */
						esc_html__( 'W roku szkolnym %s nie ma jeszcze wpisów', 'przedszkole' ),
						esc_html( przedszkole_rok_z_slug( $przedszkole_rok_pusty ) )
					);
					?>
				</h2>

				<?php if ( $przedszkole_starszy ) : ?>
					<p>
						<a href="<?php echo esc_url( add_query_arg( 'rok', $przedszkole_starszy ) ); ?>">
							<?php
							printf(
								/* translators: %s: rok szkolny, na przykład 2025/2026. */
								esc_html__( 'Zobacz wpisy z roku %s', 'przedszkole' ),
								esc_html( przedszkole_rok_z_slug( $przedszkole_starszy ) )
							);
							?>
						</a>
					</p>
				<?php endif; ?>

			<?php else : ?>

				<h2><?php esc_html_e( 'Brak wpisów', 'przedszkole' ); ?></h2>
				<p><?php esc_html_e( 'Nie ma tu jeszcze żadnych treści.', 'przedszkole' ); ?></p>

			<?php endif; ?>
		</div>

	<?php endif; ?>
```

- [ ] **Krok 3: Sprawdź nowy komunikat na parze znalezionej w kroku 1**

```bash
curl -s "http://przedszkole.ddev.site/category/<kategoria>/?rok=<rocznik>" | grep -A8 'class="notice"'
```

Oczekiwane: nagłówek „W roku szkolnym &lt;rocznik&gt; nie ma jeszcze wpisów"
zamiast dawnego „Brak wpisów", a pod nim odnośnik „Zobacz wpisy z roku …"
prowadzący do rocznika o rok starszego.

Dla pary `misie / 2026-2027` (najpewniej pierwszej z kroku 1) oczekiwane
dokładnie: nagłówek z `2026/2027` i odnośnik `Zobacz wpisy z roku 2025/2026`.

- [ ] **Krok 4: Sprawdź, że najstarszy rocznik nie ma odnośnika wstecz**

Najstarszy rocznik na liście nie ma poprzednika, więc odnośnik ma zniknąć —
inaczej prowadziłby donikąd. Weź najstarszy rocznik i kategorię, która jest
w nim pusta (z listy z kroku 1):

```bash
ddev exec wp --path=wp eval 'echo end( ( $l = przedszkole_lata_szkolne() ) ), "\n";'
curl -s "http://przedszkole.ddev.site/category/<kategoria>/?rok=<najstarszy>" | grep -c 'Zobacz wpisy z roku'
```

Oczekiwane: pierwsze polecenie wypisuje `2023-2024`, drugie zwraca `0`.

Jeśli żadna kategoria nie jest pusta w najstarszym roczniku, ten krok pomiń
i odnotuj to w raporcie — warunek `isset( $lata[ $poz + 1 ] )` z kroku 2
i tak go pokrywa.

- [ ] **Krok 5: Sprawdź, że wyszukiwarka ma nadal swój własny komunikat**

```bash
curl -s "http://przedszkole.ddev.site/?s=zzzxxxqqq" | grep -c 'Nic nie znaleziono'
```

Oczekiwane: `1` — `search.php` ma osobny szablon i nie powinien się zmienić.

- [ ] **Krok 6: Commit**

```bash
git add theme/przedszkole/index.php
git commit -m "feat: komunikat o pustym roczniku z przejsciem wstecz"
```

---

## Zadanie 6: Blok „3 ostatnie" dla grup i logopedy

**Pliki:**
- Nowy: `theme/przedszkole/template-parts/wpisy-kategorii.php`
- Kasowany: `theme/przedszkole/template-parts/aktualnosci-grupy.php`
- Zmiana: `theme/przedszkole/page.php`

- [ ] **Krok 1: Sprawdź stan wyjściowy na stronie grupy**

```bash
curl -s "http://przedszkole.ddev.site/grupy/misie/" | grep -o 'Aktualności grupy'
curl -s "http://przedszkole.ddev.site/grupy/misie/" | grep -o 'datetime="20[0-9][0-9]' | sort -u
```

Oczekiwane: sekcja „Aktualności grupy" jest, a daty obejmują roczniki spoza
bieżącego. Jeśli adres strony grupy jest inny, znajdź go:
`ddev exec wp --path=wp post list --post_type=page --fields=ID,post_name --format=csv | grep misie`

- [ ] **Krok 2: Utwórz `theme/przedszkole/template-parts/wpisy-kategorii.php`**

```php
<?php
/**
 * Ostatnie wpisy kategorii pod treścią strony.
 *
 * Wiążemy stronę z kategorią przez slug: strona „Misie" i kategoria „Misie"
 * mają ten sam slug, więc nie trzeba niczego łączyć ręcznie w panelu. Tak samo
 * strona „Kącik logopedy" i kategoria o slugu `logopeda`.
 *
 * Dwa zachowania, bo dwa rodzaje treści:
 *
 * * **Grupa** — wpisy są aktualnościami, więc blok pokazuje bieżący rocznik.
 *   Gdy grupa nic jeszcze nie dodała, zamiast znikać zostawia zdanie
 *   i przejście do poprzedniego rocznika. Pusty blok niesie tu informację:
 *   „jeszcze nic, stare jest tutaj".
 * * **Logopeda** — poradniki bez daty ważności. Bez cięcia po roczniku,
 *   a gdy pusto, sekcja po prostu się nie pokazuje.
 *
 * @package Przedszkole
 * @param string $args['slug'] Slug strony, ten sam co slug kategorii.
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_slug = isset( $args['slug'] ) ? (string) $args['slug'] : '';

$przedszkole_jest_grupa    = in_array( $przedszkole_slug, przedszkole_grupy(), true );
$przedszkole_jest_logopeda = PRZEDSZKOLE_LOGOPEDA === $przedszkole_slug;

if ( ! $przedszkole_jest_grupa && ! $przedszkole_jest_logopeda ) {
	return;
}

$przedszkole_kategoria = get_category_by_slug( $przedszkole_slug );

if ( ! $przedszkole_kategoria ) {
	return;
}

$przedszkole_parametry = array(
	'post_type'           => 'post',
	'cat'                 => $przedszkole_kategoria->term_id,
	'posts_per_page'      => 3,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

$przedszkole_rok     = '';
$przedszkole_starszy = '';

if ( $przedszkole_jest_grupa ) {
	/* Strona grupy nie jest listą, więc nie czytamy `?rok=` — zawsze
	   pokazujemy rocznik bieżący. */
	$przedszkole_rok = przedszkole_rok_slug( przedszkole_rok_szkolny() );

	$przedszkole_parametry['date_query'] = przedszkole_zakres_roku( $przedszkole_rok );

	$przedszkole_lata = przedszkole_lata_szkolne();
	$przedszkole_poz  = array_search( $przedszkole_rok, $przedszkole_lata, true );

	if ( false !== $przedszkole_poz && isset( $przedszkole_lata[ $przedszkole_poz + 1 ] ) ) {
		$przedszkole_starszy = $przedszkole_lata[ $przedszkole_poz + 1 ];
	}
}

$przedszkole_wpisy = new WP_Query( $przedszkole_parametry );

// Kącik logopedy bez artykułów: sekcja się nie pokazuje.
if ( ! $przedszkole_wpisy->have_posts() && $przedszkole_jest_logopeda ) {
	return;
}

$przedszkole_adres_kategorii = get_category_link( $przedszkole_kategoria );
?>

<section class="section">

	<div class="section__head">
		<h2>
			<?php
			if ( $przedszkole_jest_logopeda ) {
				esc_html_e( 'Artykuły logopedy', 'przedszkole' );
			} else {
				esc_html_e( 'Aktualności grupy', 'przedszkole' );
			}
			?>
		</h2>

		<?php if ( $przedszkole_wpisy->have_posts() ) : ?>
			<a href="<?php echo esc_url( $przedszkole_adres_kategorii ); ?>">
				<?php esc_html_e( 'Zobacz więcej', 'przedszkole' ); ?> →
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $przedszkole_wpisy->have_posts() ) : ?>

		<div class="cards">
			<?php
			while ( $przedszkole_wpisy->have_posts() ) :
				$przedszkole_wpisy->the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>

	<?php else : ?>

		<p>
			<?php
			printf(
				/* translators: %s: rok szkolny, na przykład 2026/2027. */
				esc_html__( 'Grupa nie dodała jeszcze wpisów w roku szkolnym %s.', 'przedszkole' ),
				esc_html( przedszkole_rok_z_slug( $przedszkole_rok ) )
			);
			?>
		</p>

		<?php if ( $przedszkole_starszy ) : ?>
			<p>
				<a href="<?php echo esc_url( add_query_arg( 'rok', $przedszkole_starszy, $przedszkole_adres_kategorii ) ); ?>">
					<?php
					printf(
						/* translators: %s: rok szkolny, na przykład 2025/2026. */
						esc_html__( 'Zobacz wpisy z roku %s', 'przedszkole' ),
						esc_html( przedszkole_rok_z_slug( $przedszkole_starszy ) )
					);
					?>
				</a>
			</p>
		<?php endif; ?>

	<?php endif; ?>

</section>
```

- [ ] **Krok 3: Przełącz wywołanie w `page.php`**

Znajdź:

```php
		<?php get_template_part( 'template-parts/aktualnosci-grupy', null, array( 'slug' => get_post_field( 'post_name' ) ) ); ?>
```

Zamień na:

```php
		<?php get_template_part( 'template-parts/wpisy-kategorii', null, array( 'slug' => get_post_field( 'post_name' ) ) ); ?>
```

- [ ] **Krok 4: Skasuj stary plik**

```bash
git rm theme/przedszkole/template-parts/aktualnosci-grupy.php
```

- [ ] **Krok 5: Sprawdź, że blok grupy tnie po roczniku**

```bash
curl -s "http://przedszkole.ddev.site/grupy/misie/" | grep -o 'datetime="20[0-9][0-9]' | sort -u
```

Oczekiwane: wyłącznie daty z bieżącego rocznika (2026), albo — jeśli grupa nic
jeszcze nie dodała — brak dat i komunikat z kroku 6.

- [ ] **Krok 6: Sprawdź komunikat pustej grupy**

Na dzień pisania planu **żadna z sześciu grup nie ma wpisu w roczniku
2026/2027**, więc komunikat zobaczysz na każdej stronie grupy. Gdyby w międzyczasie
któraś coś dodała, pustą znajdziesz poleceniem z Zadania 5, krok 1.

Na stronie pustej grupy oczekiwane:

```bash
curl -s "http://przedszkole.ddev.site/grupy/<slug>/" | grep -o 'Grupa nie dodała jeszcze wpisów w roku szkolnym [0-9/]*'
curl -s "http://przedszkole.ddev.site/grupy/<slug>/" | grep -o 'Zobacz wpisy z roku [0-9/]*'
```

Oczekiwane: obie linie obecne, a odnośnik prowadzi na adres kategorii z `?rok=`.

- [ ] **Krok 7: Sprawdź, że strona logopedy nie wywala sekcji przy pustej kategorii**

```bash
curl -s "http://przedszkole.ddev.site/dla-rodzicow/logopeda/" | grep -c 'Artykuły logopedy'
```

Oczekiwane: `0` — kategoria jest jeszcze pusta, więc sekcja się nie pokazuje.
Jeśli adres strony jest inny, znajdź go przez
`ddev exec wp --path=wp post list --post_type=page --name=logopeda --field=url --format=csv`.

- [ ] **Krok 8: Sprawdź, że nic nie odwołuje się już do starej nazwy**

```bash
grep -rn "aktualnosci-grupy" theme/ tools/ *.md
```

Oczekiwane: brak trafień w `theme/` i `tools/`. Trafienia w `PLAN.md`
poprawisz w Zadaniu 9.

- [ ] **Krok 9: Commit**

```bash
git add theme/przedszkole/template-parts/wpisy-kategorii.php theme/przedszkole/page.php
git commit -m "feat: blok ostatnich wpisow obsluguje grupy i logopede"
```

---

## Zadanie 7: `noindex` na adresach z rocznikiem

**Pliki:**
- Zmiana: `theme/przedszkole/inc/seo.php`

- [ ] **Krok 1: Sprawdź, że archiwum rocznika jest dziś indeksowalne**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/?rok=2024-2025" | grep -c 'name="robots"'
```

Oczekiwane: `0` — rdzeń nie wystawia `robots` na archiwach.

- [ ] **Krok 2: Dopisz filtr na końcu `inc/seo.php`**

```php
/**
 * Archiwa rocznikowe poza indeksem wyszukiwarek.
 *
 * Bez tego siedem kategorii razy cztery roczniki daje 28 list, z których każda
 * powiela zawartość którejś innej. Rdzeń nie wystawia `rel=canonical`
 * na archiwach, więc nie ma czym tego rozstrzygnąć — prościej nie wpuszczać
 * ich do indeksu wcale.
 *
 * `follow`, nie `nofollow`: wyszukiwarka ma dalej chodzić po odnośnikach
 * do wpisów. Same wpisy siedzą w mapie witryny osobno i pozostają indeksowalne.
 *
 * Dotyczy też rocznika bieżącego — on również powiela adres bez parametru.
 *
 * @param array $roboty Dyrektywy dla robotów.
 * @return array
 */
function przedszkole_roboty_rocznik( $roboty ) {
	// Odczyt publicznego filtra listy, bez zmiany stanu — nonce nie ma tu sensu.
	if ( isset( $_GET['rok'] ) ) {
		unset( $roboty['nofollow'] );
		$roboty['noindex'] = true;
		$roboty['follow']  = true;
	}

	return $roboty;
}
add_filter( 'wp_robots', 'przedszkole_roboty_rocznik' );
```

- [ ] **Krok 3: Sprawdź, że dyrektywa się pojawiła**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/?rok=2024-2025" | grep -o '<meta name="robots"[^>]*>'
```

Oczekiwane: `<meta name="robots" content="noindex, follow" />`

- [ ] **Krok 4: Sprawdź, że adres bez parametru pozostał indeksowalny**

```bash
curl -s "http://przedszkole.ddev.site/category/misie/" | grep -c 'noindex'
curl -s "http://przedszkole.ddev.site/" | grep -c 'noindex'
```

Oczekiwane: `0` i `0`.

- [ ] **Krok 5: Sprawdź, że wyszukiwarka nie straciła swojego `noindex` z rdzenia**

```bash
curl -s "http://przedszkole.ddev.site/?s=dzieci" | grep -o '<meta name="robots"[^>]*>'
```

Oczekiwane: zawiera `noindex` — rdzeń robi to sam i nasz filtr tego nie zepsuł.

- [ ] **Krok 6: Commit**

```bash
git add theme/przedszkole/inc/seo.php
git commit -m "feat: archiwa rocznikowe poza indeksem wyszukiwarek"
```

---

## Zadanie 8: Import artykułów logopedki ze zrzutu Joomli

Przeczytaj najpierw sekcję „Korekta wobec specu" na górze planu — liczby
w kryteriach odbioru pochodzą stamtąd.

**Pliki:**
- Nowy: `tools/migracja_logopedy.py`
- Zmiana: `.gitignore` (wynik pośredni)

- [ ] **Krok 1: Sprawdź, że baza `joomla` stoi i ma dane**

```bash
ddev mysql -uroot -proot joomla -e "SELECT COUNT(*) FROM l6hwz_content WHERE catid=19 AND state=1;"
```

Oczekiwane: `27`. Jeśli bazy nie ma, odtwórz ją ze zrzutu w katalogu projektu:

```bash
ddev mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS joomla;"
gunzip -c icrdslom_dbj34_1789116422.sql.gz | ddev mysql -uroot -proot joomla
```

- [ ] **Krok 2: Sprawdź, że kategoria jest pusta, a cztery wpisy siedzą w Ogłoszeniach**

```bash
ddev exec wp --path=wp term list category --slug=logopeda --fields=slug,count --format=csv
for id in 333 331 592 591; do ddev exec wp --path=wp post term list $id category --fields=slug --format=csv </dev/null | tail -1; done
```

Oczekiwane: `logopeda,0` oraz cztery razy `ogloszenia`.

Jeśli identyfikatory 333/331/592/591 nie istnieją w tej instalacji, znajdź je
po meta `_joomla_id`:

```bash
for jid in 1897 1899 1613 1614; do ddev exec wp --path=wp post list --post_type=post --meta_key=_joomla_id --meta_value=$jid --fields=ID,post_title --format=csv </dev/null | tail -1; done
```

- [ ] **Krok 3: Sprawdź, czy ktoś ręcznie edytował te cztery wpisy**

```bash
ddev exec wp --path=wp post list --post_type=post --meta_key=_joomla_id --fields=ID,post_date,post_modified --format=csv | head -20
```

Import nadpisze ich tytuł, slug i treść. Jeśli `post_modified` znacząco
odbiega od daty migracji, zatrzymaj się i zapytaj, zanim uruchomisz krok 6.

- [ ] **Krok 4: Utwórz `tools/migracja_logopedy.py`**

```python
#!/usr/bin/env python3
"""Import artykułów logopedki ze zrzutu Joomli do kategorii `logopeda`.

Kategoria 19 Joomli („Logopeda") ma 27 opublikowanych artykułów z lat
2015–2026. Migracja z Etapu 2b obejmowała tylko lata szkolne 2023/24–2025/26,
więc do WordPressa weszły cztery — resztę trzeba dobrać teraz.

Czego tu NIE ma i dlaczego:

* **Artykułu 32** („Godziny pracy logopedy", 2015). Podaje poniedziałek
  10:40–14:40, podczas gdy strona „Kącik logopedy" mówi 12:00–16:00. Import
  postawiłby na stronie dwie sprzeczne wersje tej samej informacji.
* **Kategorii 64** („Archiwum Logopedy"). Szesnaście odcinków serii
  #zostańwdomu z lockdownu. Kategoria była na starej stronie **niepublikowana**
  (`published = 0`) — przedszkole samo ją schowało po pandemii.
* **Artykułów w koszu** (`state = -2`, cztery sztuki z marca 2020).

Osobnego kroku „przepnij cztery istniejące wpisy" nie ma, bo nie jest
potrzebny: importer rozpoznaje wpis po meta `_joomla_id` i robi
`wp_set_post_categories( ..., false )`, czyli zastępuje kategorie. Cztery
wpisy siedzące dziś w `Ogłoszeniach` przejdą na `logopeda` w tym samym
przebiegu.

Skanowanie treści i konwersję na bloki bierzemy z `migracja_wpisow.py` —
stara strona była zaatakowana, więc żaden artykuł nie idzie do bazy bez
przejścia przez `skanuj()`.

Użycie:
    python3 tools/migracja_logopedy.py               # buduje plan, nic nie wgrywa
    python3 tools/migracja_logopedy.py --zastosuj    # wgrywa do WordPressa
"""

import html
import json
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from migracja_tresci import sql, tekst  # noqa: E402
from migracja_wpisow import skanuj, tresc_wpisu, zastosuj  # noqa: E402

PROJEKT = Path(__file__).resolve().parent.parent
WYNIK = PROJEKT / 'tools' / 'migracja_logopedy.json'

KATEGORIA_JOOMLI = 19
KATEGORIA = 'logopeda'
AUTOR = 'przedszkole'

# Godziny pracy logopedy z 2015 roku — aktualne stoją w treści strony.
POMIJANE = {32}


def artykuly():
	"""Opublikowane artykuły kategorii 19, najnowsze pierwsze."""
	# Znaki końca linii i tabulatory z edytorów windowsowych rozjechałyby
	# podział wierszy wyniku — spłaszczamy je w SQL-u.
	plaska = "REPLACE(REPLACE(REPLACE({}, '\\r', ' '), '\\n', ' '), '\\t', ' ')"
	return sql(
		'SELECT a.id, a.alias, a.created, '
		+ plaska.format('a.title') + ', '
		+ plaska.format('CONCAT(a.introtext, a.`fulltext`)') + ' '
		'FROM l6hwz_content a '
		f'WHERE a.state = 1 AND a.catid = {KATEGORIA_JOOMLI} '
		'ORDER BY a.created DESC'
	)


def zbuduj():
	"""Plan importu + raport z tego, co odpadło."""
	wpisy, pominiete, podejrzane = [], [], []
	wyciete_obrazki = 0

	for wiersz in artykuly():
		wiersz += [''] * (5 - len(wiersz))
		jid, alias, created, tytul, kod = wiersz[:5]
		jid = int(jid)

		if jid in POMIJANE:
			pominiete.append((jid, tytul, 'pominiety swiadomie'))
			continue

		blokady, ostrzezenia = skanuj(kod)
		if blokady:
			podejrzane.append((jid, tytul, 'POMINIETY', blokady))
			continue
		if ostrzezenia:
			podejrzane.append((jid, tytul, 'zaimportowany', ostrzezenia))

		if not tekst(kod):
			pominiete.append((jid, tytul, 'sama tresc nietekstowa'))
			continue

		wyciete_obrazki += len(re.findall(r'<img\b', kod, flags=re.I))
		tresc, _albumy = tresc_wpisu(kod)

		if not tresc.strip():
			pominiete.append((jid, tytul, 'pusto po wycieciu obrazkow'))
			continue

		wpisy.append({
			'joomla_id': jid,
			'tytul': html.unescape(tytul).strip(),
			'slug': alias,
			'data_utc': created,
			'kategoria': KATEGORIA,
			'autor': AUTOR,
			'albumy': [],
			'tresc': tresc,
		})

	return {
		'wpisy': wpisy,
		'pominiete': pominiete,
		'podejrzane': podejrzane,
		'wyciete_obrazki': wyciete_obrazki,
	}


def raport(plan):
	print(f'Wpisów do importu: {len(plan["wpisy"])}')
	print(f'Wyciętych znaczników <img>: {plan["wyciete_obrazki"]}')

	if plan['pominiete']:
		print('\nPominięte:')
		for jid, tytul, powod in plan['pominiete']:
			print(f'  {jid}  „{tytul}" — {powod}')

	if plan['podejrzane']:
		print('\nZgłoszone przez skaner:')
		for jid, tytul, stan, powody in plan['podejrzane']:
			print(f'  {jid}  „{tytul}" [{stan}] — {"; ".join(powody)}')


if __name__ == '__main__':
	plan = zbuduj()
	WYNIK.write_text(json.dumps(plan['wpisy'], ensure_ascii=False, indent=1), encoding='utf-8')
	raport(plan)
	print(f'\nPlan → {WYNIK.relative_to(PROJEKT)}')

	if '--zastosuj' in sys.argv:
		print('\n--- wgrywanie do WordPressa ---')
		zastosuj(plan['wpisy'])
```

- [ ] **Krok 5: Zbuduj plan bez wgrywania i przeczytaj raport**

```bash
python3 tools/migracja_logopedy.py
```

Oczekiwane:
- `Wpisów do importu: 25`
- `Wyciętych znaczników <img>: 2`
- wśród pominiętych **32** („pominiety swiadomie") i **1224**
  („pusto po wycieciu obrazkow")
- sekcja „Zgłoszone przez skaner" pusta albo wyłącznie ze stanem
  `zaimportowany`; jakikolwiek `POMINIETY` z tej sekcji zatrzymaj i zgłoś

Jeśli liczba jest inna niż 25, **nie wgrywaj** — najpierw ustal dlaczego.

- [ ] **Krok 6: Wgraj do WordPressa**

```bash
python3 tools/migracja_logopedy.py --zastosuj
```

Oczekiwane w podsumowaniu: `nowe: 21   zaktualizowane: 4   bledy: 0`

- [ ] **Krok 7: Sprawdź liczbę i rozrzut dat**

```bash
ddev exec wp --path=wp term list category --slug=logopeda --fields=slug,count --format=csv
ddev exec wp --path=wp post list --post_type=post --category_name=logopeda --fields=post_date --format=csv | cut -d- -f1 | sort -u
```

Oczekiwane: `logopeda,25` oraz lata rozłożone od 2015 do 2026 — nie zbite
w dniu importu.

- [ ] **Krok 8: Sprawdź, że cztery wpisy opuściły Ogłoszenia**

```bash
for jid in 1897 1899 1613 1614; do
  ID=$(ddev exec wp --path=wp post list --post_type=post --meta_key=_joomla_id --meta_value=$jid --field=ID --format=csv </dev/null | tail -1)
  printf "%s -> " "$jid"
  ddev exec wp --path=wp post term list "$ID" category --fields=slug --format=csv </dev/null | tail -1
done
```

Oczekiwane: cztery razy `logopeda`, ani razu `ogloszenia`.

- [ ] **Krok 9: Sprawdź, że kategoria nie podlega cięciu i nie ma przełącznika**

```bash
curl -s "http://przedszkole.ddev.site/category/logopeda/" | grep -o 'datetime="20[0-9][0-9]' | sort -u
curl -s "http://przedszkole.ddev.site/category/logopeda/" | grep -c 'name="rok"'
```

Oczekiwane: daty z wielu lat (nie tylko 2026) oraz `0` dla przełącznika.

- [ ] **Krok 10: Sprawdź, że strona „Kącik logopedy" pokazuje teraz artykuły**

```bash
curl -s "http://przedszkole.ddev.site/dla-rodzicow/logopeda/" | grep -c 'Artykuły logopedy'
```

Oczekiwane: `1`.

- [ ] **Krok 11: Dopisz wynik pośredni do `.gitignore`**

W sekcji „Wyniki posrednie migracji" dopisz linię:

```
tools/migracja_logopedy.json
```

- [ ] **Krok 12: Commit**

```bash
git add tools/migracja_logopedy.py .gitignore
git commit -m "feat: import artykulow logopedki ze zrzutu Joomli"
```

---

## Zadanie 9: Wersja motywu i dokumentacja

**Pliki:**
- Zmiana: `theme/przedszkole/style.css` (nagłówek `Version:`)
- Zmiana: `theme/przedszkole/functions.php` (`PRZEDSZKOLE_VERSION`)
- Zmiana: `PLAN.md`, `MIGRACJA.md`, `tools/README.md`

- [ ] **Krok 1: Odczytaj obie wersje i sprawdź, że są zgodne**

```bash
grep -n "^Version:" theme/przedszkole/style.css
grep -n "PRZEDSZKOLE_VERSION" theme/przedszkole/functions.php
```

Muszą pokazywać ten sam numer. Jeśli już się rozjeżdżają, wyrównaj je,
zanim podbijesz — CLAUDE.md, pułapka 6.

- [ ] **Krok 2: Podbij obie naraz**

Podnieś wersję pomocniczą (na przykład `1.4.0` → `1.5.0`) w **obu** miejscach.
Wzorce bloków są cache'owane pod wersją motywu, a my zmieniliśmy CSS i szablony.

- [ ] **Krok 3: Sprawdź, że front wstaje bez błędów PHP**

```bash
ddev exec wp --path=wp eval 'echo PRZEDSZKOLE_VERSION, "\n";'
curl -s -o /dev/null -w "%{http_code}\n" "http://przedszkole.ddev.site/"
curl -s -o /dev/null -w "%{http_code}\n" "http://przedszkole.ddev.site/aktualnosci/"
curl -s -o /dev/null -w "%{http_code}\n" "http://przedszkole.ddev.site/category/logopeda/"
curl -s "http://przedszkole.ddev.site/aktualnosci/" | grep -ci "fatal error\|warning:"
```

Oczekiwane: nowy numer wersji, trzy razy `200`, i `0` błędów.

- [ ] **Krok 4: Dopisz decyzje w `PLAN.md`**

W tabeli rejestru decyzji dopisz wiersze z datą `2026-09-16`:

```markdown
| 2026-09-16 | Listy aktualności i archiwa kategorii tną się do bieżącego roku szkolnego | 430 wpisów w jednej paginowanej liście zasłaniało bieżące; rok szkolny to naturalna jednostka dla przedszkola |
| 2026-09-16 | Rok szkolny nadal liczony z daty publikacji, bez taksonomii | decyzja z 2026-09-11 zostaje w mocy; dokładamy do niej filtrowanie, nie zastępujemy jej terminami. Przeniesienie wpisu = zmiana daty, co WP już umie |
| 2026-09-16 | Rocznik w adresie jako `?rok=`, bez reguł przepisania | działa od pierwszej minuty, a `paginate_links()` sam scala parametr do odnośników stron |
| 2026-09-16 | Strona główna nie tnie bloku aktualności, strony grup tną | strona główna nigdy nie ma być pusta; na stronie grupy pusty blok z odnośnikiem wstecz niesie informację |
| 2026-09-16 | Kategoria `logopeda` wyjęta spod podziału na roczniki | poradniki nie mają daty ważności — „Rozwój mowy dziecka" z 2016 jest tak samo aktualny jak wpis z wczoraj |
| 2026-09-16 | Archiwa rocznikowe `noindex, follow` | 7 kategorii × 4 roczniki to 28 list duplikatów, a rdzeń nie wystawia `rel=canonical` na archiwach |
| 2026-09-16 | Artykuł 32 i kategoria 64 Joomli pominięte przy imporcie | art. 32 to godziny pracy z 2015 sprzeczne z treścią strony; kategoria 64 to seria covidowa, którą przedszkole samo wyłączyło |
```

W tabeli stanu etapów odnotuj zmianę tam, gdzie opisany jest Etap 7 (frontend)
albo dopisz wiersz o tej zmianie — dopasuj do istniejącej konwencji pliku.

Popraw też wzmiankę o `aktualnosci-grupy.php`, jeśli plik jest wymieniony
w drzewku szablonów (`grep -n "aktualnosci-grupy" PLAN.md`).

- [ ] **Krok 5: Popraw bilans w `MIGRACJA.md`**

W tabeli „Rozkład 440 migrowanych artykułów wg gałęzi Joomli" wiersz
„Kącik Logopedy | 4" jest zaniżony — to była liczba artykułów widocznych
w menu, nie w kategorii. Dopisz pod tabelą:

```markdown
**Korekta 2026-09-16.** Kategoria 19 („Logopeda") miała w zrzucie **27
opublikowanych artykułów** z lat 2015–2026, nie 4. Do WordPressa weszły
wtedy tylko cztery, bo migracja obejmowała lata szkolne 2023/24–2025/26.
Pozostałe dobrał `tools/migracja_logopedy.py`. Ostatecznie w kategorii
`logopeda` stoi **25 wpisów**: 27 minus artykuł 32 (godziny pracy z 2015,
sprzeczne z treścią strony „Kącik logopedy") i minus artykuł 1224 (sam
obrazek, który przepadł ze starym serwerem). Kategoria 64 („Archiwum
Logopedy", 16 odcinków serii #zostańwdomu) pominięta świadomie — na starej
stronie była niepublikowana.
```

- [ ] **Krok 6: Opisz nowe narzędzia w `tools/README.md`**

Dopisz dwie sekcje w konwencji pliku:

````markdown
## `logopeda.sh`

Zakłada kategorię „Kącik logopedy" (slug `logopeda`) i dopasowuje do niej slug
strony o tej samej nazwie. Motyw wiąże stronę z kategorią po slugu, tak samo
jak strony grup, więc oba muszą być identyczne.

```bash
tools/logopeda.sh
```

Idempotentny: istniejącej kategorii nie zakłada drugi raz.

## `migracja_logopedy.py`

Dobiera ze zrzutu Joomli artykuły z kategorii 19 („Logopeda"), których nie
objęła migracja z Etapu 2b — obejmowała tylko lata szkolne 2023/24–2025/26,
a kącik logopedy sięga 2015 roku.

```bash
python3 tools/migracja_logopedy.py              # plan, nic nie wgrywa
python3 tools/migracja_logopedy.py --zastosuj   # wgrywa do WordPressa
```

Wymaga bazy roboczej `joomla` — tej samej, z której korzystają
`migracja_tresci.py` i `migracja_wpisow.py`. Idempotentny: wpisy rozpoznaje
po meta `_joomla_id`, więc powtórny przebieg aktualizuje, a nie duplikuje.

Pomija świadomie artykuł 32 (godziny pracy z 2015, sprzeczne z treścią strony)
i całą kategorię 64 („Archiwum Logopedy" — seria covidowa, na starej stronie
niepublikowana).
````

- [ ] **Krok 7: Commit**

```bash
git add theme/przedszkole/style.css theme/przedszkole/functions.php PLAN.md MIGRACJA.md tools/README.md
git commit -m "docs: rejestr decyzji, bilans migracji i opis narzedzi"
```

---

## Odbiór całości

Po ostatnim zadaniu przejdź całą listę. Każdy punkt musi przejść.

- [ ] `/aktualnosci/` pokazuje wyłącznie bieżący rocznik
- [ ] `/aktualnosci/?rok=2024-2025` pokazuje wyłącznie rocznik 2024/2025
- [ ] paginacja na `?rok=` nie gubi parametru
- [ ] `/kategoria/misie/` tnie się, `/kategoria/logopeda/` nie
- [ ] przełącznik ma widoczną etykietę `<label for="rok">` i przycisk `submit`
- [ ] pusty rocznik daje komunikat z nazwą rocznika i odnośnikiem wstecz
- [ ] strona grupy bez wpisów w bieżącym roczniku pokazuje komunikat, nie pustkę
- [ ] strona główna pokazuje 3 najnowsze ogłoszenia niezależnie od rocznika
- [ ] strona „Kącik logopedy" pokazuje sekcję „Artykuły logopedy"
- [ ] kategoria `logopeda` ma **25** wpisów, daty rozłożone 2015–2026
- [ ] żaden z 25 wpisów nie został w `Ogłoszeniach`
- [ ] `?rok=` zwraca `<meta name="robots" content="noindex, follow" />`
- [ ] adresy bez `?rok=` nie mają `noindex`
- [ ] wyszukiwarka przeszukuje wszystkie roczniki i nie ma przełącznika
- [ ] `Version:` w `style.css` i `PRZEDSZKOLE_VERSION` mają ten sam, podbity numer
- [ ] `grep -rn "aktualnosci-grupy" theme/ tools/` nie zwraca nic
- [ ] panel działa bez zmian: dodanie wpisu nie wymaga wyboru rocznika
- [ ] `ddev mysql -uroot -proot -e "DROP DATABASE joomla_tmp;"` — jeśli baza
      robocza gdzieś została, skasuj ją; zrzut zawiera dane osobowe

## Poza zakresem tego planu

Przekierowania 301 ze starych adresów Joomli (Etap 9), odświeżenie godzin
pracy logopedy na stronie, wgranie zdjęć do dwóch artykułów, których obrazki
przepadły ze starym serwerem.
