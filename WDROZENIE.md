# Wdrożenie na produkcję — cyber_Folks

Instrukcja wykonawcza do Etapu 9 z [PLAN.md](PLAN.md). PLAN mówi **co** ma być
zrobione, ten plik **jak** — komenda po komendzie, w kolejności, z pułapkami,
na które już wpadliśmy lokalnie.

**Domena:** `przedszkoleslomniki.pl`
**Katalog na serwerze:** `/home/icrdslom/domains/przedszkoleslomniki.pl/public_html/`
**Panel:** cyber_Folks (FTP na pewno, SSH zależnie od pakietu — patrz krok 1)

---

## Stan wyjściowy (zweryfikowany 2026-09-17)

| | |
|---|---|
| WordPress | 7.1 |
| PHP lokalnie | 8.5 |
| Baza lokalnie | MariaDB 11.8, ~7,5 MB |
| Prefiks tabel | `wp_` |
| Wtyczki | **zero** (`wp-content/plugins` pusty poza `index.php`) |
| Motyw | `przedszkole` 0.32.0, symlink → `theme/przedszkole` |
| Motyw zapasowy | `twentytwentyfive` (zostaje — awaryjny powrót przy błędzie w motywie) |
| Uploady | 44 pliki, 3,8 MB — 8 załączników w bibliotece, wszystkie przenosimy |
| Konta | 10 (`dev`, `przedszkole`, 6× grupa, `intendent`, `pedagog`) |
| Serwer lokalnie | **nginx**, na produkcji **Apache** — `.htaccess` z kroku 7 nie był testowany lokalnie |

Strefa czasowa `Europe/Warsaw`, permalinki `/%postname%/`, `blog_public = 1` —
wszystko jedzie w bazie, nie trzeba ustawiać ręcznie.

---

## Kolejność

```
1. Panel hostingu        (nic nie kasuje)
2. Dług treściowy        (lokalnie, przed eksportem)
3. Usunięcie starej strony  ← NIEODWRACALNE
4. Eksport bazy z podmianą adresów
5. Wgranie plików
6. wp-config.php
7. .htaccess
8. Import bazy
9. Konta i sprzątanie na produkcji
10. Cron
11. Testy odbiorcze
12. Po starcie
```

Kroki 1–2 da się zrobić dowolnego dnia. Od kroku 3 idziemy jednym ciągiem —
między usunięciem starej strony a działającą nową jest okno, w którym domena
nie oddaje niczego sensownego.

---

## 1. Panel hostingu

Nic jeszcze nie kasujemy.

- [ ] PHP → **8.5** (MultiPHP)
- [ ] `upload_max_filesize` ≥ **16M**, `post_max_size` ≥ `upload_max_filesize`
- [ ] `max_execution_time` — sprawdzić, minimum 60 s
- [ ] SSL Let's Encrypt → włączony, **wymuszony HTTPS**
- [ ] Nowa baza MySQL + użytkownik tylko do niej (zapisać dane — wchodzą do `wp-config.php`)
- [ ] Sprawdzić wersję MySQL/MariaDB — poniżej 10.4 import z MariaDB 11.8 potrafi
      się wyłożyć na kolacji `utf8mb4_uca1400_*`; wtedy przy eksporcie
      (krok 4) dodać `--default-character-set=utf8mb4` i podmienić kolację w zrzucie
- [ ] Sprawdzić, czy pakiet daje **SSH** — bez niego kroki z `wp-cli` i `find`
      odpadają, zostaje FTP + phpMyAdmin (ścieżki awaryjne opisane przy każdym kroku)
- [ ] Sprawdzić **cron** w panelu (krok 10)
- [ ] Sprawdzić zasady backupu hostingu: 1×/24h, retencja 28 dni — i **jak się odtwarza**

## 2. Dług treściowy — lokalnie, przed eksportem

Baza lokalna jest źródłem. Co zostanie w niej teraz, pojedzie na produkcję.

**Biblioteka mediów — zamknięta 2026-09-17.** Zostało 8 załączników i wszystkie
jadą na produkcję: logo (258, 1240), ikona strony (1215), trzy dokumenty
(statut, ubezpieczenie, klauzula informacyjna) oraz dwa zdjęcia wyróżniające
wpisów 267 i 268. Testowe `rene-porter-…` i `sample.pdf` usunięte razem
z plikami.

Kontrola przed eksportem — lista ma mieć 8 pozycji i żadnej nieznajomej:

```bash
ddev exec wp --path=wp post list --post_type=attachment --fields=ID,post_title,post_parent --format=csv
```

**Kosz — opróżniony 2026-09-17** (cztery wpisy testowe: „Test", „ddfdfs", pusty
wpis, automatyczny szkic strony). Kosz jedzie w bazie i widzi go redaktor, więc
przed eksportem sprawdzić, że nic nie doszło:

```bash
ddev exec wp --path=wp post list --post_status=trash --post_type=any --fields=ID,post_title --format=csv
ddev exec wp --path=wp post delete <ID> --force   # pojedynczo, nieodwracalnie
```

`wp site empty` **nie** — ta komenda czyści całą treść, nie kosz.

**Szkice.** `Kącik pedagoga` (1322) i wpis `Adaptacja w przedszkolu…` (1325)
czekają na akceptację przedszkola. Treść jest **wygenerowana, nie migrowana** —
godziny pracy pedagoga są zmyślone. Do decyzji przed wdrożeniem:

- akceptacja → opublikować oba i dodać pozycję menu pod „Dla rodziców",
  za „Kącikiem logopedy",
- brak akceptacji → usunąć.

Zostawienie ich jako szkiców też jest poprawne: szkic jest niewidoczny,
a strona stoi poza menu.

**Reszta długu (nie blokuje startu, do ustalenia z przedszkolem):**
- 7 wpisów, które na starej stronie były zajawką dla PDF-a, i 11 bez dokumentu
  — listy w [MIGRACJA.md](MIGRACJA.md)
- zdjęcia kadry — 14 kafelków ma inicjały; to poprawny stan końcowy do czasu,
  aż będą zdjęcia **wraz ze zgodami**
- potwierdzić dane w stopce: adres, telefon `510 217 005`,
  `sekretariat@przedszkoleslomniki.pl`, godziny 6:30–17:00

**Skrzynki e-mail.** Przed krokiem 9 muszą istnieć realne konta pocztowe dla
kont grupowych, `intendent@` i `pedagog@` — bez nich nie działa odzyskiwanie
hasła. Hosting pokazuje 4 użyte konta, brakuje ośmiu.

## 3. Usunięcie starej strony

> **Operacja nieodwracalna.** Na koncie FTP stoi stara strona: 5,5 GB, 56 tys.
> plików. Po skasowaniu nie ma jej skąd odzyskać — hosting już wyłączył witrynę
> i FTP jest jedynym dostępem do tych plików. Nie kasuj niczego, dopóki wszystkie
> punkty poniżej nie są odhaczone.

- [ ] **Pełny backup katalogu przez FTP na dysk lokalny** — całość, nie wybiórczo
- [ ] **Eksport obu baz** przez phpMyAdmin (Eksport → SQL); ustalić, która należy
      do starej strony
- [ ] Przegląd zawartości pod kątem materiałów do odzyskania: logo i grafiki,
      zdjęcia z galerii, PDF-y, teksty
- [ ] **Pisemne potwierdzenie klienta**, że stara strona nie jest już potrzebna
- [ ] Sprawdzone, że backup hostingu obejmuje ten katalog i wiadomo, jak go odtworzyć
- [ ] Backup lokalny przetestowany — rozpakowany, otwarty, nie jest pustym archiwum

Dopiero teraz: usunięcie plików i **nieużywanej** bazy starej strony.

Uwaga: zrzut `icrdslom_dbj34_*.sql.gz` w katalogu projektu to baza Joomli
z migracji. Jest jedynym archiwum 1313 starych artykułów spoza zakresu migracji.
Nie kasować, nie wrzucać do repo (dane osobowe, hasła).

## 4. Eksport bazy z podmianą adresów

Podmieniamy adresy **przy eksporcie**, nie w lokalnej bazie — lokalna zostaje
sprawna do dalszej pracy. `search-replace` rozumie dane serializowane; ręczne
`sed` po zrzucie je psuje.

```bash
ddev exec wp --path=wp search-replace 'http://przedszkole.ddev.site' 'https://przedszkoleslomniki.pl' \
  --all-tables --export=/var/www/html/produkcja.sql
```

Ścieżka `--export` jest **wewnątrz kontenera**; plik wyląduje w katalogu
projektu jako `produkcja.sql` (jest w `.gitignore` przez wzorzec `*.sql`).

Kontrola — w zrzucie nie może zostać ani jedno `ddev.site`:

```bash
grep -c 'ddev.site' produkcja.sql    # oczekiwane: 0
gzip produkcja.sql
```

**Prefiks tabel zostaje `wp_`.** PLAN.md dopuszczał zmianę, ale zmiana prefiksu
po eksporcie to nie tylko nazwy tabel: trzeba podmienić klucz opcji
`wp_user_roles` i metadane `wp_capabilities` / `wp_user_level` przy każdym koncie.
Trzy miejsca do zapomnienia w zamian za utrudnienie botowi, który i tak celuje
w `wp-login.php` — to zabezpieczenie przez zaciemnienie, odrzucone w Etapie 8.3
razem z ukrywaniem adresu panelu.

## 5. Wgranie plików

FTP do `/home/icrdslom/domains/przedszkoleslomniki.pl/public_html/`.

Wgrywamy zawartość katalogu `wp/` **z wyjątkiem**:

- `wp-config.php` — powstaje na serwerze od nowa (krok 6)
- `wp-content/debug.log` — lokalny log diagnostyczny
- `wp-content/themes/przedszkole` — symlink; **na serwer idzie zwykły katalog**

Motyw:

```bash
# lokalnie, spakowanie motywu jako zwykłego katalogu
tar czf motyw.tar.gz -C theme przedszkole
```

Rozpakować w `wp-content/themes/`. Bez SSH: wgrać zawartość
`theme/przedszkole/` przez FTP do `wp-content/themes/przedszkole/`.
**Symlink wgrany przez FTP nie zadziała** — klient FTP wyśle albo pusty plik,
albo pójdzie za dowiązaniem; katalog musi być realny.

Po wgraniu sprawdzić, że są `wp-content/themes/przedszkole/style.css`
i `functions.php`, a `wp-content/plugins/` jest pusty poza `index.php`.

## 6. `wp-config.php` produkcyjny

Nowy plik, pisany na serwerze. **Nie kopiować lokalnego** — ma dane DDEV-a
i `WP_DEBUG` włączony.

Klucze (salts) generujemy świeże: https://api.wordpress.org/secret-key/1.1/salt/

```php
<?php
define( 'DB_NAME',     'nazwa_bazy' );
define( 'DB_USER',     'uzytkownik' );
define( 'DB_PASSWORD', 'haslo' );
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

// TU wkleić 8 linii z generatora salt.

$table_prefix = 'wp_';

define( 'WP_DEBUG',         false );
define( 'WP_DEBUG_LOG',     false );
define( 'WP_DEBUG_DISPLAY', false );

define( 'DISALLOW_FILE_EDIT', true );   // dublet wobec motywu, celowo
define( 'DISABLE_WP_CRON',    true );   // cron systemowy — krok 10
define( 'WP_MEMORY_LIMIT',    '256M' );
define( 'FS_METHOD',          'direct' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
```

`DISALLOW_FILE_EDIT` siedzi już w motywie (Etap 8.3) — tu jedzie drugi raz,
bo to kanoniczne miejsce i nic nie kosztuje.

`WP_HOME` / `WP_SITEURL` **nie** ustawiamy: adresy wchodzą z bazy (krok 4),
a stałe w configu blokują późniejszą zmianę z panelu.

## 7. `.htaccess`

Lokalnie stoi nginx, więc te reguły idą na produkcję nieprzetestowane —
po wklejeniu każdego bloku odświeżyć stronę główną. Pusta biała strona albo
błąd 500 = literówka w bloku, który właśnie wszedł.

**Główny `.htaccess`.** Blok WordPressa wygeneruje sam rdzeń po zapisaniu
permalinków (krok 9), ale można go wkleić od razu:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

Reszta **poza** blokiem `# BEGIN WordPress` — rdzeń nadpisuje tamten fragment
przy każdej zmianie ustawień permalinków i zabrałby ze sobą nasze reguły:

```apache
# Kompresja tekstu.
<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE text/html text/css text/plain text/xml \
		application/javascript application/json image/svg+xml
</IfModule>

# Cache przegladarki. Zasoby z numerem wersji w adresie moga lezec dlugo,
# HTML nie lezy wcale.
<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresByType text/css            "access plus 1 year"
	ExpiresByType application/javascript "access plus 1 year"
	ExpiresByType image/webp          "access plus 1 year"
	ExpiresByType image/png           "access plus 1 year"
	ExpiresByType image/jpeg          "access plus 1 year"
	ExpiresByType font/woff2          "access plus 1 year"
	ExpiresByType text/html           "access plus 0 seconds"
</IfModule>

# Pliki, ktore nie sa trescia.
<FilesMatch "^(wp-config\.php|\.htaccess|readme\.html|license\.txt|xmlrpc\.php)$">
	Require all denied
</FilesMatch>
```

**`wp-content/uploads/.htaccess`** — osobny plik. Gdyby ktoś wgrał tam kod,
serwer odda go jako tekst zamiast uruchomić:

```apache
<FilesMatch "\.(?i:php|phtml|phar)$">
	Require all denied
</FilesMatch>
```

## 8. Import bazy

phpMyAdmin → wybrana baza → Import → `produkcja.sql.gz`. Przy limicie rozmiaru
w panelu wgrać rozpakowany plik (7,5 MB mieści się w typowym limicie 50 MB).

Ze SSH:

```bash
gunzip < produkcja.sql.gz | mysql -u uzytkownik -p nazwa_bazy
```

Po imporcie strona powinna odpowiadać pod `https://przedszkoleslomniki.pl`.
Jeśli oddaje 404 na podstronach — brak `.htaccess` albo `mod_rewrite`;
strona główna działa, reszta nie, to klasyczny objaw.

## 9. Konta i sprzątanie na produkcji

Logowanie: `https://przedszkoleslomniki.pl/wp-admin`, na razie kontem `dev`.

- [ ] **Ustawienia → Bezpośrednie odnośniki → Zapisz** — przebudowa reguł
      (nawet bez zmiany ustawienia)
- [ ] Ustawienia → Czytanie: „Widoczność dla wyszukiwarek" **odznaczone**
- [ ] Sprawdzić, czy własne role żyją: `Użytkownicy` pokazuje `intendent`
      i `pedagog` z tymi rolami. Definicje ról jadą w bazie; motyw odtwarza je
      na `init`, gdy `przedszkole_wersja_rol` nie zgadza się z
      `PRZEDSZKOLE_WERSJA_ROL` (`inc/role.php`)
- [ ] Założyć konto administratora dla osoby z przedszkola (mocne hasło, realna
      skrzynka)
- [ ] Ustawić realne adresy e-mail przy kontach grupowych, `intendent`, `pedagog`
- [ ] Zalogować się na nowe konto administratora i **dopiero wtedy**:

```
Użytkownicy → dev → Usuń → „Przypisz wszystkie treści do:" → przedszkole
```

Nie „usuń treść" — konto `dev` jest autorem części stron. Konto `dev`
(`dev/dev12345`) jest wyłącznie lokalne i nie ma prawa zostać na produkcji.

## 10. Cron systemowy

`DISABLE_WP_CRON` z kroku 6 wyłącza uruchamianie zadań przy wejściach na stronę.
W panelu cyber_Folks dodać zadanie co 15 minut:

```bash
cd /home/icrdslom/domains/przedszkoleslomniki.pl/public_html && php wp-cron.php > /dev/null 2>&1
```

Jeśli panel nie daje cronu z linią poleceń, wersja przez HTTP:

```bash
curl -s https://przedszkoleslomniki.pl/wp-cron.php?doing_wp_cron > /dev/null
```

Na tej stronie cron obsługuje głównie aktualizacje i zaplanowane publikacje —
15 minut wystarcza z zapasem.

## 11. Uprawnienia plików

Ze SSH:

```bash
cd /home/icrdslom/domains/przedszkoleslomniki.pl/public_html
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
```

Bez SSH — w menedżerze plików panelu: katalogi 755, pliki 644, `wp-config.php`
600 (albo 640, jeśli panel nie pozwala na 600).

## 12. Testy odbiorcze

Funkcjonalne:

- [ ] Strona główna, każda pozycja menu głównego i stopki — bez 404
- [ ] Aktualności: lista, pojedynczy wpis, przełącznik roczników
- [ ] Kategorie grup, „Kącik logopedy" (kąciki **nie** tną się do bieżącego roku szkolnego)
- [ ] Strony grup, Kadra (kafelki z inicjałami), Jadłospis, Kontakt z mapą
- [ ] Wyszukiwarka, strona 404, kanał RSS (`/feed/`)
- [ ] Obrazy się ładują — logo, ikona strony, mapa
- [ ] Polskie znaki wszędzie, także w tytułach i menu
- [ ] Telefon / tablet / desktop; Chrome, Safari, Firefox, Edge

Bezpieczeństwo — każdy punkt to jedno sprawdzenie w przeglądarce:

- [ ] `/?author=1` **nie** przekierowuje na `/author/<login>/`
- [ ] `/wp-json/wp/v2/users` anonimowo → 404, po zalogowaniu → 200
- [ ] `/wp-admin/theme-editor.php` → 403
- [ ] `/xmlrpc.php` → 403
- [ ] `/wp-config.php` → 403
- [ ] Szósta nieudana próba logowania → komunikat o kwadransie; poprawne hasło
      zeruje licznik
- [ ] Źródło strony nie zdradza wersji WordPressa
- [ ] Odpowiedź dla niezalogowanego nie zawiera `Set-Cookie` ani adresu spoza
      domeny (narzędzia deweloperskie → Sieć, tryb prywatny)

Role — zalogować się na każde konto i sprawdzić, co widać:

- [ ] `przedszkole` (Editor) — strony i wpisy, bez ustawień i wtyczek
- [ ] konto grupowe (Author) — własne wpisy, bez stron
- [ ] `intendent` — wyłącznie „Jadłospis"
- [ ] `pedagog` — „Kącik pedagoga" i wpisy wymuszane do kategorii `pedagog`

Pomiar:

- [ ] Lighthouse / PageSpeed na produkcji — wydajność, dostępność, SEO
- [ ] Pasek dostępności (rozmiar tekstu, wysoki kontrast) na prawdziwej
      przeglądarce: aktywacja Enterem i spacją, odsłuch NVDA/VoiceOver

## 13. Po starcie

- [ ] **Deklaracja dostępności napisana od nowa** — obowiązek ustawowy, lista
      zmian w PLAN.md, Etap 8.4
- [ ] Przegląd polityki prywatności pod kątem nowej strony
- [ ] Google Search Console: potwierdzenie własności + zgłoszenie
      `https://przedszkoleslomniki.pl/wp-sitemap.xml`
- [ ] Przekierowania 301 ze starych adresów — jeśli stara strona była
      zaindeksowana. Wpisy mają metadaną `_joomla_id`, więc mapa da się wyliczyć
      bez ręcznej listy (MIGRACJA.md)
- [ ] Backup: harmonogram (baza codziennie, pliki tygodniowo), retencja ≥ 30 dni,
      kopia **poza** katalogiem strony
- [ ] **Testowe odtworzenie backupu** — backup nieprzetestowany to brak backupu
- [ ] Instrukcja dla personelu + szkolenie (Etap 12)
- [ ] Przekazanie danych dostępowych bezpiecznym kanałem (nie e-mailem z hasłem
      w treści)

## 14. Wycofanie

Jeśli po wdrożeniu coś jest nie tak:

1. **Błąd w motywie** — w panelu przełączyć na `twentytwentyfive`. Strona
   wygląda inaczej, ale działa i pozwala się zalogować. Dlatego zostawiamy
   ten motyw na serwerze.
2. **Błąd 500 po edycji `.htaccess`** — usunąć ostatnio wklejony blok.
   Sam `# BEGIN WordPress` wystarczy do działania strony.
3. **Zepsuta baza** — import zrzutu z kroku 4 jeszcze raz, po `DROP` tabel.
4. **Całość** — backup hostingu (1×/24h, retencja 28 dni) plus backup starej
   strony z kroku 3. Nowej strony nie da się odtworzyć z niczego innego,
   dopóki nie stoi backup z punktu 13.

---

## Czego ta instrukcja świadomie nie robi

- **Nie zmienia prefiksu tabel** — uzasadnienie w kroku 4
- **Nie instaluje wtyczek**: ani cache, ani bezpieczeństwa, ani backupu.
  Strona bez wtyczek utrzymuje się sama; każda wtyczka to cykl aktualizacji
  na lata (PLAN.md, Etap 8.3)
- **Nie stawia formularza kontaktowego** — odrzucony 2026-09-11. Zostaje adres
  e-mail i telefon, więc odpadają testy poczty z Etapu 11
- **Nie konfiguruje SMTP** — strona wysyła tylko maile odzyskiwania hasła,
  funkcja `wp_mail()` z serwera wystarczy. Jeśli maile nie dochodzą,
  sprawdzić SPF domeny, zanim sięgniesz po wtyczkę
