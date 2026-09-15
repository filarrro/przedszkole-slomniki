# Strona przedszkola — plan projektu

Dokument roboczy. Pracujemy **etapami**. Każdy etap ma: cel, kroki, kryteria odbioru i status.
Po każdym etapie testujemy, zanim ruszymy dalej.

Powiązane: [README.md](README.md) — jak uruchomić · [MIGRACJA.md](MIGRACJA.md) — treści ze starej strony · [CLAUDE.md](CLAUDE.md) — konwencje kodu

Status: `[ ]` do zrobienia · `[~]` w trakcie · `[x]` zrobione · `[-]` pominięte (z uzasadnieniem)

---

## Stan na 2026-09-15

| Etap | Stan | Uwagi |
|---|---|---|
| 1. Analiza hostingu | ✅ | cyber_Folks, brak blokerów |
| 2. Środowisko lokalne | ✅ | DDEV + WordPress 7.1 + PHP 8.5 |
| 2b. Migracja z Joomli | ✅ | 16 stron + 428 wpisów; pliki ze starego serwera przepadły |
| 3. Motyw | ✅ | szkielet, szablony, identyfikacja wizualna |
| 4. Struktura treści | ✅ | strony, menu główne + stopka, kategorie |
| 5. Gutenberg | ✅ | wzorce, warianty stylów, style bloków i edytora |
| 6. Użytkownicy | ✅ | role natywne, konta grupowe, panel odchudzony |
| 7. Frontend | ✅ | widoki gotowe; lista dokumentów bez PDF-ów — przepadły ze starym serwerem |
| 8. SEO / wydajność / bezpieczeństwo | 🔄 | motyw gotowy; `.htaccess`, cache i Search Console przy wdrożeniu |
| 9. Wdrożenie | ⬜ | |
| 10–12 | ⬜ | |

### Co blokuje postęp

**Nic po stronie kodu.** Motyw gotowy, treść na miejscu. Następny krok to Etap 9 —
wdrożenie na cyber_Folks.

**Pliki ze starego serwera przepadły bezpowrotnie.** Hosting wyłączył starą
witrynę: domena oddaje 403, ścieżki do plików 404, FTP niedostępny. 407 obrazków
wyróżniających i 118 załączników (PDF-y, wnioski, zarządzenia) nie ma skąd wziąć.
To nie jest blokada, tylko trwała strata — planujemy bez nich. Szczegóły
w [MIGRACJA.md](MIGRACJA.md).

### Dług treściowy do domknięcia przed startem

- 7 wpisów, które na starej stronie były samą zajawką dla pliku PDF
- 11 wpisów z treścią, ale bez dokumentu
- zdjęcia kadry — 12 osób ma inicjały, 2 stockowe zastępniki czekają na podmianę
- przekierowania 301 ze starych adresów, jeśli stara strona była indeksowana

### Decyzje czekające na klienta

- ~~Dane do stopki: adres, telefon, godziny otwarcia~~ — **odzyskane ze zrzutu**
  (Etap 2b): ul. św. Jadwigi Królowej 4, 32-090 Słomniki, tel. 510 217 005,
  sekretariat@przedszkoleslomniki.pl. Do potwierdzenia, czy nadal aktualne
- ~~Jakie sekcje na stronie głównej poza aktualnościami~~ — **zamknięte**
  2026-09-15: treść strony głównej potwierdzona jako docelowa
- Czy stara strona jest zaindeksowana w Google (przekierowania 301)
- Zakres zgód rodziców na publikację zdjęć dzieci
- **Zdjęcia kadry.** Zostały dwa stockowe portrety ze znakiem wodnym (Agata Dudek,
  Marta Gądek); trzy ilustrowane awatary usunięte 2026-09-14, bo podpisane imieniem
  i nazwiskiem wyglądały jak portret tej osoby. Dwanaście osób ma inicjały.
  Potrzebne prawdziwe zdjęcia pracownic wraz z ich zgodą na publikację —
  bez nich zostają inicjały, które są poprawnym stanem końcowym, nie brakiem
- ~~Godziny otwarcia — w sekcji „Dlaczego my" stoi tymczasowe 6:30–17:00~~ —
  **potwierdzone** ramowym rozkładem dnia ze starej strony: schodzenie się dzieci
  od 6:30, zajęcia do 17:00. Do potwierdzenia, czy nadal aktualne

---

## 0. Kontekst i zasady

**Co budujemy:** jedna strona WWW dla jednego konkretnego przedszkola. Nie SaaS, nie uniwersalny CMS.

**Priorytety (w tej kolejności):**
1. Krótki czas wykonania
2. Prostota wdrożenia i utrzymania
3. Niski koszt
4. Samodzielna edycja treści przez pracowników
5. Łatwe zarządzanie zdjęciami
6. Dodawanie nowych podstron bez developera
7. Różne poziomy uprawnień
8. Wydajność i bezpieczeństwo
9. Brak zbędnej komplikacji technologicznej

**Zasada nadrzędna:** jeśli WordPress już coś potrafi — używamy tego, nie piszemy własnego.
Wtyczkę dodajemy, gdy jest szybsza i stabilniejsza niż własny kod. Własnego kodu nie piszemy tylko po to, by uniknąć wtyczki.

### Stack (ustalony)
- WordPress (CMS + backend + frontend)
- Własny lekki motyw (PHP + WordPress Template API)
- Gutenberg jako edytor treści
- MySQL / MariaDB
- PHP w wersji zgodnej z WP
- HTML + CSS + minimum vanilla JS
- Istniejący hosting — nie zmieniamy

### Poza zakresem (świadomie odrzucone)
Laravel · Payload CMS · Strapi · Next.js · React jako frontend · osobne API / headless · VPS · Vercel i inne hostingi · ciężkie page buildery (Elementor) · własny system logowania · SPA

### Architektura
```
Przeglądarka
      ↓
  WordPress
   ↓      ↓
  PHP    MySQL
   ↓
własny lekki motyw
```

### Zasady pracy z AI
- Nie zakładamy znajomości WordPressa ani PHP po stronie właściciela projektu.
- Przy każdym większym kroku: (1) krótko co i po co, (2) konkretne działania, (3) kod tylko gdy potrzebny, (4) gdzie dokładnie go wkleić, (5) jak sprawdzić, że działa.
- Nie robimy wielu niezweryfikowanych kroków naraz.
- Nie generujemy całego projektu w jednym strzale.

### Zasady dla kodu
- Prosty, konwencjonalny, zgodny ze standardami WordPressa (WPCS).
- Bez „magii" i nadmiernych abstrakcji.
- Czytelny dla kolejnego agenta AI / developera.
- Przed napisaniem funkcji: sprawdź, czy WP już jej nie ma.

---

## Etap 1 — Analiza hostingu

**Cel:** wiedzieć, na czym stoimy, zanim cokolwiek instalujemy. Bez tego reszta to zgadywanie.

- [ ] Wersja PHP (i czy da się podnieść)
- [ ] Wersja MySQL / MariaDB
- [ ] Rozszerzenia PHP wymagane przez WP (mysqli, gd/imagick, curl, mbstring, zip, json)
- [ ] `.htaccess` / mod_rewrite — czy działa (potrzebne do ładnych URL-i)
- [ ] `memory_limit` PHP (min. 128M, zalecane 256M)
- [ ] `upload_max_filesize` i `post_max_size` (zdjęcia!)
- [ ] `max_execution_time`
- [x] Dostęp FTP / SFTP — jest
- [ ] Dostęp SSH (opcjonalny, ale ułatwia)
- [ ] Cron systemowy (do zastąpienia WP-Cron)
- [ ] SSL / certyfikat (Let's Encrypt?)
- [ ] Wybór katalogu dla WordPressa (główna domena / subdomena / podkatalog)
- [ ] Backupy hostingu — czy są, jak częste, jaka retencja, jak odtworzyć
- [x] Limity bazy danych — bazy ∞, dysk 35 GB
- [ ] Panel hostingu — jaki (cPanel / DirectAdmin / własny)
- [ ] **Co już stoi na koncie?** 5,5 GB / 56 tys. plików zajęte — sprawdzić przed instalacją

**Jak sprawdzić:** panel hostingu + plik `phpinfo.php` wgrany tymczasowo na serwer (usunąć zaraz po sprawdzeniu — ujawnia konfigurację serwera).

**Nie zakładamy:** Node.js, Dockera, VPS-a, composera, dostępu roota.

**Kryteria odbioru:** wypełniona tabelka parametrów poniżej + decyzja, czy hosting wystarcza.

### Wyniki

**Hosting: cyber_Folks.** Źródła: zrzut panelu (2026-09-11) + https://cyberfolks.pl/parametry-techniczne/#hosting-www

| Parametr | Wartość | Status |
|---|---|---|
| PHP | MultiPHP — do 8.5 | ✅ **wybrane 8.5** (WP core kompatybilny od 6.9) |
| memory_limit | do 1024 MB | ✅ ogromny zapas |
| MySQL — rozmiar bazy | bez limitu | ✅ |
| MySQL — połączenia | 25 jednoczesnych / użytkownik | ✅ |
| MySQL — max czas zapytania | 180 s | ✅ |
| HTTP timeout | 300 s | ✅ |
| `.htaccess` | obsługiwany | ✅ ładne URL-e OK |
| GD + ImageMagick | dostępne | ✅ miniaturki, konwersja WebP |
| CURL / iconv / Freetype | dostępne | ✅ |
| Backup hostingu | 1×/24h, retencja do 28 dni | ✅ solidnie |
| SSH | od pakietu cyber_RUN wzwyż | ⚠️ zależy od pakietu |
| Miejsce na dysku | 35 840 MB (5551 zajęte) | ✅ |
| Transfer | ∞ (1,26 GB użyte) | ✅ |
| Bazy danych | ∞ (2 użyte) | ✅ |
| Konta FTP | ∞ (1 użyte) | ✅ |
| Liczba plików | 56 189 / 1 000 000 | ✅ WP to ~2–3 tys. plików |
| Konta e-mail | ∞ (4 użyte) | ✅ |
| Limit wysyłki e-mail | 5000 | ✅ wystarczy dla formularza |
| MySQL/MariaDB — wersja | ? | do sprawdzenia |
| upload_max_filesize | ? | ⚠️ ustawić min. 16M (zdjęcia) |
| post_max_size | ? | ⚠️ ≥ upload_max_filesize |
| max_execution_time | ? | do sprawdzenia |
| Cron | ? | sprawdzić w panelu |
| SSL | ? | cyber_Folks daje Let's Encrypt — włączyć |
| Katalog WP | ? | do ustalenia |

### ✅ Wniosek Etapu 1

**Hosting w pełni wystarcza pod WordPressa. Brak blokerów.** Można przechodzić do Etapu 2.
Pozostałe znaki zapytania to ustawienia do zmiany w panelu, nie ograniczenia platformy.

**Stan katalogu:** w FTP stoi **stara strona przedszkola** (5,5 GB, 56 tys. plików). Do usunięcia przed instalacją WP.

### Do zrobienia w panelu cyber_Folks
Nie pilne — budujemy lokalnie. Wykonać przy wdrożeniu (Etap 9).
- [ ] Sprawdzić cron
- [ ] Sprawdzić pakiet (czy z SSH)
- [ ] Sprawdzić wersję MySQL/MariaDB

### ⚠️ Usunięcie starej strony — operacja nieodwracalna

Wykonywane dopiero przy wdrożeniu (Etap 9). **Nic nie kasujemy przed odhaczeniem wszystkich punktów:**

- [ ] Pełny backup katalogu przez FTP na dysk lokalny (całość, nie wybiórczo)
- [ ] Eksport obu baz danych przez phpMyAdmin (Eksport → SQL) — stara strona może z nich korzystać
- [ ] Przegląd zawartości pod kątem materiałów do odzyskania:
  - [ ] logo i grafiki przedszkola
  - [ ] zdjęcia (galerie ze starej strony)
  - [ ] PDF-y i dokumenty
  - [ ] teksty do przeniesienia (O przedszkolu, oferta, kontakt)
- [ ] Potwierdzenie od klienta, że stara strona nie jest już potrzebna
- [ ] Weryfikacja, że backup cyber_Folks obejmuje ten katalog i wiadomo, jak go odtworzyć
- [ ] Dopiero teraz: usunięcie plików
- [ ] Usunięcie nieużywanej bazy danych starej strony (po potwierdzeniu, która to)


---

## Etap 2 — Środowisko lokalne

**Cel:** działający WordPress na laptopie. Całą stronę budujemy lokalnie, na serwer wchodzimy dopiero gotowi (Etap 9).

**Dlaczego lokalnie:** szybciej, bez ryzyka dla produkcji, można psuć i cofać, nie trzeba jeszcze kasować starej strony.

### Stack lokalny
- **DDEV** (Docker) — Docker już jest na maszynie
- PHP 8.5 — tak jak na produkcji
- MariaDB/MySQL w kontenerze
- wbudowany `wp-cli` — automatyzacja instalacji, ról, stron

### Układ katalogów
```
przedszkole-wp/              ← repo git
├── PLAN.md
├── .ddev/                   ← konfiguracja DDEV
├── theme/                   ← NASZ KOD (jedyne, co wersjonujemy)
│   └── przedszkole/
└── wp/                      ← WordPress (w .gitignore)
    └── wp-content/themes/
        └── przedszkole  →   symlink do ../../../theme/przedszkole
```

**Zasada:** git wersjonuje tylko motyw. Rdzeń WP, wtyczki i uploady są ignorowane — to nie nasz kod.

### Kroki
- [x] Instalacja DDEV (`brew install ddev/ddev/ddev`) — v1.25.4
- [x] `ddev config` — typ `wordpress`, PHP 8.5, docroot `wp`
- [x] `ddev start` — kontenery działają
- [x] WordPress 7.1 pl_PL zainstalowany przez `wp-cli`
- [x] Konto administratora (lokalne, robocze)
- [x] Struktura katalogów + symlink motywu
- [x] `.gitignore` — rdzeń WP, uploads, wtyczki, zrzuty baz
- [x] `git init` + staging
- [x] Pierwszy commit
- [x] Ustawienia WP: język PL, Europe/Warsaw, permalinki `/%postname%/`
- [x] Usunięcie domyślnych treści, wtyczek i zbędnych motywów
- [x] Minimalny szkielet motywu — aktywowany
- [x] `WP_DEBUG` włączone (środowisko lokalne) — wraz z `WP_DEBUG_LOG`,
      `WP_DEBUG_DISPLAY = false` i `DISALLOW_FILE_EDIT`
- [ ] HTTPS lokalnie: `mkcert -install` (wymaga hasła — do wykonania ręcznie)

### Stan środowiska

| Element | Wartość |
|---|---|
| Adres lokalny | http://przedszkole.ddev.site |
| Panel | http://przedszkole.ddev.site/wp-admin |
| Login / hasło | `dev` / `dev12345` (tylko lokalnie, nie trafia na produkcję) |
| WordPress | 7.1 pl_PL |
| PHP | 8.5.8 |
| Baza | MariaDB 11.8 (`db` / `db` / `db`) |
| DDEV | 1.25.4 |
| Motyw aktywny | `przedszkole` 0.1.0 |
| Motyw zapasowy | `twentytwentyfive` (do diagnostyki) |

### Przydatne komendy
```bash
ddev start              # uruchom srodowisko
ddev stop               # zatrzymaj
ddev describe           # adresy i status
ddev launch             # otworz strone w przegladarce
ddev exec wp --path=wp <komenda>   # wp-cli
ddev export-db --file=dump.sql.gz  # zrzut bazy
ddev mysql              # konsola SQL
```

**Uwaga:** `wp-cli` uruchamiaj z `--path=wp`, bo docroot to `wp/`, a kontener startuje w katalogu projektu.

**Kryteria odbioru:** ✅ strona zwraca 200, panel działa, ładne URL-e działają, motyw aktywny.

---

## Etap 2b — Migracja treści z Joomli

**Cel:** przenieść treści ze starej strony (Joomla 3.10.5) do WordPressa.

Szczegółowa analiza i plan: **[MIGRACJA.md](MIGRACJA.md)**

### Skrót
- Stara strona: Przedszkole w Słomnikach, Joomla 3.10.5
- 1753 opublikowane artykuły (2015–2026), **440 z ostatnich 3 lat**
- ❌ 782 pliki graficzne i 118 załączników **przepadły** — stary hosting wyłączył witrynę,
  FTP niedostępny. Zostały same ścieżki w bazie
- ✅ **Prawdziwe galerie są w Google Photos: 395 albumów linkowanych z 387 artykułów** —
  linki działają, więc zdjęcia z wydarzeń nie zginęły
- 4 autorki: Bożenka, Agnieszka, Ewa, Aneta
- Treść to czysty HTML — brak shortcode'ów i wtyczek galerii
- Domena: `przedszkoleslomniki.pl`, katalog: `/home/icrdslom/domains/przedszkoleslomniki.pl/public_html/`

- [x] Import zrzutu do lokalnej bazy roboczej
- [x] Analiza struktury i zakresu
- [x] ~~Pobranie `images/` i `attachments/` ze starego FTP~~ — **nieosiągalne**, hosting
      wyłączył witrynę (403 na stronie, 404 na plikach). 407 obrazków i 118 załączników przepadło
- [x] Zakres: lata szkolne 2023/24–2025/26 (od 2023-09-01) → 440 wpisów
- [x] Autorstwo: 7 kont grupowych, wyprowadzanych z kategorii
- [x] Decyzja: galerie **zostają w Google Photos** — konto należy do przedszkola
- [x] Decyzja: wpisy **bez obrazków wyróżniających** — zdjęcia tylko tam, gdzie treść
      wskazuje konkretny plik, a takich odnośników w zakresie nie ma
- [x] Migracja treści **statycznych** — 16 stron (`tools/migracja_tresci.py`)
- [x] Skrypt migracyjny **aktualności** (`tools/migracja_wpisow.py`) + skan treści
      pod kątem wstrzykniętego kodu — zero trafień w 1753 artykułach
- [x] Import 428 wpisów, weryfikacja: kategorie, autorzy, daty, zero utraty treści
- [ ] Przekierowania 301 ze starych adresów — wpisy trzymają `_joomla_id`,
      więc mapowanie nie wymaga ręcznej listy
- [ ] Uzupełnienie 7 pominiętych wpisów i 11 brakujących dokumentów z innego źródła

**Kryteria odbioru:** wpisy w WordPressie z poprawnymi datami, autorami i treścią;
próbka sprawdzona ręcznie. Zdjęć nie ma z czego przenieść — pliki przepadły wraz
ze starym serwerem, szczegóły w [MIGRACJA.md](MIGRACJA.md).

---

## Etap 3 — Własny motyw

**Cel:** lekki motyw z własną identyfikacją wizualną. Podstawa pod treści.

### Kierunek wizualny
Ciepła biel zamiast czystej bieli, granat-fiolet z logotypu jako kolor wiodący,
jeden ciepły akcent (miód) na przyciski. Tęcza z logo użyta oszczędnie —
cienki pasek nad nagłówkiem i kolory przypisane sześciu grupom.

Sekcje rozdzielone **falami z chmurkami** (SVG), tła na miękkich gradientach,
w hero **własna ilustracja** nauczycielki czytającej dzieciom. Charakter przedszkolny,
ale bez krzykliwości i bez zdjęć stockowych.

Paleta wyprowadzona z pliku `logo.png` — kolory pobrane bezpośrednio z logotypu.

### Paleta

| Rola | Kolor | Zastosowanie |
|---|---|---|
| Tło | `#FDFAF5` | ciepła biel, tło strony |
| Karta | `#FFFFFF` | kafelki, panele |
| Tekst | `#2B2733` | treść |
| Tekst pomocniczy | `#5F5869` | daty, zajawki |
| Granat | `#3D2FB5` | nagłówki, linki, stopka — z logotypu |
| Granat jasny | `#ECEAFB` | tła aktywnych elementów |
| Akcent (miód) | `#E8961C` | przyciski, wyróżnienia |
| Obramowanie | `#EAE3D9` | ramki kart |

### Kolory grup

Paleta pastelowa, wyprowadzona z tęczy w logotypie. Każda grupa ma trzy odcienie:
**pastel** na tło etykiety, **średni** na pasek kafelka i do użytku w edytorze,
**ciemny** na tekst — sam pastel nie daje wystarczającego kontrastu pod tekst.

| Grupa | Barwa | Pastel (tło) | Średni | Ciemny (tekst) | Kontrast |
|---|---|---|---|---|---|
| Misie | różowy | `#F7DCE7` | `#E88BAE` | `#A8305C` | 5,04 |
| Wiewiórki | pomarańcz | `#FBE4CD` | `#EFA45C` | `#96540F` | 4,78 |
| Zajączki | błękit | `#D8EDF9` | `#6FBFE4` | `#175F80` | 5,83 |
| Żabki | zielony | `#DCEFD7` | `#7BC45F` | `#2E6B26` | 5,35 |
| Jeżyki | czerwony | `#F9DCD7` | `#E4705C` | `#A63A28` | 4,99 |
| Kotki | żółty | `#FAF0C6` | `#EFC63F` | `#79590A` | 5,65 |

Kontrast tekst/pastel policzony i zweryfikowany — wszystkie pary spełniają WCAG AA (wymóg 4,5).
Odcienie średnie są w `theme.json` (widoczne w edytorze), pastel i ciemny jako zmienne CSS.

### Krój pisma

**Nunito**, wersja zmienna (grubości 400–800 z jednego pliku).
Licencja **SIL Open Font License 1.1** — wolno używać komercyjnie i na stronach
placówek publicznych. Treść licencji w `assets/fonts/OFL.txt` (wymóg OFL).

Hostowany lokalnie, bez CDN. Podzielony na zestawy znaków:
`latin` (39 kB) i `latin-ext` (35 kB, polskie znaki) — przeglądarka pobiera tylko potrzebny.
Font wstępnie wczytywany przez filtr `wp_preload_resources`.

### Pliki motywu
```
theme/przedszkole/
├── style.css              tokeny + wszystkie style (jeden plik, jedno żądanie)
├── theme.json             paleta, typografia, odstępy — wspólne z Gutenbergiem
├── functions.php          wsparcie motywu, menu, widgety, sprzątanie WP
├── inc/helpers.php        funkcje pomocnicze (etykiety grup)
├── header.php  footer.php
├── front-page.php         strona główna: treść z Gutenberga + auto aktualności
├── page.php  single.php
├── index.php              listy wpisów: aktualności, kategorie, archiwa
├── search.php  404.php
├── screenshot.png         podgląd motywu w panelu (1200×900)
├── languages/             szablon tłumaczeń (`przedszkole.pot`)
├── template-parts/
│   ├── card.php              kafelek aktualności
│   ├── chmurki.php           falista krawędź z chmurkami (SVG)
│   ├── dlaczego-my.php       sekcja „Dlaczego my" — 4 powody z ikonami SVG
│   └── skrzydla.php          hasło „Pomagamy dzieciom rozwijać skrzydła" na zdjęciu
└── assets/
    ├── js/nav.js             menu mobilne (~1 kB)
    ├── img/hero.webp         ilustracja do hero (2 szerokości)
    ├── img/skrzydla-*.webp   zdjęcie do sekcji „rozwijać skrzydła" (1140/1920/2816)
    └── fonts/                Nunito woff2 + licencja OFL
```

### Zrobione
- [x] `theme.json` — paleta, typografia płynna (clamp), odstępy, style przycisków
- [x] `style.css` — tokeny, reset, układ, nagłówek, stopka, karty, paginacja
- [x] Nagłówek: przyklejony, pasek tęczy, logotyp, menu
- [x] Nawigacja: poziomo na desktopie z podmenu, panel rozwijany na telefonie
- [x] Menu mobilne w czystym JS — Escape zamyka, przełączenie na desktop resetuje
- [x] Stopka: kontakt, menu, godziny (obszary widgetów)
- [x] Szablony: strona główna, strona, wpis, archiwum, wyszukiwanie, 404
- [x] Kafelki aktualności z kolorowymi etykietami grup
- [x] Dostępność: skip link, widoczny focus, `prefers-reduced-motion`, `aria-expanded`
- [x] Semantyczny HTML: `<header> <nav> <main> <article> <footer>`
- [x] Responsywność: 3 kolumny → 2 → 1
- [x] Sprzątanie WP: emoji, generator, wlwmanifest, RSD, XML-RPC wyłączone
- [x] Zero frameworków, zero jQuery, zero zewnętrznych zapytań
- [x] Logo wgrane do biblioteki mediów i ustawione jako logo motywu
- [x] Ilustracja hero — własny SVG, kolory dopasowane do logotypu
- [x] Fale z chmurkami między sekcjami
- [x] Gradienty: hero, sekcja aktualności, kafelki
- [x] Nunito hostowane lokalnie, dzielone na zestawy znaków, z preload
- [x] Pastelowa paleta grup z weryfikacją kontrastu WCAG AA
- [x] Fala z chmurkami przed stopką na wszystkich podstronach
- [x] `screenshot.png` — zrzut strony głównej, podgląd motywu w Wyglądzie
- [x] `languages/przedszkole.pot` — szablon tłumaczeń (110 ciągów).
      Teksty motywu są po polsku, więc plik istnieje dla porządku:
      `load_theme_textdomain()` wskazywało na nieistniejący katalog
- [x] Komentarze zamknięte filtrami w kodzie, nie tylko w ustawieniach —
      motyw nie ma `comments.php`, więc włączenie dyskusji w panelu dałoby
      front bez formularza. Pola komentarzy zdjęte też z ekranu edycji
- [x] Formularz hasła (`.post-password-form`) ostylowany jak wyszukiwarka —
      strona z widocznością „Chroniona hasłem" nie wygląda już surowo
- [x] `wp_link_pages()` w `single.php` — wpis podzielony znacznikiem
      „następna strona" ma nawigację, tak jak strony w `page.php`

### Do dokończenia
- [x] Wypełnienie widgetów stopki — adres, telefon, e-mail i godziny otwarcia
      (widgety bloku `footer-kontakt` i `footer-godziny`, ustawia
      `tools/struktura.sh`). Ikony SVG dokłada CSS przez klasy
      `.kontakt__poz--*`, więc redaktor ich nie skasuje edytując treść.
      Dane do potwierdzenia przez dyrekcję — patrz „Decyzje czekające na klienta"
- [ ] Weryfikacja wyglądu edytora Gutenberg (Etap 5)
- [x] Komponent linku do galerii Google Photos — jako wariant stylu bloku
      „Przycisk" (`is-style-galeria`), wstawiany wzorcem (Etap 5)
- [x] Sekcja „Dlaczego my" — cztery powody z własnymi ilustracjami SVG
- [x] Sekcja „Pomagamy dzieciom rozwijać skrzydła" — hasło na zdjęciu, paski
      granatu z palety, napisy skalowane jednostkami `cqw` względem kadru.
      Kadr poza siatką treści: pełna szerokość okna, najwyżej 2560 px;
      powyżej 2000 px przycinany od dołu (`aspect-ratio` + `object-fit`),
      krawędzie wygaszane gradientem do koloru tła (góra zawsze, boki od 2560 px)
- [ ] Skróty do kluczowych sekcji na stronie głównej — do ustalenia z klientem

**Kryteria odbioru:** ✅ strona spójna na telefonie i desktopie, konsola czysta, brak błędów PHP.

### Podgląd wizualny
Panel podglądu w aplikacji blokuje pliki podrzędne z `*.ddev.site`.
Do zrzutów służy `tools/podglad.py` — skleja stronę w jeden plik HTML.
Do normalnej pracy wystarczy http://przedszkole.ddev.site w przeglądarce.

---

## Etap 4 — Struktura treści

**Cel:** szkielet serwisu — puste strony, menu, kategorie. Bez finalnych tekstów.

### Zbudowana struktura

Scalenie propozycji z realnym menu starej strony (patrz MIGRACJA.md).
Płaska lista 13 pozycji nie mieściła się w poziomym menu — stąd zagnieżdżenie.

**Korekta 2026-09-14.** Usunięte trzy strony: „O przedszkolu", „Oferta"
i „Galeria". Pierwsza miała wyłącznie treść testową i nie było z czego jej
napisać — stara strona nie miała takiej podstrony (patrz MIGRACJA.md, „Czego
w zrzucie nie ma"). Druga niosła jedno zdanie o zajęciach dodatkowych, a te są
już opisane na stronach grup. Trzecia była zbędna, odkąd każdy wpis prowadzi
wprost do swojego albumu — zbiorcza lista 395 linków nie miała komu służyć.
Kadra przeszła na górny poziom menu, adres z `/o-przedszkolu/kadra/` na `/kadra/`.

**Kolejność menu** od najczęściej odwiedzanego: Aktualności · Grupy ·
Dla rodziców · Kadra · Dofinansowanie · Kontakt. Sześć pozycji najwyższego
poziomu, 17 łącznie.

```
Strona główna              (statyczna, poza menu)
Aktualności                (strona wpisów)
Grupy
  ├── Misie · Wiewiórki · Zajączki
  └── Żabki · Jeżyki · Kotki
Dla rodziców
  ├── Dokumenty
  ├── Jadłospis
  ├── Ramowy rozkład dnia
  ├── Opłaty
  └── Kącik logopedy
Kadra
Dofinansowanie             ⚖️ wymagania zewnętrzne — musi być widoczne
Kontakt

— stopka —
Deklaracja dostępności     ⚖️ wymagana prawem
Polityka prywatności       (natywna strona prywatności WP)
Kontakt
```

**Czego nie odtwarzamy ze starego menu.** „Projekty" (12 artykułów w 3 lata,
rozbite na 6 podgałęzi) i „Dofinansowanie" jako gałąź z treścią — martwe.
„Kącik Logopedy" (4 artykuły) schodzi pod „Dla rodziców" jako strona, nie sekcja.
Liczby z analizy kategorii Joomli — patrz niżej.

### Rozkład 440 migrowanych artykułów wg gałęzi Joomli

| Gałąź | Artykułów |
|---|---|
| aktualności ogólne (`rok-szkolny-*`) | ~113 |
| komunikaty grup (6 grup) | ~320 |
| Projekty (wszystkie grupy razem) | 12 |
| Kącik Logopedy | 4 |
| Specjaliści / Kadra / Dokumenty / Dla rodziców | 10 |

Stąd taksonomia WordPressa: **6 grup + Ogłoszenia**. Nic więcej nie ma pokrycia w treści.

### Kategorie wpisów

`misie` · `wiewiorki` · `zajaczki` · `zabki` · `jezyki` · `kotki` — kolory i etykiety w motywie (Etap 3).
`ogloszenia` — aktualności ogólne, ustawiona jako kategoria domyślna.
„Bez kategorii" usunięta.

- [x] Utworzenie stron wg struktury — 20 stron (było 23; „O przedszkolu”, „Oferta” i „Galeria” usunięte)
- [x] Ustawienie strony głównej jako statycznej + strony wpisów („Aktualności")
- [x] Menu główne + kolejność + podstrony jako pozycje zagnieżdżone
- [x] Menu w stopce (lokalizacja `footer` była zarejestrowana, ale pusta)
- [x] Kategorie wpisów: 6 grup + Ogłoszenia
- [x] Strona polityki prywatności przez natywny mechanizm WP (`wp_page_for_privacy_policy`)
      — WP sam dokłada `rel="privacy-policy"` do linku
- [x] Decyzja: galerie jako **strony**, nie własny typ treści — właściwe galerie
      i tak żyją w Google Photos
- [x] **Korekta 2026-09-14: strona „Galeria" usunięta.** Zbiorcza lista linków
      dublowała aktualności — każdy wpis ma własny przycisk „Zobacz zdjęcia",
      a 395 albumów to archiwum przeglądane po wydarzeniach, nie po liście
- [x] Decyzja: dokumenty jako **strona z listą linków** do Media Library
- [x] Treść stron — 16 z 20 wypełnione migracją treści statycznych (Etap 2b).
      Pozostałe 7 wymaga treści pisanej od zera — lista w [MIGRACJA.md](MIGRACJA.md)
- [ ] Praktyczny test kryterium odbioru z administratorem

### Odtworzenie struktury od zera

Skrypt idempotentny (nie duplikuje stron o istniejącym slugu):
`tools/struktura.sh`

**Ważne:** nie kodujemy każdej podstrony jako osobnego szablonu. Standardowy `page.php` + Gutenberg obsługuje wszystko. Osobny szablon tylko tam, gdzie naprawdę trzeba (strona główna, kontakt).

**Kryteria odbioru:** administrator potrafi sam dodać nową stronę i wstawić ją do menu. Sprawdzamy to praktycznie.

---

## Etap 5 — Gutenberg

**Cel:** edytor ma wyglądać i działać tak, by pracownik przedszkola nie musiał znać HTML.

- [x] `theme.json` dopięty: kolory, rozmiary czcionek, szerokość treści — gotowe opcje w edytorze
- [x] Style edytora — `add_editor_style()` na `style.css` + `assets/css/editor.css`
- [x] Paleta ograniczona do kolorów marki — `defaultPalette: false`, `custom: false`,
      czyli bez próbnika dowolnych kolorów. Tak samo rozmiary pisma (`customFontSize: false`)
- [x] Sprawdzenie bloków z listy: nagłówek, akapit, lista, obraz, galeria, przycisk,
      cytat, kolumny, grupa, okładka, osadzenie, plik, tabela, separator, szczegóły
- [x] Wzorce bloków — 5 sztuk we własnej kategorii „Przedszkole"
- [x] Warianty stylów zamiast własnych bloków
- [x] Odcięcie wzorców pobieranych z wordpress.org
- [-] Ograniczenie listy dostępnych bloków — **pominięte**. Zgodnie z zapisem planu
      robimy to dopiero, gdy edytor okaże się przytłaczający. Wróci jako decyzja
      po szkoleniu personelu (Etap 12), na podstawie tego, co realnie sprawia trudność
- [-] Własne bloki — **pominięte**. Warianty stylów i wzorce pokryły potrzeby
      bez linii kodu JS do utrzymania

### Wzorce bloków (`patterns/`)

WordPress rejestruje pliki z tego katalogu sam — nie ma listy w kodzie.

| Wzorzec | Do czego |
|---|---|
| Link do albumu w Google Photos | najczęstszy element serwisu — na starej stronie miało go 387 z 440 artykułów |
| Wyróżniona informacja | ogłoszenie, którego nie można przeoczyć |
| Lista dokumentów do pobrania | strona „Dokumenty" — bloki pliku z biblioteki mediów |
| Wizytówka grupy | nagłówek strony grupy: nauczycielki, sala, wiek dzieci |
| Dane kontaktowe | adres, telefon, godziny w dwóch kolumnach |

### Warianty stylów bloków

Zamiast własnych bloków — pracownik wstawia zwykły blok i wybiera wariant z listy.

| Blok | Wariant | Efekt |
|---|---|---|
| Przycisk | „Link do albumu" | przycisk ze strzałką oznaczającą odnośnik zewnętrzny |
| Grupa | „Wyróżnienie" | ramka z akcentowaną krawędzią |

**Album otwiera się w tej samej karcie.** Nowe okno bez uprzedzenia łamie WCAG 3.2.5,
a przycisk „wstecz" i tak wraca na stronę.

### Ustalenia techniczne

**Szerokie wyrównania są wyłączone i tak zostaje.** Sprawdzone: `alignWide` = `false`.
Motyw klasyczny nie dostaje `align-wide` z samego `theme.json` — trzeba by je włączyć
jawnie. Nie włączamy: obsługa `alignwide` i `alignfull` wymaga przebudowy `page.php`
i `single.php` na siatkę, a `.entry` ma twarde `max-width: 760px`. Gdyby ktoś dodał
`add_theme_support( 'align-wide' )`, edytor zacznie oferować wyrównania, których
front nie pokaże.

**Edytor działa w iframe.** `.editor-styles-wrapper` jest tam elementem `body`,
więc reguły `body` i zmienne z `:root` w `style.css` działają bez przepisywania.
`editor.css` powtarza wyłącznie te reguły, które na froncie wiszą pod `.entry__content` —
tego opakowania w edytorze nie ma.

**Odstęp między blokami zrównany z `theme.json`.** Front miał 1,25 rem, edytor brał
`blockGap` 1,5 rem — podgląd rozjeżdżał się z gotową stroną. Klasyczne szablony nie
dostają układu `.is-layout-flow` wokół `the_content()`, więc odstęp ustawiamy w CSS.

**Dwa arkusze zamiast jednego** — wbrew zasadzie z CLAUDE.md. `editor.css` wchodzi
wyłącznie w panelu, więc front nadal pobiera jeden plik.

**Kryteria odbioru:** ✅ osoba nietechniczna układa sekcję ze zdjęciem, nagłówkiem
i przyciskiem bez pomocy — sprawdzone praktycznie 2026-09-11.

---

## Etap 6 — Użytkownicy i uprawnienia

**Cel:** każdy widzi tylko to, czego potrzebuje.

| Rola | Zakres | Rola WP |
|---|---|---|
| Administrator | wszystko: konfiguracja, motyw, wtyczki, użytkownicy | Administrator |
| Dyrektor | strony, aktualności, galerie, dokumenty, media | Editor (Redaktor) |
| Nauczyciel (konto grupowe) | aktualności, zdjęcia, galerie; bez konfiguracji technicznej | Author |

**Konta grupowe (z migracji):** `grupa-kotki`, `grupa-zabki`, `grupa-jezyki`, `grupa-zajaczki`, `grupa-misie`, `grupa-wiewiorki` — rola Author. Plus `przedszkole` (Editor) na treści ogólne.

- [x] Mapowanie ról na natywne role WP
- [x] Sprawdzenie, czy natywne role wystarczają — **wystarczają, zero korekt**
- [x] Utworzenie kont grupowych — `tools/uzytkownicy.sh`
- [-] Minimalna korekta uprawnień — niepotrzebna, patrz audyt niżej
- [x] Ukrycie zbędnych elementów panelu — `theme/przedszkole/inc/panel.php`
- [x] Zasada: konto administratora **nie** służy do codziennej pracy —
      wypisana na końcu `tools/uzytkownicy.sh`
- [ ] Utworzenie kont dla realnych osób — czeka na listę od dyrekcji
- [ ] Praktyczny test z dyrekcją

### Audyt uprawnień — natywne role wystarczają

Sprawdzone na żywo: logowanie na każde konto + wejście wprost po adresie,
żeby zweryfikować realne uprawnienia, nie samo ukrycie menu.

| Próba | `grupa-*` (Author) | `przedszkole` (Editor) |
|---|---|---|
| Lista wpisów, media | 200 | 200 |
| Lista i edycja stron | **403** | 200 |
| Własny wpis | 200 | 200 |
| Wpis innej grupy | **403** | 200 |
| Ustawienia, motywy, wtyczki, użytkownicy | **403** | **403** |

Author nie ma też `manage_categories` ani `unfiltered_html` — nauczyciel
przypisze wpis do istniejącej kategorii, ale nie założy nowej i nie wklei
surowego HTML-a. Oba braki są tu zaletą.

### Panel po sprzątaniu

| Rola | Widzi w menu |
|---|---|
| Administrator | Kokpit, Wpisy, Media, Strony, Wygląd, Wtyczki, Użytkownicy, Narzędzia, Ustawienia |
| Dyrektor (Editor) | Kokpit, Wpisy, Media, Strony, Profil |
| Nauczyciel (Author) | Kokpit, Wpisy, Media, Profil |

Co odjęliśmy i dlaczego:
- **„Wydarzenia i nowości WordPressa”** — odpytuje `api.wordpress.org` przy
  każdym wejściu na kokpit, z geolokalizacją pod listę meetupów. Strona nie
  wykonuje zapytań na zewnątrz, więc widget odpada dla wszystkich ról
- **„Szybki szkic”** — tworzy wpisy bez tytułu, kategorii i zdjęcia, lądujące
  w szkicach, o których nikt nie pamięta
- **Komentarze** — zamknięte globalnie i na wszystkich 29 treściach,
  przedszkole ich nie przewiduje. Zostawał pusty ekran
- **Narzędzia** dla nie-administratorów — import, eksport, kondycja i dane
  osobowe wymagają uprawnień, których Editor i Author nie mają, więc strona
  była pusta. Sprawdzamy uprawnienie `manage_options`, nie nazwę roli
- **Logo „W” w górnym pasku** — prowadzi wyłącznie na wordpress.org,
  dokumentacja i fora po angielsku

`remove_menu_page()` chowa pozycję, nie blokuje adresu — `tools.php` nadal
odpowiada 200. Nie jest to luka: strona jest dla tych ról pusta, a to, co
naprawdę chronione, zwraca 403 (tabela wyżej).

### Do rozstrzygnięcia przed wdrożeniem

- **Adresy e-mail kont.** Skrypt nadaje `grupa-<nazwa>@przedszkoleslomniki.pl`.
  Na produkcji muszą to być realne skrzynki, inaczej nie zadziała odzyskiwanie
  hasła. Hosting pokazuje 4 użyte konta pocztowe — brakuje sześciu
- ~~**Galeria a rola Author.**~~ Rozstrzygnięte 2026-09-14 przez usunięcie
  strony „Galeria”: link do albumu ląduje we wpisie grupy (wzorzec „Link
  do albumu”), a nauczyciel edytuje wpisy, nie strony. Nie ma już strony
  zbiorczej, o której uprawnienia trzeba by się spierać
- **Kategoria domyślna to `ogloszenia`.** Nauczyciel, który zapomni zaznaczyć
  swoją grupę, opublikuje wpis w ogłoszeniach ogólnych. Do instrukcji dla
  personelu (Etap 12), nie do kodu
- **Konta imienne.** Nie wiemy, kto realnie pracuje w przedszkolu — lista od
  dyrekcji. Konta grupowe są z migracji i pokrywają obieg treści

**Kryteria odbioru:** zalogowanie się na każde konto i weryfikacja, co widać w panelu, a czego nie.

---

## Etap 7 — Frontend

**Cel:** działające, wyglądające widoki na realnych treściach.

### 7.1 Strona główna
- [x] Sekcja powitalna — treść z Gutenberga, ilustracja i chmurki z motywu
- [x] Najnowsze aktualności (3 wpisy z miniaturkami)
- [x] Skróty do kluczowych sekcji — kafelki z menu „Na skróty"
- [x] Kontakt / godziny otwarcia — w stopce, na każdej podstronie

Kolejność sekcji: powitanie → aktualności → „Na skróty" → „Dlaczego my" → hasło
ze zdjęciem. Kafelki „Na skróty" biorą się z osobnego menu (`tools/skroty.sh`),
więc dyrekcja zmienia ich zestaw, tytuły i opisy w Wyglądzie → Menu. Bez menu
sekcja po prostu nie powstaje.

Sekcja skrótów stoi na żółtym tle (`#F9E229`), żeby odciąć się od kremowego tła
strony — nagłówek jest w kolorze tekstu, nie granatowy jak reszta, bo na żółtym
daje kontrast 11 zamiast 7 i nie konkuruje z tytułami w kafelkach.

Przejścia między sekcjami rysuje `template-parts/fala.php`: „warstwy" (trzy
nakładające się fale) pod powitaniem, pod skrótami i nad stopką, a „skos"
(asymetryczna krzywa) nad skrótami.
Kolor jest parametrem, bo fala należy do sekcji, która ją poprzedza, ale ma kolor
tej, która po niej następuje — i musi być rysowana wewnątrz sekcji, inaczej
przezroczysta część pokazuje tło strony zamiast tła sekcji.

### 7.2 Strony treściowe
- [x] `page.php` — uniwersalny szablon dla wszystkich stron Gutenberga
- [x] Obsługa stron zagnieżdżonych (Grupy → Misie)

Strona-rodzic („Grupy", „Dla rodziców") bywa krótka, bo treść siedzi
w podstronach. Pod treścią wypisują się więc kafelki „W tym dziale", wyliczane
z drzewa stron — nowa podstrona pojawia się tam sama. Strona-dziecko dostaje nad
tytułem odnośnik powrotny do rodzica.

### 7.3 Aktualności
- [x] Lista wpisów: tytuł, zdjęcie wyróżniające, data, zajawka
- [x] Pojedynczy wpis: tytuł, treść, zdjęcie, data, autor, kategoria
- [x] Paginacja
- [x] Archiwum kategorii

`home.php` i `archive.php` były znakiem w znak tym samym plikiem co `index.php` —
zostaje jeden. WordPress i tak schodzi do `index.php`, gdy nie znajdzie
szablonu bardziej szczegółowego.

Autor pokazuje się tylko wtedy, gdy wnosi coś ponad etykietę kategorii. Wpis
Żabek w kategorii „Żabki" wyświetlałby to samo słowo dwa razy w jednej linijce.

### 7.4 Galerie — linki do Google Photos
Galerie przedszkola żyją w Google Photos (395 albumów). Nie budujemy własnego systemu galerii.
- [x] Czytelny komponent „Zobacz zdjęcia" prowadzący do albumu
- [x] Oznaczenie, że link prowadzi na zewnątrz
- [x] Tekst alternatywny dla linków — czytnik ekranu słyszy „(album w serwisie Google Zdjęcia)"
- [x] Natywne bloki galerii WP dostępne dla treści, które trafią bezpośrednio na stronę
- [x] Miniatury zamiast pełnych zdjęć na listach — rozmiar `przedszkole-karta` 640×427
- [x] `loading="lazy"` — WP robi to sam, zweryfikowane w wygenerowanym HTML-u
- [x] Poprawne `srcset` / rozmiary obrazów — zweryfikowane
- [x] WebP — zdjęcie przechodzi na WebP przy wgrywaniu, w każdej wielkości
- [x] Limit rozmiaru uploadu — 5 MB; instrukcja dla personelu w Etapie 12

Strzałka przy przycisku albumu jest rysowana w CSS, więc czytnik ekranu jej nie
przeczyta — zapowiedź dopisuje filtr w `functions.php`. Ten sam filtr obsługuje
dokumenty. Treść pisze personel w edytorze i nikt nie będzie pamiętał
o dopisywaniu takich adnotacji ręcznie.

Zdjęcia większe niż 2048 px WordPress zmniejsza przy wgrywaniu — treść ma
1140 px szerokości, więc nawet ekran o podwójnej gęstości nie potrzebuje więcej.
Plik sprzed zmniejszenia, który WordPress normalnie chowa obok „na wszelki
wypadek", jest kasowany: przy zdjęciach z telefonu to kilka megabajtów na każdą
pozycję w bibliotece, po które nikt nie sięgnie.

Konwersja na WebP dzieje się od razu przy wgrywaniu, zanim WordPress zabierze
się za zmniejszanie i miniatury — dlatego w WebP jest każda wielkość, także ta
pełna. Plik w formacie źródłowym znika; to samo zdjęcie w dwóch formatach
zajmowałoby dwa razy tyle miejsca bez żadnego pożytku. Przezroczystość PNG-ów
przechodzi bez zmian. Filtr na miniatury zostaje dla obrazków, które przyjdą
inną drogą: migracji ze starej strony i `wp media regenerate`.

### 7.5 Dokumenty
- [x] Strona z listą dokumentów PDF (linki do Media Library) — wzorzec „Lista dokumentów"
- [x] Nazwa dokumentu + grupowanie (nagłówki sekcji w Gutenbergu)
- [x] Otwieranie PDF w nowej karcie
- [ ] Realne dokumenty — **czeka na pliki z FTP**

Nowa karta wbrew decyzji o albumach, które otwierają się w tej samej: plik PDF
nie jest stroną, do której da się wrócić przyciskiem „wstecz" — przeglądarka
albo go pobiera, albo uruchamia własną przeglądarkę plików. WCAG dopuszcza nowe
okno pod warunkiem uprzedzenia (technika G201), więc uprzedzamy: ikoną dla
patrzących, tekstem w odnośniku dla czytnika ekranu.

### 7.6 Kontakt
- [x] Dane kontaktowe, adres, godziny — wzorzec „Dane kontaktowe", dane realne
- [x] Mapa — statyczny obrazek z OpenStreetMap ze znacznikiem przedszkola
- [-] **Formularz kontaktowy — odrzucony** (decyzja z 11.09.2026). Zostaje
      adres e-mail i telefon. Odpada wtyczka, konfiguracja SMTP, antyspam,
      klauzula RODO przy formularzu i utrzymywanie tego wszystkiego przez
      lata. Przedszkole ma działającą skrzynkę — to wystarczy
- [-] ~~Antyspam bez reCAPTCHA~~ — bezprzedmiotowe bez formularza
- [-] ~~Weryfikacja, że maile dochodzą~~ — bezprzedmiotowe bez formularza
- [-] ~~RODO przy formularzu~~ — bezprzedmiotowe bez formularza

Mapa jest obrazkiem (43 kB na telefonie), a nie osadzoną mapą Google. Osadzenie
ładowałoby kilkaset kilobajtów z cudzego serwera przy każdym wejściu, zakładało
ciasteczka i wymagało klauzuli RODO. Kafelki pochodzą z OpenStreetMap na licencji
ODbL — podpis z odnośnikiem do autorów jest warunkiem licencji i nie wolno go
usuwać. Obok mapy stoi przycisk „Wyznacz trasę" prowadzący do nawigacji.

### 7.7 Animacje powiązane z przewijaniem

- [x] Sekcja `23. Animacje powiazane z przewijaniem` na końcu `style.css`
- [x] Siatka bezpieczeństwa w sekcji 7 (`animation-timeline: none !important`)
- [x] Wejście kafelków: aktualności, „Na skróty", „Dlaczego my"
- [x] Dryf chmurek w powitaniu
- [x] Parallaks bazgrołów w „Na skróty" (przy okazji naprawa iOS)
- [x] Zbliżenie zdjęcia i wjazd pasków w „Skrzydłach"
- [x] Cień nagłówka po odjechaniu od góry (globalnie)
- [x] Testy: Chrome, klawiatura, 320 px — zrobione. **Safari 26, Firefox
      i iPhone czekają na sprzęt**
- [x] Podbicie wersji motywu (`style.css` + `functions.php`) — 0.14.0

Animacje sterowane przewijaniem (CSS scroll-driven animations, `animation-timeline`)
zamiast obserwatora przecięć w JavaScripcie. Zero skryptu, zero wtyczki, zero
nasłuchu zdarzeń — przeglądarka liczy postęp sama, poza wątkiem głównym.

**Wsparcie przeglądarek jest niepełne i to determinuje całą resztę.** Dane z MDN
(`browser-compat-data`, wrzesień 2026): Chrome i Edge od 115, Safari od 26,
Firefox — nadal tylko w wersjach testowych. Do tego iOS starszy niż 26 nie ma
tego wcale, a na telefonach to spory kawałek ruchu. Wniosek: czysta ozdoba.
Stan bazowy CSS jest stanem końcowym animacji, reguły wchodzą wyłącznie
w `@supports (animation-timeline: view())`, a przeglądarka bez wsparcia pokazuje
kompletną, statyczną stronę. Nic nie zależy od tego, czy animacja się wykona.

**Ograniczenie ruchu wymaga osobnej obsługi — globalna reguła z sekcji 7 tu nie
działa.** `animation-duration: .01ms !important` skraca czas trwania, ale postęp
animacji sterowanej przewijaniem bierze się z osi czasu, nie z czasu trwania:
animacja nadal chodzi z przewijaniem, tylko szybciej. Dlatego każda reguła jest
bramkowana `@media (prefers-reduced-motion: no-preference)` — przy `reduce` nie
deklarujemy jej w ogóle. Dodatkowo do bloku `reduce` w sekcji 7 dokładamy
`animation-timeline: none !important`, który odpina element od każdej osi czasu
(także od domyślnej) i zostawia go w stanie bazowym — siatka bezpieczeństwa na
kod, który powstanie później, i na bloki Gutenberga.

Wzorzec wspólny dla wszystkich reguł:

```css
@supports (animation-timeline: view()) {
@media (prefers-reduced-motion: no-preference) {
	.cards .card {
		animation: przedszkole-wejscie 1ms linear both;
		animation-timeline: view();
		animation-range: entry 5% entry 55%;
	}
	.cards .card:nth-child(2) { animation-range: entry 12% entry 62%; }
	.cards .card:nth-child(3) { animation-range: entry 19% entry 69%; }
	.card:focus-within { animation: none; }
}
}
@keyframes przedszkole-wejscie {
	from { opacity: 0; transform: translateY(24px); }
	to   { opacity: 1; transform: none; }
}
```

Trzy rzeczy w tym wzorcu są nieoczywiste i każda kosztowałaby śledztwo:

1. `animation-timeline` musi stać **po** skrócie `animation`. Skrót zawiera oś
   czasu jako składnik resetujący i cofa ją do `auto` — deklaracja przed skrótem
   przepada bez śladu.
2. `1ms` zamiast pominięcia czasu trwania. Firefox wymaga niezerowej wartości,
   a przy braku wsparcia dla osi czasu animacja trwa milisekundę, czyli jest
   niewidoczna, zamiast błysnąć.
3. Kaskada przez przesunięty `animation-range`, nie przez `animation-delay`.
   Opóźnienie w czasie nie ma sensu, gdy zegarem jest pozycja przewijania.

`:focus-within` wyłącza animację, bo element z `opacity: 0` na początku zakresu,
na który wskoczył Tab, byłby niewidocznym celem fokusu. Przy wyłączonej animacji
wraca stan bazowy, czyli widoczny.

Animujemy `transform` i `opacity` — jedyne własności, które idą przez kompozytor.
Wyjątkiem jest cień nagłówka; jeśli będzie zamulał, przenosimy go na `opacity`
warstwy `::after`.

Parallaks w „Na skróty" przy okazji naprawia błąd: deseń bazgrołów stoi dziś na
`background-attachment: fixed`, które Safari na iOS traktuje jak `scroll`, więc na
telefonie efektu nie ma w ogóle. Deseń przenosi się na `.skroty::before`
(`inset: -15% 0`) przesuwany przez `transform` — kompozytorowo i na każdym
systemie, gdzie oś czasu działa. To jedyna zmiana strukturalna w tym podetapie;
reszta to dopisane reguły.

Odrzucone: animowanie pojedynczych ścieżek wewnątrz SVG fal (osie czasu na
elementach SVG są niepewne, a jednego `<svg>` nie da się rozbić bez nazwanych osi
i `timeline-scope`), pasek postępu czytania na tęczy (sensowny na wpisie,
bezużyteczny na stronie głównej), przypinanie sekcji.

Kolejność prac: najpierw wejścia kafelków (sam CSS, najmniejsze ryzyko), potem
parallaks, na końcu paski i nagłówek. Cień nagłówka dotyka wszystkich podstron,
więc sprawdzany osobno.

**Pułapka, której nie było w projekcie: `overflow: hidden` zabija `view()`.**
`hidden` robi z elementu kontener przewijania, a oś czasu `view()` liczy się
względem najbliższego takiego kontenera. Sekcja „Na skróty" i kadr „Skrzydeł"
przycinały nim zawartość — więc kafelki, deseń, zdjęcie i paski mierzyły się
względem pudełka, które nigdy się nie przewija, i były „widoczne" od załadowania
strony. Animacje kończyły się, zanim cokolwiek wjechało na ekran. Lekarstwo to
`overflow: clip`: przycina tak samo, kontenera nie tworzy.

Druga niespodzianka: zdjęcie w „Skrzydłach" dostało `transform`, a element
z transformacją maluje się w warstwie pozycjonowanych — zaczęło zasłaniać
wygaszoną górną krawędź kadru, która stoi przed nim w kodzie. Gradienty i hasło
dostały jawne `z-index` (sekcja 17). Ten sam mechanizm dotyczy deseniu
w „Na skróty": przeniesiony z tła sekcji do pseudoelementu zakryłby kafelki,
więc zawartość sekcji ma własną warstwę.

**Ogon tej samej sprawy, znaleziony po fakcie: wygaszenia krawędzi kadru
wychodziły ponad falę nad stopką.** Kadr jest pozycjonowany, ale bez `z-index`,
więc sam kontekstu układania nie tworzy — a wtedy `z-index: 1` z obu wygaszeń
(`::before`, `::after`) liczy się względem całej strony, nie względem kadru.
Fala nad stopką na stronie głównej wjeżdża na dolną krawędź zdjęcia ujemnym
marginesem, więc trafiała pod te wygaszenia zamiast nad nie. Na ekranie
szerszym niż 2560 px widać to było wprost: kremowa mgiełka bocznego wygaszenia
leżała na grzbiecie fali. Lekarstwo to `isolation: isolate` na `.skrzydla__kadr` —
zamyka oba wygaszenia wewnątrz kadru, nie ruszając ich kolejności względem
zdjęcia i pasków.

**Kryteria odbioru podetapu:** Firefox pokazuje kompletną stronę bez ruchu;
przy włączonym ograniczeniu ruchu nie rusza się nic w żadnej przeglądarce;
przechodzenie Tabem przez kafelki nie zostawia przezroczystych elementów;
tło „Na skróty" przesuwa się na iPhonie; przy zoomie 200% i szerokości 320 px
nic nie wychodzi poza kadr. `tools/podglad.py` robi zrzuty statyczne, więc tych
efektów nie pokaże — weryfikacja tylko w prawdziwej przeglądarce.

**Kryteria odbioru:** ✅ każdy widok przetestowany na realnej treści, na telefonie
i desktopie — strona główna, lista i pojedyncza aktualność, archiwum kategorii,
strona-rodzic z podstronami, strona-dziecko, kontakt z mapą, galeria, 404,
wyniki wyszukiwania.

---

## Etap 8 — SEO, wydajność, bezpieczeństwo

**Cel:** dopięcie po tym, jak strona działa. Nie wcześniej.

Kod rozkłada się na dwa nowe pliki motywu — `inc/seo.php` (znaczniki nagłówka
i dane strukturalne) oraz `inc/bezpieczenstwo.php` (utwardzenie instalacji) —
plus kilka filtrów w `functions.php`. Ustawienia, które żyją na serwerze,
a nie w kodzie, są zebrane na końcu etapu jako gotowe fragmenty `.htaccess`
do wklejenia przy wdrożeniu.

**Zero nowych wtyczek.** Instalacja nadal ma ich zero.

### 8.1 SEO

- [x] Poprawne `<title>` — natywne (`add_theme_support( 'title-tag' )`)
- [x] Meta description — wyliczany z treści, `inc/seo.php`
- [x] Struktura nagłówków H1→H2→H3 — sprawdzona na wszystkich widokach, poprawiona
- [x] Przyjazne adresy URL — Etap 4
- [x] Sitemap XML — natywna `wp-sitemap.xml`, bez sekcji autorów
- [x] Canonical — natywny `rel_canonical`
- [x] Open Graph — `inc/seo.php`
- [x] Dane strukturalne: `Preschool` na stronie głównej, `BlogPosting` na wpisie
- [x] `robots.txt` — natywny, z odnośnikiem do mapy witryny
- [x] `alt` przy zdjęciach — wszystkie obrazki na wszystkich widokach mają `alt`
- [-] **Wtyczka SEO — odrzucona.** Po odjęciu tego, co robi rdzeń, zostały trzy
      znaczniki w nagłówku
- [ ] Google Search Console — wymaga działającej domeny → **Etap 9**
- [ ] Instrukcja opisywania zdjęć dla personelu → **Etap 12**

**Co WordPress umie sam — sprawdzone w wygenerowanym HTML-u, nie założone.**
`<title>`, `rel=canonical`, `noindex` na wynikach wyszukiwania, mapa witryny,
`robots.txt` z odnośnikiem do niej, `loading="lazy"`, `srcset`, a od 6.8 także
reguły wstępnego pobierania (`speculationrules`). Brakowało wyłącznie opisu meta,
Open Graph i danych strukturalnych — i tylko to dopisujemy.

**Dlaczego bez wtyczki SEO.** Wtyczka (SEOPress, Yoast, Slim SEO) to ekran
ustawień, własne tabele, pola w edytorze przy każdej podstronie i cykl
aktualizacji na lata. Jedyna realna korzyść — ręczne nadpisanie opisu — jest
tu pozorna: nikt w przedszkolu nie będzie wypełniał pola „meta description".
Opis wyliczamy z treści, a pracownik, który zechce go zmienić, ma natywne pole
„Fragment" w edytorze. Gdyby kiedyś okazało się to za mało, wtyczka wchodzi
w miejsce jednego pliku, bez ruszania reszty motywu.

**Opis meta z treści, nie z `wp_trim_excerpt()`.** Rdzeń skleja nagłówek
z akapitem bez żadnego znaku między nimi („Witamy w naszym przedszkolu Jesteśmy
miejscem…"). Nagłówki nie kończą się kropką, bo w układzie strony oddziela je
odstęp — w jednej linijce opisu tego odstępu nie ma, więc kropkę dokładamy sami.

**Typ podwójny `["Preschool", "LocalBusiness"]`.** `Preschool` dziedziczy po
`EducationalOrganization`, w którym nie ma godzin otwarcia ani współrzędnych —
te należą do `LocalBusiness`. JSON-LD dopuszcza wiele typów naraz; bez tego
trzeba by wybrać między „to jest przedszkole" a „to jest miejsce, które ma adres
i godziny". Dane kontaktowe w postaci dla maszyn stoją w `przedszkole_dane_placowki()`
(`inc/helpers.php`) — **to druga kopia tych samych informacji** obok wzorca „Dane
kontaktowe", i zmiana adresu czy telefonu musi trafić w oba miejsca.

**Nagłówek kafelka zależy od tego, co stoi nad nim.** Na liście aktualności
kafelki idą wprost pod `<h1>`, więc tytuł jest `<h2>`; na stronie głównej
poprzedza je nagłówek sekcji „Aktualności", więc schodzi na `<h3>`. Wcześniej
był wszędzie `<h3>` i na trzech widokach powstawał przeskok H1→H3 — brakujący
poziom, który czytnik ekranu zgłasza jako błąd struktury. Wygląd bez zmian:
style siedzą na klasie `card__title`, nie na znaczniku.

**Mapa witryny bez autorów.** `wp-sitemap-users-1.xml` wysyłał do Google listę
kont wraz z nazwami użytkowników — połowę pary potrzebnej do zgadywania hasła.
Archiwa autorów i tak przekierowujemy (8.3), więc byłaby to mapa nieistniejących
adresów.

### 8.2 Wydajność

- [x] Rozmiar CSS i JS pod kontrolą — pomiar niżej
- [x] Brak zewnętrznych zapytań — zweryfikowany na wygenerowanym HTML-u
- [x] Optymalizacja obrazów — WebP przy wgrywaniu (Etap 7) + logo przerobione
- [x] Liczba zapytań SQL na stronę — zmierzona, 26–38
- [x] Jeden `fetchpriority="high"` na stronę, na właściwym obrazku
- [x] Limit wersji roboczych wpisu — 5, żeby baza nie puchła przez lata
- [ ] Cache stron → **Etap 9**, decyzja po pomiarze na produkcji
- [ ] Kompresja i nagłówki cache w `.htaccess` → **Etap 9**, fragment niżej
- [ ] PageSpeed / Lighthouse → **Etap 9**, pomiar ma sens dopiero po HTTPS

**Pomiar strony głównej, pierwsze wejście (pusty cache):**

| Zasób | Rozmiar | Uwaga |
|---|---|---|
| HTML | 12 kB | po kompresji |
| `style.css` | 23 kB | po kompresji, ze 72 kB źródła |
| `nav.js` | 0,5 kB | po kompresji |
| Nunito (woff2) | 35 kB | jeden krój, lokalnie |
| Ilustracja powitalna | 115 kB | WebP, `srcset` — telefon bierze mniejszą |
| Zdjęcie w „Skrzydłach" | 66 kB | WebP, `srcset` |
| Logo | 17 kB | WebP |
| Reszta (miniatury, zastępnik) | 36 kB | |
| **Razem** | **303 kB** | 2 arkusze + 1 skrypt + font + obrazki |

Obrazki to 82% wagi strony — i tak ma być. Kodu jest 35 kB, resztę widać.

**Zapytania SQL i czas bazy:** strona główna 38 zapytań / 5 ms, lista
aktualności 33 / 5 ms, wpis 33 / 5 ms, strona treściowa 26 / 4 ms. TTFB
lokalnie 35 ms. Mierzone tymczasową wtyczką `mu-plugins` z `SAVEQUERIES`,
usuniętą po pomiarze — Query Monitor nie jest potrzebny, a na produkcji
nie ma po nim wchodzić.

**Logo: PNG 34 kB → WebP 17 kB.** Trafiło do biblioteki przed filtrem
konwertującym wgrywane zdjęcia (Etap 7), więc jako jedyny stały zasób strony
zostało w starym formacie. Wgrane jeszcze raz, tą samą drogą co wszystko inne.
Pozostałe pliki JPEG w bibliotece to dwa stockowe zastępniki kadry i jeden
plik testowy (`meme_motherofgod.jpeg`) — znikają razem z treścią zastępczą,
nie ma czego konwertować.

**Jeden `fetchpriority="high"` na stronę.** WordPress sam wskazuje przeglądarce
jeden obrazek jako najważniejszy do pobrania i trafiał nim w logo, bo jest
pierwsze w kodzie. Logo waży kilkanaście kilobajtów i nigdy nie jest największym
elementem widocznym po wejściu. Samo wycięcie atrybutu nie wystarczyłoby: rdzeń
trzyma osobną flagę „priorytet przyznany" i zdjąłby ją przy logo, więc kolejne
obrazki nie dostałyby wskazania tak czy owak. Flagę oddajemy z powrotem —
i priorytet trafia tam, gdzie trzeba: w ilustrację powitalną na stronie głównej
i w zdjęcie wyróżniające na wpisie.

**Czego nie robimy.** Minifikacji CSS — plik jest komentowany celowo, a projekt
nie ma i nie będzie miał kroku budowania; kompresja serwera załatwia 72 kB → 23 kB.
Łączenia arkuszy — jest jeden. Wtyczki cache na zapas — przy 35 ms TTFB i zerze
zapytań zewnętrznych nie ma czego przyspieszać, a każda wtyczka cache to nowa
klasa błędów („dlaczego nie widzę zmian"). Decyzja po pomiarze na produkcji.

### 8.3 Bezpieczeństwo

- [x] `DISALLOW_FILE_EDIT` — w motywie, nie w `wp-config.php`
- [x] XML-RPC wyłączone — Etap 3
- [x] Ukrycie wersji WP — Etap 3
- [x] Hasła aplikacji wyłączone
- [x] Lista kont w REST API tylko dla zalogowanych
- [x] Archiwa autorów przekierowane — koniec z `/?author=1`
- [x] Komunikat błędu logowania bez podpowiedzi
- [x] Ograniczenie prób logowania — 5 na kwadrans z jednego adresu
- [x] Awatary wyłączone — koniec z odpytywaniem Gravatara z panelu
- [x] Walidacja i escapowanie we własnym kodzie — konwencja od Etapu 3
- [x] Minimum wtyczek — zero
- [ ] Blokada wykonywania PHP w `wp-content/uploads` → **Etap 9**, fragment niżej
- [ ] Uprawnienia plików 644/755 → **Etap 9**
- [ ] Mocne hasła i plan aktualizacji → **Etap 12**
- [ ] 2FA dla administratora → **decyzja klienta**, patrz niżej

**Model zagrożenia.** Strona placówki publicznej bez sklepu, płatności i kont
rodziców. Realne zagrożenie to boty, nie napastnik z celem. Boty robią masowo
trzy rzeczy: zgadują hasła na `wp-login.php`, zbierają nazwy użytkowników
i szukają dziur we wtyczkach. Na trzecie odpowiada zero wtyczek, na dwa
pierwsze — ten podetap.

**`DISALLOW_FILE_EDIT` w motywie, wbrew zwyczajowi.** Kanonicznym miejscem jest
`wp-config.php`, ale ten plik jest poza repozytorium (dane dostępowe do bazy)
i przy wdrożeniu powstaje na serwerze od nowa — czyli jest to dokładnie ta
stała, którą najłatwiej zgubić. W motywie jedzie razem z kodem. WordPress
sprawdza ją przy budowaniu menu panelu i w `map_meta_cap`, długo po wczytaniu
`functions.php`. Zweryfikowane: `/wp-admin/theme-editor.php` zwraca 403.

**Archiwa autorów: bezpieczeństwo i treść naraz.** `/?author=1` przekierowywało
na `/author/<login>/` i tym samym zdradzało nazwę konta. Przy okazji: autorem
bywa konto grupowe („Żabki"), a wpisy tej grupy są już pod adresem kategorii —
archiwum autora było drugą listą tego samego pod innym adresem. Przekierowanie
musi mieć priorytet **1**, przed `redirect_canonical` (10); przy domyślnym
priorytecie rdzeń zdążyłby najpierw przekierować na `/author/<login>/` i login
wyciekłby nagłówkiem `Location` — czyli dokładnie to, czemu zapobiegamy.

**Ograniczenie prób logowania bez wtyczki.** Pięć nieudanych prób z jednego
adresu blokuje logowanie na kwadrans. Kilkadziesiąt linijek na liczniku
w `transient`, bez ekranu ustawień, własnej tabeli i cyklu aktualizacji przez
najbliższe lata. Trzy rzeczy warte zapamiętania:

1. **Licznik trzyma parę: liczbę prób i moment wygaśnięcia.** Sam licznik nie
   wystarcza, bo `set_transient` na istniejącym kluczu odmierza czas od nowa —
   przy ciągłym ostrzale kwadrans nigdy by nie minął, a blokada zostałaby
   na zawsze.
2. **Adres bierzemy wyłącznie z `REMOTE_ADDR`.** Nagłówka `X-Forwarded-For`
   nadaje klient, więc bot ustawiałby sobie nowy przy każdej próbie i licznik
   nigdy nie doszedłby do limitu. Cena: gdyby przed serwerem stanął kiedyś CDN
   albo proxy, wszystkie próby zliczą się jako jeden adres i limit zablokuje
   logowanie wszystkim naraz. Wtedy — i tylko wtedy — ten filtr wymaga poprawki.
3. **Ogólny komunikat błędu podmienia tylko kody dotyczące danych logowania.**
   Filtr `login_errors` zastąpiłby wszystko, łącznie z komunikatem o blokadzie:
   pracownik zobaczyłby „nieprawidłowe hasło" i wpisywał kolejne, zamiast
   dowiedzieć się, że ma odczekać kwadrans. Dlatego `wp_login_errors`
   i podmiana po kodzie błędu.

To nie jest zapora. Atak rozproszony po tysiącu adresów przejdzie przez to bez
przeszkód. To odpowiedź na to, co dzieje się naprawdę: jeden skrypt waląc
słownikiem w `wp-login.php` całą dobę.

**Awatary wyłączone.** Na froncie nie pokazujemy żadnego (Etap 7), ale panel
owszem — na liście wpisów i w profilu. Każde wejście wysyłało zahaszowane adresy
e-mail pracowników do Gravatara. Strona z założenia nie odpytuje nikogo
na zewnątrz, a tu chodzi dodatkowo o dane pracowników.

**Czego świadomie nie robimy.** Zapory aplikacyjnej (WAF) i skanera plików —
to wtyczki, które przy pięciu kontach i braku danych osobowych na stronie
kosztują więcej, niż dają. Ukrywania adresu panelu — bot i tak trafi
w `wp-login.php`, a pracownik zgubi adres logowania. Usuwania numerów wersji
z adresów plików — to zabezpieczenie przez zaciemnienie, które przy okazji psuje
odświeżanie cache po aktualizacji.

**2FA — do decyzji klienta.** WordPress nie ma tego w rdzeniu, więc oznacza
wtyczkę. Sensowna dla konta administratora, uciążliwa dla nauczycielki, która
wchodzi raz w miesiącu dodać wpis. Propozycja: 2FA tylko dla administratora,
reszta na mocnych hasłach. Do rozstrzygnięcia przy szkoleniu (Etap 12).

### 8.4 RODO i dostępność prawna

- [x] Polityka prywatności — strona istnieje, wskazana w Ustawieniach → Prywatność
- [x] Brak ciasteczek dla niezalogowanego odwiedzającego — zweryfikowane
- [ ] **Deklaracja dostępności do napisania od nowa** — patrz niżej
- [ ] Przegląd polityki prywatności pod kątem nowej strony → **Etap 9**

**Baner ciasteczek nie jest potrzebny.** Odpowiedź serwera dla niezalogowanego
odwiedzającego nie zawiera ani jednego `Set-Cookie`, a strona nie ładuje niczego
z cudzych serwerów: brak analityki, brak osadzonych map (Etap 7), brak wtyczek
społecznościowych, font lokalnie. Zgody wymagają ciasteczka inne niż niezbędne —
tych po prostu nie ma. To wynik decyzji z poprzednich etapów, nie osobna praca.
**Warunek:** gdy kiedykolwiek dojdzie Google Analytics, osadzony film albo mapa,
baner staje się obowiązkowy.

**Deklaracja dostępności jest nieaktualna i musi zostać napisana od nowa.**
Strona `/deklaracja-dostepnosci/` przyszła z migracji i opisuje **starą** stronę:
oświadczenie sporządzone 23 września 2020 r., data publikacji 1 października
2016 r., a wśród niezgodności „brak opisów zdjęć, tekstu alternatywnego"
i „brak możliwości zmiany rozmiaru tekstu" — czyli rzeczy, których nowa strona
nie ma. Ustawa z 4 kwietnia 2019 r. o dostępności cyfrowej wymaga od podmiotu
publicznego deklaracji zgodnej ze stanem faktycznym, przeglądanej co roku.
Do zmiany:

- data publikacji nowej strony i data sporządzenia deklaracji
- status zgodności wynikający z nowej samooceny (WCAG 2.1 AA)
- lista niezgodności — obecna dotyczy strony, która przestanie istnieć
- osoba kontaktowa: dziś wskazany jest prywatny adres `@poczta.fm`,
  powinien być adres służbowy placówki
- dostępność architektoniczna — przenieść bez zmian, dotyczy budynku

Treść pisze przedszkole; deklaracja jest oświadczeniem podmiotu publicznego,
a nie elementem motywu. Z naszej strony: samoocena techniczna (Etap 11).

### 8.5 Rozmiar tekstu i wysoki kontrast

**To nie jest wymóg ustawy.** WCAG 2.1 AA wymaga kryterium 1.4.4 (treść użyteczna
przy powiększeniu 200%, co zapewnia zwykły zoom przeglądarki) i 1.4.3 (kontrast
4,5:1, już spełniony). Przełącznik „A / A+ / A++" i tryb wysokiego kontrastu to
konwencja polskiego sektora publicznego, nie przepis. Robimy, bo jest powszechnie
oczekiwana, audytorzy z listą kontrolną o nią pytają, a stara deklaracja wprost
wymieniała jej brak jako niezgodność.

#### Co sprzyja w obecnym motywie

- **Zero rozmiarów czcionki w `px`** — 38 deklaracji `font-size`, wszystkie `rem`
  albo preset. Skalowanie przez `html { font-size }` zadziała bez wyjątków
- Płynna typografia z `theme.json` ma granice w `rem`, więc też się skaluje.
  Sprawdzone na wygenerowanym CSS żywej instalacji:
  `--wp--preset--font-size--medium: clamp(1rem, 1rem + ((1vw - 0.2rem) * 0.123), 1.0625rem)`
- **144 użycia `--wp--preset--color--*`** — redefinicja 15 zmiennych w jednym
  bloku przykrywa większość motywu
- `"custom": false` i `"defaultPalette": false` w `theme.json` — redaktor **nie może**
  wybrać dowolnego koloru, więc treść z Gutenberga idzie przez presety.
  Wysoki kontrast obejmie wpisy i strony za darmo

#### Co przeszkadza

- **68 zaszytych hexów + 12 `rgba()`** w `style.css`. Z tego ~33 to duplikaty
  palety: `#fff` ×18, `#2E2390` ×6 (hover granatu), `#ECEAFB` ×3, `#5F5869` ×3,
  `#3D2FB5` ×2
- **`--header-h: 76px`** sztywne. Przy 150% tekstu nagłówek urośnie, a
  `scroll-padding-top` i `max-height: calc(100svh - var(--header-h))` panelu
  mobilnego rozjadą się z rzeczywistością
- **8 `data:image/svg`** w CSS z wtopionymi kolorami, plus inline SVG
  w `template-parts/dlaczego-my.php`, `fala.php`, `chmurki.php`
- `contentSize: 760px` sztywne — przy dużym tekście linijka robi się krótsza

#### Decyzje

**Stan na `<html>`, nie na `<body>`** — atrybuty `data-rozmiar` i `data-kontrast`.
`html { font-size }` jest korzeniem skalowania, a wczesny skrypt działa w `<head>`,
gdy `<body>` jeszcze nie istnieje.

**`localStorage`, nie ciasteczko.** Sekcja 8.4 opiera brak banera cookies na
zweryfikowanym fakcie: zero `Set-Cookie` dla niezalogowanego. Ciasteczko preferencji
byłoby prawdopodobnie zwolnione ze zgody jako niezbędne, ale nie ma powodu
podważać czystego ustalenia.

**Blokujący skrypt inline w `wp_head`.** Bez niego strona maluje się w normalnych
barwach i dopiero potem przeskakuje na czarne tło. Miganie jest gorsze niż brak
funkcji dla osoby światłoczułej. Świadomy wyjątek od zasady „bez inline",
z komentarzem uzasadniającym w kodzie. Kilkanaście linii, zero zapytań.

**`prefers-contrast: more` uruchamia ten sam blok** — kto ma wysoki kontrast
ustawiony w systemie, dostaje go bez klikania.

#### Etapy

**A. Tokenizacja kolorów — bez zmian wizualnych** ✅ 2026-09-15
- [x] 26 zaszytych hexów w deklaracjach → tokeny. Szacunek „33" był zawyżony:
      `#ECEAFB` ×3, `#5F5869` ×3, `#3D2FB5` ×2, `#2B2733`, `#FDFAF5` siedzą
      **wyłącznie w komentarzach** dokumentujących policzone kontrasty — zostają,
      to informacja, nie kod
- [x] `--primary-hover: #2E2390` — 6 użyć (`theme.json` ustawia hover na przycisku
      bloku, ale nie wystawia go jako presetu)
- [x] `--tekst-na-ciemnym: #FFFFFF` — 17 użyć. Osobno od `--surface`, bo to rola
      tekstu, nie tła: etap D przestawia jedno bez drugiego
- [x] `--tekst-na-ciemnym-slaby: #EDEBF8` — tekst stopki na granacie
- [x] Dwa `#fff`/`#FFFFFF` w roli tła (plama pod ilustracją, gradient karty)
      → `--wp--preset--color--surface`
- [x] Zostaje ~35 hexów faktycznie unikalnych: pastele grup i kolory ilustracji

**Odbiór:** zamiast zrzutów — dowód mechaniczny. Rozwinięcie nowych tokenów
z powrotem do wartości daje plik **bajt w bajt identyczny** z oryginałem,
więc zmiana jest wizualnie neutralna z definicji, nie z oględzin.

**B. Jednostki odporne na skalowanie tekstu**
- [ ] `--header-h` → `rem`, sprawdzenie obu miejsc, które z niego liczą
- [ ] Przegląd odstępów w `px`, które trzymają tekst
- [ ] Punkty łamania zostają w `px` — celowo, żeby powiększenie tekstu
      nie przerzucało układu na mobilny

**Odbiór:** `html { font-size: 150% }` w devtools — nic nie ucieka, nic nie nachodzi,
brak poziomego paska przewijania przy 320px.

**C. Przełącznik rozmiaru tekstu**
- [ ] Pasek narzędzi w `header.php`, nad `.site-header`, po `skip-link`
- [ ] Trzy przyciski `A` / `A+` / `A++`, `aria-pressed` na aktywnym,
      całość w `role="group"` z `aria-label`
- [ ] `assets/js/dostepnosc.js` — czysty JS, wzorowany na `nav.js`
- [ ] `przedszkole_dostepnosc_skrypt_wczesny()` wpięty w `wp_head`
- [ ] Skoki 100% / 125% / 150%

**D. Wysoki kontrast**
- [ ] Przycisk przełącznika obok rozmiaru, `aria-pressed`
- [ ] Paleta: tło `#000000`, tekst `#FFFFFF` (21:1), linki i akcenty `#FFFF00`
      (19,6:1), obramowania `#FFFFFF`
- [ ] **Linki zawsze podkreślone** — w monochromie kolor nie może być
      jedynym wyróżnikiem
- [ ] Dekoracje (fale, chmurki, skrzydła, plamy 404) ukryte; wszystkie mają już
      `aria-hidden="true"`, więc nic nie ginie z treści
- [ ] Kolory grup zwijają się do monochromu — grupy są i tak podpisane tekstem
- [ ] Pierścień focusa żółty, grubszy

**E. Testy i domknięcie**
- [ ] **Policzenie kontrastów**, nie oszacowanie
- [ ] Klawiatura, czytnik ekranu, zoom przeglądarki 200% osobno
      (to jest właściwe kryterium 1.4.4)
- [ ] Odświeżenie `languages/przedszkole.pot` o nowe ciągi
- [ ] `Version:` w `style.css` i `PRZEDSZKOLE_VERSION` podbite razem
- [ ] Wynik wchodzi do samooceny pod deklarację dostępności (8.4)

#### Ryzyka

| Ryzyko | Odpowiedź |
|---|---|
| Skrypt inline łamie konwencję | Jedyny sposób na brak migania; komentarz z uzasadnieniem w kodzie |
| Wysoki kontrast nie działa w edytorze | Celowo — to funkcja frontu, nie podglądu redaktora |
| Zdjęcia w wysokim kontraście | Zostają bez filtra, dostają obrys; przygaszanie fotografii pogarsza czytelność |
| Pasek narzędzi psuje układ nagłówka na telefonie | Etap B przed C — najpierw odporność na skalowanie, potem nowy element |

Etapy A i B nie zmieniają niczego widocznego, więc idą osobnymi commitami.

### Do wklejenia przy wdrożeniu (Etap 9)

Kompresja i nagłówki cache — do głównego `.htaccess`, **poza** blokiem
`# BEGIN WordPress` (rdzeń nadpisuje tamten blok przy zmianie ustawień
permalinków):

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
```

Blokada wykonywania PHP w katalogu uploadów — plik
`wp-content/uploads/.htaccess`. Gdyby komukolwiek udało się wgrać tam plik
z kodem, serwer odda go jako tekst zamiast uruchomić:

```apache
<FilesMatch "\.(?i:php|phtml|phar)$">
	Require all denied
</FilesMatch>
```

Ochrona plików, które nie są treścią — do głównego `.htaccess`:

```apache
<FilesMatch "^(wp-config\.php|\.htaccess|readme\.html|license\.txt|xmlrpc\.php)$">
	Require all denied
</FilesMatch>
```

**Kryteria odbioru:** ✅ (część serwerowa przechodzi do Etapu 9)
— wszystkie widoki mają opis meta, Open Graph i jedno `<h1>` bez przeskoków
poziomów; mapa witryny bez sekcji autorów; `/?author=1` nie zdradza loginu;
`/wp-json/wp/v2/users` anonimowo zwraca 404, a po zalogowaniu 200;
`/wp-admin/theme-editor.php` zwraca 403; szósta nieudana próba logowania jest
odcinana z komunikatem o kwadransie, a poprawne hasło zeruje licznik; przejście
po wszystkich widokach nie zostawia nic w `debug.log`; odpowiedź dla
niezalogowanego nie zawiera `Set-Cookie` ani adresu spoza domeny.

---

## Etap 9 — Wdrożenie na serwer

**Cel:** przeniesienie gotowej strony z laptopa na cyber_Folks.

### 9.1 Przygotowanie serwera
- [ ] PHP → 8.5 w panelu
- [ ] `upload_max_filesize` / `post_max_size` → min. 16M
- [ ] SSL Let's Encrypt → włączony + wymuszony HTTPS
- [ ] Nowa baza MySQL + użytkownik z ograniczonymi uprawnieniami

### 9.2 Usunięcie starej strony
**Nieodwracalne — wykonać checklistę z Etapu 1 w całości przed kasowaniem.**
- [ ] Checklista „Usunięcie starej strony" (Etap 1) odhaczona w 100%
- [ ] Materiały odzyskane i przeniesione do nowej strony
- [ ] Usunięcie plików starej strony
- [ ] Usunięcie nieużywanej bazy

### 9.3 Przeniesienie
- [ ] Eksport bazy lokalnej (`ddev export-db`)
- [ ] Zamiana adresów w bazie: lokalny → produkcyjny (`wp search-replace`, uwaga na dane serializowane)
- [ ] Import bazy na serwer przez phpMyAdmin
- [ ] Wgranie plików przez FTP: rdzeń WP + motyw + wtyczki + uploads
- [ ] **Motyw jako zwykły katalog, nie symlink** — symlink działa tylko lokalnie
- [ ] `wp-config.php` produkcyjny: dane bazy, nowe klucze (salts), prefiks tabel, `DISALLOW_FILE_EDIT`, `WP_DEBUG` = false
- [ ] `.htaccess` z regułami permalinków
- [ ] `.htaccess`: kompresja, nagłówki cache, ochrona plików — fragmenty w Etapie 8
- [ ] `wp-content/uploads/.htaccess` — blokada wykonywania PHP, fragment w Etapie 8
- [ ] Uprawnienia plików: 644 pliki / 755 katalogi
- [ ] `DISABLE_WP_CRON` + cron systemowy co 15 minut (WP-Cron chodzi przy wejściach)

### 9.4 Usunięcie treści testowej

W lokalnej instalacji jest treść wygenerowana na potrzeby testów motywu.
Nie może trafić na produkcję.

- [x] ~~6 przykładowych wpisów~~ — usunięte 2026-09-14 razem z dwoma obrazkami
      z biblioteki mediów. W bazie zostało 428 wpisów, wszystkie z migracji
      (każdy ma metadaną `_joomla_id`)
- [x] ~~3 ilustrowane awatary w kafelkach kadry~~ — usunięte 2026-09-14,
      kafelki wróciły do inicjałów. Zostają 2 stockowe portrety
- [x] ~~Strona „O przedszkolu" z treścią wypełniaczową~~ — usunięta 2026-09-14
      razem ze stroną „Oferta" i memem z biblioteki mediów. Biblioteka trzyma
      już tylko logo i dwa stockowe zastępniki kadry
- [x] ~~4 strony puste (Grupy, Dla rodziców, Aktualności, Strona główna)~~ —
      potwierdzone 2026-09-15: wszystkie mają docelową treść, żadna nie jest
      pusta. Menu bez martwych pozycji
- [x] ~~Strony z jadłospisem i harmonogramem logopedy zawierają dane z 2025/2026~~ —
      decyzja 2026-09-15: to treść bieżąca, którą personel aktualizuje sam
      po wdrożeniu. Nie blokuje startu
- [x] ~~Treść zastępcza na stronie głównej („Witamy w naszym przedszkolu…")~~ —
      potwierdzona 2026-09-15 jako docelowa
- [ ] Konto `dev` — **usunąć**, nie przenosić na produkcję
- [ ] Baza robocza `joomla` — nie migruje na serwer, zostaje lokalnie

```bash
# Podgląd przed usunięciem
ddev exec wp --path=wp post list --post_type=post --fields=ID,post_title
```

### 9.5 Po wdrożeniu
- [ ] Konta użytkowników dla realnych osób (mocne hasła)
- [ ] Usunięcie lokalnego konta roboczego
- [ ] Przejście po wszystkich podstronach — czy działają
- [ ] Sprawdzenie, czy zdjęcia się ładują (ścieżki!)
- [ ] Test formularza kontaktowego na produkcji
- [ ] `robots.txt` + indeksowanie włączone (WP potrafi blokować — sprawdzić Ustawienia → Czytanie)
- [ ] Google Search Console: potwierdzenie własności + zgłoszenie `wp-sitemap.xml`
- [ ] Deklaracja dostępności napisana od nowa (Etap 8.4 — lista zmian)
- [ ] Przegląd polityki prywatności pod kątem nowej strony
- [ ] Pomiar PageSpeed / Lighthouse na produkcji — cel: zielone Core Web Vitals
- [ ] Przekierowania ze starych adresów, jeśli stara strona była indeksowana w Google

**Kryteria odbioru:** strona działa pod docelową domeną po HTTPS, identycznie jak lokalnie,
bez treści testowej i bez konta `dev`.

---

## Etap 10 — Backup

**Cel:** da się odtworzyć stronę po awarii. Sprawdzone, nie założone.

- [ ] Backup bazy MySQL — automatyczny
- [ ] Backup plików, zwłaszcza `wp-content/uploads`
- [ ] Backup przechowywany **poza** katalogiem strony (inne konto / chmura)
- [ ] Harmonogram: baza codziennie, pliki tygodniowo (do dostosowania)
- [ ] Retencja: minimum 30 dni
- [ ] Weryfikacja backupu hostingu (zasady + retencja + jak odtworzyć)
- [ ] **Testowe odtworzenie backupu** — backup nieprzetestowany to brak backupu
- [ ] Spisana procedura odtworzenia

---

## Etap 11 — Testy i odbiór

- [ ] Desktop / tablet / telefon
- [ ] Chrome, Safari, Firefox, Edge
- [ ] Formularz kontaktowy — mail dochodzi
- [ ] Upload zdjęcia przez użytkownika nietechnicznego
- [ ] Wstawienie linku do albumu we wpisie przez nauczycielkę (wzorzec „Link do albumu”)
- [ ] Dodanie aktualności przez nauczyciela
- [ ] Edycja istniejącej strony
- [ ] Utworzenie nowej podstrony + dodanie do menu
- [ ] Zmiana kolejności menu
- [ ] Strona 404
- [ ] Wyszukiwarka
- [ ] Linki wewnętrzne — brak martwych
- [ ] Lighthouse: wydajność / dostępność / SEO
- [ ] Test ról: każde konto widzi to, co powinno
- [ ] Odtworzenie backupu
- [ ] Poprawne wyświetlanie polskich znaków
- [ ] Sprawdzenie strony na wolnym łączu mobilnym

---

## Etap 12 — Przekazanie klientowi

- [ ] Krótka instrukcja obsługi (jak dodać wpis, zdjęcia, galerię, stronę) — max 2–3 strony, ze zrzutami ekranu
- [ ] Szkolenie dla personelu (30–60 min)
- [ ] Przekazanie danych dostępowych w bezpieczny sposób
- [ ] Spisanie: co robić przy aktualizacjach, kogo pytać o pomoc
- [ ] Dokumentacja techniczna motywu (krótka, dla przyszłego developera/AI)

---

## Rejestr decyzji

| Data | Decyzja | Uzasadnienie |
|---|---|---|
| 2026-09-11 | WordPress zamiast własnego CMS-a | WP daje logowanie, role, media, menu, aktualizacje od ręki |
| 2026-09-11 | Własny lekki motyw zamiast gotowego | wydajność, brak zbędnego kodu, łatwość modyfikacji |
| 2026-09-11 | Gutenberg zamiast page buildera | natywny, bez vendor lock-in, lżejszy |
| 2026-09-11 | Brak architektury headless | zbędna złożoność dla jednej strony wizytówkowej |
| 2026-09-11 | Hosting: cyber_Folks, bez zmian | wystarczający: memory 1 GB, .htaccess, GD+ImageMagick, backup 28 dni |
| 2026-09-11 | PHP 8.5 | najnowsza dostępna; WP core kompatybilny od 6.9; wtyczki weryfikować pojedynczo |
| 2026-09-11 | Stara strona z FTP do usunięcia | po pełnym backupie i odzyskaniu materiałów (logo, zdjęcia, teksty) |
| 2026-09-11 | Budujemy najpierw lokalnie | szybciej, bez ryzyka dla produkcji, stara strona może stać do końca |
| 2026-09-11 | DDEV zamiast MAMP/LocalWP | Docker już jest; pliki w repo, wbudowany wp-cli, PHP 8.5, blisko produkcji |
| 2026-09-11 | Menu zagnieżdżone, 8 pozycji górnego poziomu | płaskie 13 pozycji nie mieści się w poziomym menu |
| 2026-09-11 | „Projekty" nie wracają jako sekcja menu | 12 artykułów w 3 lata, rozbite na 6 podgałęzi — gałąź martwa |
| 2026-09-11 | „Dofinansowanie" zostaje na górnym poziomie | wymagania zewnętrzne wymuszają widoczność |
| 2026-09-11 | Kategorie: 6 grup + Ogłoszenia | tyle ma pokrycie w 440 migrowanych artykułach; podział na Ogłoszenia/Wydarzenia wymagałby ręcznej pracy na 113 wpisach |
| 2026-09-11 | Galerie jako zwykłe strony z linkami | właściwe albumy są w Google Photos, własny typ treści nic nie wnosi |
| 2026-09-11 | Polityka prywatności przez natywny mechanizm WP | WP sam dokłada `rel="privacy-policy"` i pilnuje strony w Ustawieniach |
| 2026-09-11 | Wzorce bloków zamiast własnych bloków Gutenberga | pokrywają potrzeby bez linii JS do utrzymania |
| 2026-09-11 | Warianty stylów bloków zamiast klas wpisywanych ręcznie | pracownik wybiera z listy, nie pisze HTML-a |
| 2026-09-11 | Wzorce z wordpress.org wyłączone | zero zapytań zewnętrznych + krótka, polska lista w edytorze |
| 2026-09-11 | Szerokie wyrównania (`alignwide`/`alignfull`) zostają wyłączone | front ich nie obsłuży bez przebudowy szablonów na siatkę |
| 2026-09-11 | Link do albumu otwiera się w tej samej karcie | nowe okno bez uprzedzenia łamie WCAG 3.2.5 |
| 2026-09-11 | Lista bloków nieograniczana (na razie) | decyzja po szkoleniu, na podstawie realnych trudności personelu |
| 2026-09-11 | `#ddev-generated` zdjęte z `wp/wp-config.php` | inaczej ddev kasował `WP_DEBUG` przy każdym starcie; dane bazy nadal z `wp-config-ddev.php` |
| 2026-09-11 | Mapa treści z tabeli `l6hwz_menu`, nie z tytułów artykułów | menu Joomli jednoznacznie wiąże pozycję z artykułem; dopasowanie po tytule dawałoby trafienia w aktualnościach |
| 2026-09-11 | Rozkład dnia: trzy warianty na jednej stronie | stare menu miało trzy osobne pozycje pod separatorem — jedna strona z nagłówkami jest prostsza w utrzymaniu |
| 2026-09-11 | Kadra jako jedna strona składana z 15 artykułów | stara strona rozbijała ją na 15 podstron po jednej osobie — nadmiar nawigacji przy 15 krótkich biogramach |
| 2026-09-11 | Kafelek osoby jako wariant stylu `core/columns`, nie własny blok | okrągłe zdjęcie + biogram składają się ze zwykłych bloków; personel podmienia treść bez pisania HTML-a |
| 2026-09-11 | Trzy gradientowe koła z jednego pliku SVG odbijanego w CSS | trzy układy bez trzech plików; `:nth-child(… of S)` liczy same kafelki, więc nagłówki między nimi nie psują kolejności |
| 2026-09-11 | Inicjały w kółku, dopóki nie ma zdjęć kadry | 5 fotografii wisi na FTP starej strony; kafelek bez zdjęcia wyglądałby jak dziura, a podmiana na zdjęcie nie zmienia kadru ani stylu |
| 2026-09-11 | Druga linia kafelka to wykształcenie, nie stopień awansu | przypisania do grup i stopnie pochodzą ze starej strony i zdążyły się zestarzeć; wykształcenie jest w treści u wszystkich 14 osób |
| 2026-09-11 | Administracja i obsługa zostaje listą, bez kafelków | 11 osób opisanych jedną linijką („Beata Konieczna — sekretariat") nie ma czym wypełnić kafelka |
| 2026-09-11 | Biogramy wchodzą do kafelków w całości | skrót do 2–3 zdań wyrównałby wysokości, ale trzeba by gdzieś przenieść resztę; przy biogramie dłuższym niż 900 znaków zdjęcie idzie na górę kafelka |
| 2026-09-11 | Obrazki wycięte z migrowanej treści | 5 plików leży na FTP starej strony; treść ma wejść teraz, zdjęcia dołożymy po pobraniu |
| 2026-09-11 | Nie odtwarzamy struktury kategorii z Joomli | narosła organicznie: literówki, rok szkolny w roku szkolnym, puste archiwa |
| 2026-09-11 | Migracja: lata szkolne 2023/24–2025/26 (440 wpisów) | rok szkolny to naturalna jednostka dla przedszkola |
| 2026-09-11 | Nunito jako krój pisma | SIL OFL 1.1 — wolna licencja, dopuszczalna dla placówki publicznej; hostowana lokalnie, bez CDN |
| 2026-09-11 | Pastelowa paleta grup zamiast nasyconej | decyzja klienta; każdy kolor w trzech odcieniach, kontrast zweryfikowany |
| 2026-09-11 | Galerie zostają w Google Photos | konto należy do przedszkola, więc brak ryzyka utraty; oszczędza ~35 GB i duży nakład pracy |
| 2026-09-11 | Konta autorów grupowe, nie imienne | decyzja klienta; kompromis: współdzielone hasło, brak rozliczalności — odnotowany w MIGRACJA.md |
| 2026-09-11 | Rok szkolny z daty publikacji, nie z kategorii | WP ma archiwa po dacie natywnie; odpada kilkadziesiąt pustych kategorii |
| 2026-09-11 | Kafelek skrótu na ciemniejszym wariancie barwy grupy | biały napis na pastelu z `theme.json` ma kontrast 1,6–3,1, ustawa wymaga 4,5; przyciemnione warianty dają ponad 4,6 przy tym samym odcieniu |
| 2026-09-11 | Kształt fali jako parametr, nie osobny plik SVG | fala ma kolor sekcji, która po niej następuje — plik z wypalonym kolorem trzeba by trzymać w kilku wersjach |
| 2026-09-11 | Kafelki „Na skróty" z menu, nie z kodu | dyrekcja zmienia zestaw i opisy w panelu; motyw dobiera tylko ikonę i kolor |
| 2026-09-11 | Jeden `index.php` zamiast `home.php`, `archive.php` i `index.php` | trzy identyczne pliki; poprawka w jednym omijała dwa pozostałe |
| 2026-09-11 | Autor wpisu ukryty, gdy powtarza nazwę kategorii | konta są grupowe, więc „Żabki · Żabki" w jednej linijce to szum |
| 2026-09-11 | PDF otwiera się w nowej karcie, album w tej samej | do pliku nie wraca się przyciskiem „wstecz"; WCAG G201 dopuszcza nowe okno z uprzedzeniem, które dokładamy |
| 2026-09-11 | Zapowiedź linku zewnętrznego dopisywana filtrem | treść pisze personel w edytorze — ręcznych adnotacji nikt nie dopilnuje |
| 2026-09-11 | Mapa jako statyczny obrazek OpenStreetMap | osadzona mapa Google to kilkaset kB z cudzego serwera, ciasteczka i klauzula RODO; obrazek waży 43 kB i nikogo nie śledzi |
| 2026-09-11 | Zdjęcia przechodzą na WebP przy wgrywaniu, plik źródłowy kasowany | konwersja przed zmniejszaniem obejmuje każdą wielkość, także pełną; dwa formaty tego samego zdjęcia to podwójne miejsce bez pożytku |
| 2026-09-11 | Plik sprzed zmniejszenia (`original_image`) nie jest przechowywany | kilka megabajtów na pozycję w bibliotece, po które nikt nie sięga |
| 2026-09-14 | Animacje przewijania w czystym CSS, jako ozdoba | `animation-timeline` liczy przeglądarka poza wątkiem głównym; wsparcie jest niepełne (Firefox, iOS < 26), więc stan bazowy jest stanem końcowym i nic od animacji nie zależy |
| 2026-09-14 | Ograniczenie ruchu bramkowane osobno, nie globalną regułą z sekcji 7 | `animation-duration: .01ms` skraca czas, a tu zegarem jest przewijanie — animacja chodziłaby dalej, tylko szybciej |
| 2026-09-14 | `overflow: clip` zamiast `hidden` w „Na skróty" i „Skrzydłach" | `hidden` tworzy kontener przewijania, względem którego `view()` uznaje zawartość za widoczną od początku |
| 2026-09-14 | Deseń „Na skróty" w pseudoelemencie zamiast `background-attachment: fixed` | Safari na iOS traktuje `fixed` jak `scroll`, więc na telefonie efektu nie było w ogóle |
| 2026-09-11 | Próg zmniejszania zdjęć 2048 px, limit uploadu 5 MB | treść ma 1140 px; limit odcina filmy i surowe pliki z aparatu, mieści skan dokumentu i zdjęcie z telefonu |
| 2026-09-14 | SEO bez wtyczki — trzy znaczniki w `inc/seo.php` | rdzeń daje `title`, canonical, mapę witryny i `robots.txt`; wtyczka to ekran ustawień i pola do wypełniania, z których nikt nie skorzysta |
| 2026-09-14 | Typ `["Preschool", "LocalBusiness"]` w danych strukturalnych | godziny otwarcia i współrzędne są w `LocalBusiness`, nazwa rzeczy — w `Preschool`; JSON-LD dopuszcza oba naraz |
| 2026-09-14 | Opis meta liczony z treści, nie przez `wp_trim_excerpt()` | rdzeń skleja nagłówek z akapitem bez znaku między nimi |
| 2026-09-14 | Poziom nagłówka kafelka zależy od kontekstu (H2 na liście, H3 na stronie głównej) | stały H3 dawał przeskok H1→H3 na trzech widokach — błąd struktury dokumentu |
| 2026-09-14 | Mapa witryny bez sekcji autorów, archiwa autorów przekierowane | wysyłały do Google nazwy kont i duplikowały listy kategorii |
| 2026-09-14 | `DISALLOW_FILE_EDIT` w motywie, nie w `wp-config.php` | `wp-config.php` jest poza repo i powstaje na serwerze od nowa — to stała, którą najłatwiej zgubić |
| 2026-09-14 | Ograniczenie prób logowania własnym kodem, nie wtyczką | kilkadziesiąt linijek bez ekranu ustawień, tabeli w bazie i cyklu aktualizacji; wtyczka wejdzie w to miejsce, gdyby okazało się za słabo |
| 2026-09-14 | Adres IP wyłącznie z `REMOTE_ADDR`, bez `X-Forwarded-For` | nagłówek nadaje klient, więc bot omijałby licznik; za CDN-em trzeba będzie ten filtr poprawić |
| 2026-09-14 | Awatary wyłączone | panel wysyłał zahaszowane adresy e-mail pracowników do Gravatara |
| 2026-09-14 | Strona „Galeria” usunięta, menu ustawione wg częstości odwiedzin | zbiorcza lista albumów dublowała aktualności, w których każdy wpis ma własny przycisk do albumu |
| 2026-09-14 | „O przedszkolu” i „Oferta” usunięte, Kadra na górny poziom | pierwsza miała samą treść testową i brak źródła do napisania; druga jedno zdanie, które dubluje opisy grup |
| 2026-09-14 | Ilustrowane awatary kadry usunięte | rysunkowa postać pod imieniem i nazwiskiem czyta się jak portret tej osoby; inicjały nie wprowadzają w błąd |
| 2026-09-14 | Bez wtyczki cache i bez minifikacji | 35 ms TTFB, jeden arkusz, zero zapytań zewnętrznych; kompresja serwera daje 72 kB → 23 kB, a projekt nie ma kroku budowania |
| 2026-09-14 | Bez banera ciasteczek | niezalogowany odwiedzający nie dostaje ani jednego `Set-Cookie`, strona nie ładuje niczego z cudzych serwerów; warunek przestaje obowiązywać przy pierwszej analityce lub osadzonej mapie |
| 2026-09-14 | Logo przerobione na WebP przez ponowne wgranie | trafiło do biblioteki przed filtrem konwertującym; 34 kB → 17 kB na każdej podstronie |
| 2026-09-11 | Git wersjonuje tylko motyw | rdzeń WP i wtyczki to cudzy kod; symlink lokalnie, zwykły katalog na serwerze |

---

## Pytania otwarte

- [ ] Jaki dokładnie hosting i jakie parametry? (Etap 1)
- [ ] Czy klient ma logo, kolory, materiały graficzne? (część może być na starej stronie — odzyskać przed usunięciem)
- [ ] Do kogo należy konto Google z albumami zdjęć? Czy jest do niego dostęp?
- [ ] Jaki jest zakres zgód rodziców na publikację zdjęć dzieci?
- [ ] Czy są gotowe teksty, czy trzeba je napisać? (sprawdzić starą stronę)
- [ ] Która z 2 baz danych należy do starej strony?
- [x] Ile grup i jakie nazwy? → **6: Wiewiórki, Żabki, Zajączki, Misie, Kotki, Jeżyki**
- [x] Czy jadłospis to PDF, czy treść wpisywana co tydzień? → **treść na stronie**, aktualizowana przez personel po wdrożeniu
- [ ] Czy potrzebne komentarze pod aktualnościami? (domyślnie: nie)
- [ ] Domena — istniejąca czy nowa?
- [x] Zakres migracji → **lata szkolne 2023/24–2025/26, 440 wpisów**
- [x] Konta autorów → **grupowe** (`grupa-kotki` itd. + `przedszkole`), autorstwo z kategorii
- [ ] Czy stara strona jest zaindeksowana w Google → przekierowania 301?
- [ ] Czy potrzebna strefa tylko dla rodziców (logowanie)? (domyślnie: nie — komplikuje)
- [ ] Kto po wdrożeniu odpowiada za aktualizacje?
- [ ] Czy włączamy 2FA dla konta administratora? (Etap 8.3 — oznacza wtyczkę)
- [ ] Kto pisze nową deklarację dostępności? (Etap 8.4 — obowiązek ustawowy)

---

## Cel końcowy

Lekka strona WordPress dla konkretnego przedszkola:
działająca na istniejącym hostingu PHP + MySQL · własny lekki motyw · Gutenberg ·
edycja i tworzenie podstron · aktualności · galerie i upload zdjęć · dokumenty ·
role użytkowników · responsywna · szybka · bez zbędnych zależności · tania w utrzymaniu ·
łatwa do dalszej modyfikacji przez AI lub developera.

**To nie ma być idealny CMS. To ma być prosta, szybka i łatwa w utrzymaniu strona jednego przedszkola.**
