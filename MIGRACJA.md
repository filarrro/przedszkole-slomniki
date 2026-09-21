# Migracja treści: Joomla → WordPress

Dokument roboczy Etapu 2b. Analiza wykonana 2026-09-11 na zrzucie `icrdslom_dbj34_1789116422.sql.gz`.

**Stan: treść przeniesiona.** 16 stron statycznych (2026-09-11) i 428 wpisów
z aktualnościami (2026-09-14). Pliki ze starego serwera przepadły — patrz
„Pliki ze starego serwera: przepadły". Zostają przekierowania 301 i uzupełnienie
brakujących dokumentów.

---

## Co zastaliśmy

**Stara strona:** Przedszkole w Słomnikach, Joomla **3.10.5**, prefiks tabel `l6hwz_`, 83 tabele, 84 MB po rozpakowaniu.

**Domena:** `przedszkoleslomniki.pl`
**Katalog na serwerze:** `/home/icrdslom/domains/przedszkoleslomniki.pl/public_html/`

Zrzut zaimportowany lokalnie do osobnej bazy `joomla` (obok bazy WordPressa) — służy tylko do odczytu przy migracji.

```bash
ddev mysql joomla        # konsola do bazy Joomli
```

### Jak odtworzyć analizę od zera

Baza `joomla` żyje w kontenerze DDEV, a eksport artykułów był w katalogu tymczasowym.
Jedno i drugie odtwarza się z pliku zrzutu, który leży w katalogu projektu (poza repo).

```bash
# 1. Import zrzutu do bazy roboczej
ddev import-db --database=joomla --file=icrdslom_dbj34_1789116422.sql.gz

# 2. Eksport artykułów z zakresu migracji do JSON (jeden obiekt na linię)
ddev mysql -N --raw joomla -e "SELECT JSON_OBJECT(
  'id',a.id,'title',a.title,'alias',a.alias,'created',a.created,
  'modified',a.modified,'cat_path',c.path,'cat_title',c.title,
  'author',u.name,'images',a.images,'intro',a.introtext,
  'full',a.\`fulltext\`,'metadesc',a.metadesc)
FROM l6hwz_content a
LEFT JOIN l6hwz_categories c ON c.id=a.catid
LEFT JOIN l6hwz_users u ON u.id=a.created_by
WHERE a.state=1 AND a.created >= '2023-09-01'
ORDER BY a.created DESC" > artykuly.jsonl

# 3. Załączniki
ddev mysql -N --raw joomla -e "SELECT JSON_OBJECT(
  'id',id,'plik',filename,'sciezka',filename_sys,'typ',file_type,
  'rozmiar',file_size,'nazwa',display_name,'artykul',parent_id)
FROM l6hwz_attachments WHERE state=1" > zalaczniki.jsonl
```

**Uwaga:** `fulltext` jest słowem zastrzeżonym w MariaDB — wymaga odwrotnych apostrofów.
Pliki `*.jsonl` są w `.gitignore` (zawierają treści i nazwiska autorek).

### Artykuły

| Stan | Liczba |
|---|---|
| Opublikowane (wszystkie lata 2015–2026) | 1753 |
| **Opublikowane z ostatnich 3 lat** | **440** |
| Nieopublikowane | 79 |
| W koszu | 136 |
| Zarchiwizowane | 7 |

Rozkład opublikowanych wg roku: 2026 – 96 · 2025 – 123 · 2024 – 143 · 2023 – 160 · 2022 – 209 · 2021 – 285 · 2020 – 190 · wcześniejsze – 547

### Autorzy w Joomli
Bożenka – 157 · Agnieszka – 100 · Ewa – 94 · Aneta – 88 · Super User – 1

**Autorki nie pokrywają się z grupami** — każda pisze dla kilku:

| Autorka | Grupy |
|---|---|
| Bożenka | Jeżyki 49, Wiewiórki 18, Zajączki 15, Kotki 7, Misie 6, Żabki 1, bez grupy 61 |
| Ewa | Żabki 62, Misie 30, Zajączki 2 |
| Aneta | Kotki 59, Zajączki 13, Wiewiórki 5, Misie 3, bez grupy 8 |
| Agnieszka | Zajączki 18, Wiewiórki 17, Misie 3, Kotki 2, Jeżyki 2, bez grupy 58 |

Dlatego autorstwo w WordPressie wyprowadzamy **z kategorii artykułu**, nie z oryginalnego autora.

### ⚠️ Zdjęcia: prawdziwe galerie są w Google Photos

**Kluczowe ustalenie.** Na serwerze leżą wyłącznie miniatury-zajawki. Właściwe galerie
przedszkole trzyma w Google Photos i linkuje z artykułów.

Wzorzec w treści:
```html
<a href="https://photos.app.goo.gl/XXXX"><img src="images/aktualnosci/2025/foo.jpg"></a>
```
Klikalna miniaturka → album w Google Photos.

| Metryka | Wartość |
|---|---|
| Unikalne albumy Google Photos | **395** |
| Artykuły z linkiem do albumu | **387 / 440** |
| `photos.app.goo.gl` (linki skrócone) | 299 |
| `photos.google.com/share/…` | 100 |
| Artykuły z 1 albumem | 378 |
| Artykuły z 2–6 albumami | 5 |
| Artykuły bez albumu | 57 |
| Stan linków (próbka 8) | wszystkie żywe, HTTP 200 |

Inne linki zewnętrzne: pojedyncze (youtu.be, facebook, e-civitas, fundacjabos) — 4 sztuki łącznie.

### Zdjęcia lokalne (miniatury)

| Metryka | Wartość |
|---|---|
| Artykuły z obrazkiem wyróżniającym | 412 / 440 |
| Artykuły z `<img>` w treści | 391 / 440 |
| Unikalne pliki graficzne | 782 |
| Średnio zdjęć na artykuł | 0,9 |
| Artykuły z ≥5 zdjęciami | 3 |
| Formaty | 775 × .jpg, 7 × .png |

Katalogi źródłowe: `images/` (466), `images/aktualnosci/2024/` (195), `images/aktualnosci/2025/` (85), `images/modules/` (20), reszta pojedyncze.

**Wzorzec Joomli:** każdy artykuł ma dwa warianty tego samego zdjęcia — `_min` (miniatura, pole `image_intro`) i `_nor` (pełne, pole `image_fulltext`). W WordPressie potrzebujemy **tylko wersji pełnej** — miniatury WP generuje sam. To redukuje 782 pliki do ok. 412 realnych zdjęć.

To są **zajawki, nie galerie.** Same zdjęcia z wydarzeń są w Google Photos.

### Załączniki (rozszerzenie Joomla Attachments)

Osobny mechanizm, **poza treścią artykułów** — dlatego nie widać ich po linkach w HTML.

| Metryka | Wartość |
|---|---|
| Załączniki opublikowane | 118 |
| PDF | 86 |
| ODT (OpenDocument) | 14 |
| JPEG | 15 |
| DOC | 3 |
| Łączny rozmiar | 77 MB |
| Podpięte do artykułów | 66 |

Lokalizacja: `public_html/attachments/article/<id_artykulu>/<nazwa>`

**⚠️ Nie są archiwum.** 89 załączników wisi przy starszych artykułach, ale to strony typu „Dokumenty" — treści bezterminowe (`upowaznienie_do_odbioru_dziecka.pdf`, `Koncepcja Pracy`). Wciąż aktualne.
**Migrujemy wszystkie 118, niezależnie od daty artykułu.**

### Czego NIE ma (dobra wiadomość)
- Zero shortcode'ów i wtyczek galerii — treść to czysty HTML
- Brak komponentu galerii w Joomli (jedynie `mod_random_image`)
- Brak artykułów z dziesiątkami zdjęć

Migracja treści jest prosta: tytuł + HTML + data + autor + jedno zdjęcie (+ ewentualne załączniki).

---

## Struktura starej strony (menu)

```
Aktualności
  └── Rok szkolny 2015/2016 … 2024/2025   (archiwum roczne)
Kadra
  ├── Dyrektor
  ├── Nauczyciele
  ├── Specjaliści
  └── Administracja
Dla Rodziców
  ├── Warto przeczytać
  ├── Dokumenty
  ├── Jadłospis
  ├── Ramowy rozkład dnia
  └── Opłaty
Kącik Logopedy
  └── Archiwum
Dofinansowanie
Projekty
  └── Misie · Zajączki · Żabki · Kotki · Wiewiórki · Jeżyki
Kontakt
  └── Deklaracja dostępności
Grupy (osobne pozycje)
  └── Wiewiórki · Żabki · Zajączki · Misie · Kotki · Jeżyki
```

**Grupy przedszkolne (6):** Wiewiórki, Żabki, Zajączki, Misie, Kotki, Jeżyki.
Korekta wobec pierwotnego planu, który zakładał Motylki/Żabki/Pszczółki.

**⚠️ Deklaracja dostępności** — wymagana prawem od placówek publicznych (ustawa o dostępności cyfrowej). Musi trafić na nową stronę.

### Bałagan do posprzątania przy okazji
Drzewo kategorii narosło organicznie:
- literówki w nazwach: `kotkiorojekty` (zamiast `kotkiprojekty`)
- rok szkolny zagnieżdżony w roku szkolnym: `rok-szkolny-2025-2026/rok-szkolny-2023-24`
- ta sama nazwa kategorii w kilkunastu miejscach drzewa
- puste kategorie archiwalne (rocznik po roczniku, bez treści)

**Nie odtwarzamy tej struktury.** Mapujemy na czystą taksonomię WordPressa.

---

## Plan migracji

### Mapowanie treści

| Joomla | WordPress |
|---|---|
| Artykuł z `komunikaty/<grupa>/…` | Wpis (post), kategoria = nazwa grupy |
| Artykuł z `rok-szkolny-*` | Wpis, kategoria „Ogłoszenia" |
| Artykuł z `<grupa>projekty/…` | Wpis, kategoria = nazwa grupy |
| Kadra, Specjaliści, Dokumenty, Jadłospis, Opłaty, Rozkład dnia, Dofinansowanie, Deklaracja dostępności | **Strony** (nie wpisy) |
| `created` | `post_date` |
| Kategoria → grupa | `post_author` (konto grupowe) |
| `alias` | `post_name` (slug) |
| `images.image_fulltext` | ~~Obrazek wyróżniający~~ — plik przepadł z FTP |
| `introtext` + `fulltext` | `post_content` (bez `<!-- pagebreak -->`) |
| `metadesc` | ~~Meta description~~ — puste we wszystkich 440 artykułach |

**Rok szkolny** wyprowadzamy z daty publikacji, nie z kategorii — nie potrzeba archiwalnych kategorii, WordPress ma archiwa po dacie z pudełka.

### Kroki

- [x] Import zrzutu do lokalnej bazy roboczej `joomla`
- [x] Analiza: liczby, struktura, zdjęcia, autorzy
- [x] Eksport 440 artykułów do JSON
- [x] ~~Pobranie katalogu `images/` ze starego FTP~~ — **nie udało się, patrz niżej**
- [x] Ustalenie zakresu z klientem (patrz „Decyzje do podjęcia")
- [x] Skrypt konwertujący: `tools/migracja_wpisow.py`
- [x] Czyszczenie HTML: usunięcie stylów inline, `<font>`, pustych `<p>`, atrybutów Joomli
- [x] Konwersja treści na bloki Gutenberga
- [x] Skan treści pod kątem wstrzykniętego kodu — **czysto, zero trafień**
- [x] Odnośniki do albumów → przyciski `is-style-galeria` z czytelnym tekstem
- [x] Import 428 wpisów (2026-09-14)
- [x] Weryfikacja: liczba wpisów, porównanie długości treści, próbka na froncie
- [ ] ~~Upload zdjęć do Media Library~~ — **bez plików niewykonalne**
- [ ] ~~Upload załączników (118 plików)~~ — **bez plików niewykonalne**
- [ ] Mapa przekierowań 301 ze starych adresów (SEO) — wpisy mają `_joomla_id`,
      więc da się je zmapować bez ręcznej listy

### Wskazówki do skryptu migracyjnego

Ustalenia z analizy, które oszczędzą pracy przy pisaniu importu:

- **Obrazek wyróżniający:** brać `images.image_fulltext` (wersja `_nor`), nie `image_intro`
  (`_min`). WordPress sam wygeneruje miniatury — import obu wariantów podwoiłby pliki.
- **Ścieżki w polu `images`** mają escapowane ukośniki (`images\/foo.jpg`) — JSON Joomli.
- **Galerie:** wzorzec `<a href="https://photos.app.goo.gl/..."><img src="images/..."></a>`.
  Link zostaje, ale trzeba mu dodać czytelny tekst — dziś `<a>` opakowuje sam obrazek,
  więc czytnik ekranu nie ma czego przeczytać.
- **Treść** to `introtext` + `fulltext` sklejone. Joomla rozdziela je znacznikiem
  „czytaj dalej” — w WordPressie nie jest potrzebny.
- **Autorstwo** z kategorii, nie z `created_by` (patrz „Konta autorów”).
- **Kategorie:** `cat_path` zawiera pełną ścieżkę; grupę rozpoznać po segmencie
  (`komunikaty/kotki/...` → Kotki). Ścieżki `*projekty*` → kategoria „Projekty”.
  Uwaga na literówkę `kotkiorojekty`.
- **Rok szkolny** wyprowadzić z `created`, nie z kategorii.
- **6 artykułów** w zakresie ma pustą treść — zdecydować, czy pomijać.

### ❌ Pliki ze starego serwera: przepadły

**Stan na 2026-09-14.** Dostępu do FTP nie ma i nie będzie. HTTP też nie pomoże —
`przedszkoleslomniki.pl` oddaje stronę błędu 403 od cyberFolks (hosting wyłączył
witrynę), a każda ścieżka do pliku kończy się na 404:

```bash
curl -o /dev/null -w '%{http_code}\n' \
  http://przedszkoleslomniki.pl/images/aktualnosci/2023/Przywitanie_jesieni_2023_glowne.jpg
# 404
```

Co przez to tracimy:

| Zasób | Ile | Skutek |
|---|---|---|
| Obrazki wyróżniające artykułów | 407 plików | wpisy bez zdjęcia, motyw pokazuje zastępnik |
| Załączniki (PDF, ODT) | 118 plików, 77 MB | 6 wpisów pominiętych (były samą zajawką pliku), 11 zaimportowanych bez dokumentu |
| Grafiki szablonu | — | bez znaczenia, mamy własną identyfikację wizualną |

**Czego NIE tracimy:** galerii. Właściwe zdjęcia z wydarzeń są w Google Photos,
a linki do 395 albumów siedzą w treści artykułów i działają. To dlatego utrata
FTP jest kosztowna, ale nie zabójcza.

Obrazki *w treści* w zakresie 440 artykułów to praktycznie wyłącznie ikonka
`galeria.png` (312 wystąpień) — dekoracja, nie zdjęcia. Nie ma czego żałować.

### Decyzja: wpisy idą bez obrazka wyróżniającego

**Reguła klienta:** nie dorabiamy zdjęć sztucznie. Obrazek tylko wtedy, gdy treść
artykułu wskazuje na konkretne zdjęcie.

W zakresie 440 artykułów **nie ma ani jednego takiego odnośnika.** Sprawdzone:
zero `lh3.googleusercontent.com`, zero `drive.google.com`, zero `images.app.goo.gl`.
Jedyne konkretne obrazki to ścieżki z FTP i `galeria.png`. (224 odnośniki do
`drive.google.com` w bazie należą do starszych artykułów, poza zakresem, i prowadzą
do materiałów edukacyjnych, nie do zdjęć.)

Wniosek: wszystkie 428 wpisów bez obrazka, kafelek pokazuje `assets/img/brak-zdjecia.webp`.

<details>
<summary>Gdyby przedszkole zmieniło zdanie: okładki albumów da się pobrać</summary>

Sprawdzone na żywo 2026-09-14. Strona udostępnionego albumu Google Photos wystawia
metadane Open Graph — wystarczy pobrać ją z nagłówkiem `User-Agent` crawlera
(zwykła przeglądarka dostaje pustą stronę wymagającą JavaScriptu):

```bash
curl -sL -A "facebookexternalhit/1.1" https://photos.app.goo.gl/TUg4HYsFenLrHGoD7 \
  | grep -oE 'og:(image|title)" content="[^"]*"'
```

- `og:title` → tytuł albumu z datą („2023.09.28 Iluzjonista · Thursday, Sep 28, 2023")
- `og:image` → adres okładki na `lh3.googleusercontent.com`

Adres okładki przyjmuje parametr rozmiaru: `=s0` daje 1920 px zamiast domyślnych 600.
Ze strony albumu da się też wyciągnąć adresy **wszystkich** zdjęć (w próbce: 79),
więc pełna migracja galerii jest technicznie możliwa — tylko decyzja mówi inaczej.

**Pokrycie, gdyby włączyć:** 384 z 440 artykułów (te z linkiem do albumu).

**Czego to nie rozwiązuje:** okładka to prawdziwe zdjęcie z wydarzenia, czyli twarze
dzieci na listach wpisów i w podglądach linków. Stare zajawki były często grafikami.
To zmiana charakteru strony, nie tylko wypełnienie dziury — i temat zgód rodziców
(patrz „Otwarte, do sprawdzenia u klienta").
</details>

---

## ✅ Decyzja: galerie zostają w Google Photos (wariant A)

**Ustalone 2026-09-11.** Konto Google należy do **przedszkola**, nie do prywatnej osoby — to eliminuje główne ryzyko wariantu A (utrata archiwum wraz z odejściem pracownika).

### Co to znaczy dla migracji
- Linki `photos.app.goo.gl` i `photos.google.com` przenosimy do treści **bez zmian**
- Miniatury-zajawki z `images/` trafiają do Media Library jako obrazki wyróżniające
- Wzorzec `<a href="..."><img></a>` zamieniamy na blok obrazka z linkiem + czytelny podpis („Zobacz zdjęcia")
- W motywie: wyraźny przycisk/oznaczenie, że link prowadzi do galerii zewnętrznej
- Nie budujemy własnego systemu galerii w Etapie 7 — odpada spory kawałek pracy

### Do zrobienia mimo wszystko
- [ ] Dopisać linkom tekst alternatywny — dziś są puste (`<a>` opakowuje sam obrazek), co jest problemem dostępności
- [ ] Rozważyć okresowy eksport albumów z Google (Takeout) jako kopię bezpieczeństwa — konto przedszkola, więc wykonalne

<details>
<summary>Rozważane warianty (archiwum decyzji)</summary>


395 albumów, 387 artykułów. Trzy drogi:

### A. Zostawić linki do Google Photos
- ✅ zero pracy migracyjnej, zero miejsca na serwerze, działa od razu
- ✅ przedszkole pracuje tak, jak przywykło
- ❌ strona zależna od zewnętrznej usługi i od **właściciela tamtego konta Google**
- ❌ jeśli konto należy do osoby, która odejdzie z pracy — całe archiwum zdjęć znika
- ❌ użytkownik wychodzi z witryny; brak kontroli nad wyglądem i szybkością
- ❌ zero wartości SEO ze zdjęć

### B. Przenieść albumy do WordPressa
- ✅ pełna kontrola, natywne galerie, brak zależności zewnętrznych
- ✅ zdjęcia zostają własnością przedszkola
- ❌ 395 albumów × kilkanaście–kilkadziesiąt zdjęć = **tysiące plików**
- ❌ przy oryginalnych rozmiarach ~35 GB — **nie zmieści się** (wolne 30 GB); po przeskalowaniu do wersji webowych ~3–5 GB, zmieści się
- ❌ pobranie albumów udostępnionych linkiem jest półręczne — Google Takeout obejmuje tylko albumy z własnego konta
- ❌ duży nakład pracy, poza pierwotnym zakresem projektu

### C. Hybryda (kompromis)
- Archiwum 2023–2025 zostaje na linkach do Google Photos
- Nowe galerie od roku 2026/27 robione natywnie w WordPressie
- ✅ zero pracy teraz, kontrola od nowego roku szkolnego
- ❌ dwa mechanizmy równolegle przez jakiś czas

</details>

### Otwarte, do sprawdzenia u klienta
- [ ] Zakres zgód rodziców na publikację zdjęć dzieci. Albumy „każdy z linkiem" są faktycznie publiczne. Zostawiając linki nie zmieniamy nic w stanie obecnym, ale warto, by przedszkole miało to udokumentowane.

---

## Decyzje do podjęcia

- [x] **Zakres czasowy:** lata szkolne 2023/24–2025/26, czyli od **2023-09-01** → **440 artykułów**
- [x] **Autorstwo:** konta grupowe, wyprowadzane z kategorii artykułu (patrz niżej)
- [x] **Strony statyczne** (Kadra, Dokumenty, Jadłospis, Opłaty, Deklaracja dostępności) — migrowane niezależnie od daty, wykonane 2026-09-11
- [x] **Obrazki wyróżniające** — bez nich. Zdjęcia dokładamy tylko tam, gdzie treść wskazuje konkretny plik, a w zakresie migracji nie ma ani jednego takiego odnośnika
- [ ] **Stare artykuły (2015–2023)** — skasować, czy zostawić dostępne w archiwum?
      1313 artykułów zostało w bazie roboczej. Zrzut `*.sql.gz` jest jedynym archiwum —
      przy usuwaniu bazy `joomla` z DDEV trzeba o tym pamiętać
- [ ] **Przekierowania 301** ze starych adresów — potrzebne, jeśli stara strona jest zaindeksowana w Google.

---

## Konta autorów

Autorstwo przypisywane **na podstawie kategorii**, nie oryginalnego autora.

| Konto | Nazwa wyświetlana | Rola WP | Wpisów |
|---|---|---|---|
| `grupa-kotki` | Grupa Kotki | Author | 68 |
| `grupa-zabki` | Grupa Żabki | Author | 63 |
| `grupa-jezyki` | Grupa Jeżyki | Author | 51 |
| `grupa-zajaczki` | Grupa Zajączki | Author | 48 |
| `grupa-misie` | Grupa Misie | Author | 42 |
| `grupa-wiewiorki` | Grupa Wiewiórki | Author | 40 |
| `przedszkole` | Przedszkole | Editor | 128 (wpisy bez grupy) |

Razem 440. ✅

### ⚠️ Kompromis do świadomej akceptacji

Konto na grupę oznacza **współdzielone hasło** między kilkoma nauczycielkami:
- nie wiadomo, kto konkretnie dodał wpis (brak rozliczalności)
- odejście jednej osoby wymaga zmiany hasła dla całej grupy
- hasło krąży nieformalnie, co zwiększa ryzyko wycieku

Alternatywa: konta imienne, a grupa pozostaje kategorią (którą i tak mamy).
**Decyzja klienta: konta grupowe.** Odnotowane świadomie.

---

## Uwaga o danych osobowych

Zrzut zawiera tabelę `l6hwz_users` z hasłami (hashe) i adresami e-mail.
- Nie trafia do repozytorium (`*.sql.gz` w `.gitignore`)
- Migrujemy **wyłącznie imiona autorek**, nie hasła ani dane logowania
- Nowe konta w WordPressie dostają świeże, mocne hasła

---

## Migracja aktualności (wykonana 2026-09-14)

Narzędzie: `tools/migracja_wpisow.py` (`--zastosuj` wgrywa do WordPressa).
Idempotentne — wpis rozpoznaje po metadanej `_joomla_id`.

### Wynik

| | |
|---|---|
| Artykułów w zakresie | 440 |
| **Zaimportowanych wpisów** | **428** |
| Pominiętych: treść już jest na stronach | 5 |
| Pominiętych: pusta treść (sama zajawka załącznika) | 7 |
| Przycisków do albumów Google Photos | 399 w 383 wpisach |
| Obrazków wyciętych z treści (pliki z FTP) | 407 |
| Utrata treści w konwersji | 0 % (sprawdzone na wszystkich 428) |

Rozkład po kategoriach: Ogłoszenia 118 · Kotki 66 · Żabki 63 · Jeżyki 51 ·
Zajączki 48 · Misie 42 · Wiewiórki 40.

### 🔒 Skan treści: czysto

Stara strona była zaatakowana, więc **każdy artykuł przechodzi przez skaner przed
konwersją** — nie tylko te 440 w zakresie, kontrolnie przejrzane zostały wszystkie 1753.

Sygnatury blokujące (wpis pomijany): `<script>`, `<iframe>`, `<object>`, `<embed>`,
`<form>`, `<meta>`, `<style>`, `<svg>`, atrybuty zdarzeń (`onclick`, `onerror`),
`javascript:`/`vbscript:`/`data:text/html`, `eval()`, `atob()`, `unescape()`,
`document.write`, `window.location`, kod PHP, ciągi escapowane szesnastkowo.

Sygnatury ostrzegawcze (wpis przechodzi, trafia do raportu): ukryta treść
(`display:none`, `text-indent:-`, `opacity:0`), słownictwo spamowe, odnośniki do
domen typowych dla spamu, duże bloki base64.

**Trafień: zero.** Infekcja siedziała w plikach PHP na serwerze, nie w treści
artykułów. Kontrola uzupełniająca: wszystkie 380+ domen w odnośnikach to YouTube,
Google Photos i polskie portale edukacyjne — żadnego wstrzykniętego spamu SEO.
Pięć trafień na „base64" w starszych artykułach to legalne `data:image` wklejone
w treść przez autorki.

Skaner zostaje w skrypcie na stałe — gdyby kiedyś doszło do ponownego importu
z zainfekowanego źródła, nie trzeba pamiętać o ręcznym sprawdzeniu.

### Mapowanie, które zadziałało

- **Kategoria i autor ze ścieżki kategorii Joomli.** Segment `komunikaty/kotki/…`
  → kategoria Kotki + konto `grupa-kotki`. Literówka `kotkiorojekty` i sufiks
  `projekty` obsłużone. Wszystko inne → Ogłoszenia + konto `przedszkole`.
- **Data z `created` przez `get_date_from_gmt()`.** Joomla trzyma UTC, WordPress
  chce obu wersji. Bez przeliczenia wpisy wieczorne skakałyby o dwie godziny.
- **Albumy → przyciski.** Wzorzec `<a href="photos…"><img galeria.png></a>` (399
  kotwic, każda bez tekstu — czytnik ekranu nie miał czego przeczytać) zamieniony
  na blok przycisku w wariancie `is-style-galeria` z tekstem „Zobacz zdjęcia".
  Motyw dokłada adnotację „(album w serwisie Google Zdjęcia)".
- **Wiele albumów w jednym wpisie.** 3 artykuły opisywały po 5–6 albumów, każdy
  poprzedzony akapitem „Grupa Żabki". Etykieta wchodzi do tekstu przycisku —
  inaczej strona miałaby sześć identycznych odnośników, co jest błędem dostępności.
- **Wykluczenie duplikatów.** 5 artykułów (jadłospis, opisy specjalistek) jest już
  treścią stron z migracji statycznej. Stała `ARTYKULY_STRON` w `migracja_tresci.py`
  jest wspólnym źródłem prawdy dla obu skryptów.

### Do dokończenia ręcznie

**7 wpisów pominiętych** — na starej stronie były samą zajawką dla pliku PDF/ODT,
więc bez plików nie ma z czego zrobić wpisu. Dokumenty trzeba odtworzyć z innego
źródła (gmina, archiwum przedszkola) i wstawić jako nowe wpisy albo na stronę
„Dokumenty":

| Artykuł | Data | Brakujący plik |
|---|---|---|
| Zapisy na dyżur wakacyjny | 2026-05-15 | HARMONOGRAM POSTĘPOWANIA WAKACJE.odt, WNIOSEK-O-PRZYJĘCIE-DZIECKA-DO-PRZEDSZKOLA-WAKACJE.odt |
| OŚWIADCZENIE WOLI | 2025-04-06 | OSWIADCZENIE WOLI |
| DEKLARACJA KONTYNUACJI | 2025-02-16 | DEKLARACJA KONTYNUACJI EDUKACJI-2.pdf |
| Zarządzenie Burmistrza Gminy Słomniki | 2025-02-13 | Zarządzenie rekrutacja Przedszkole i OP 2025 2026.pdf |
| Stawianie granic | 2025-02-13 | STAWIANIE GRANIC.pdf |
| Wniosek na dyżur wakacyjny | 2024-05-11 | Wniosek do pobrania |
| O projekcie: „Mały miś…" | 2024-01-29 | (bez załączników — artykuł był pusty) |

**11 wpisów zaimportowanych, ale bez dokumentu** — treść jest, brakuje pliku:
Oświadczenie woli 2026/2027, Wniosek o przyjęcie dziecka, Informacje nt deklaracji
kontynuacji (2024/25 i 2026/27), Rekrutacja (2024 i 2026), Policja dla Seniorów,
Konwencja o prawach dziecka, Standardy Ochrony Małoletnich, Zebranie z Rodzicami.
Pełną listę z nazwami plików wypisuje skrypt na końcu przebiegu.

**Treści z bieżącego roku do odświeżenia.** Wpisy z lat 2023–2026 są archiwum i mogą
zostać jak są, ale komunikaty rekrutacyjne odnoszą się do zamkniętych już terminów.
Do przeglądu przed wdrożeniem razem z treściami statycznymi (patrz niżej).

---

## Migracja treści statycznych (wykonana 2026-09-11)

Odrębna od migracji aktualności. Dotyczy podstron, które na starej stronie stały
pod konkretnymi pozycjami menu — nie artykułów newsowych.

Narzędzie: `tools/migracja_tresci.py` (`--zastosuj` wgrywa do WordPressa).

### Skąd wzięliśmy mapę

Z tabeli `l6hwz_menu`, nie z dopasowywania tytułów. Menu Joomli jednoznacznie
wiąże pozycję z ID artykułu albo kategorii — dopasowanie po tytule trafiałoby
w aktualności o podobnych nazwach.

### Co przeniesiono — 16 stron

| Strona WordPressa | Źródło w Joomli |
|---|---|
| Kadra | art. 18 (dyrektor) + 9 art. z kategorii `kadra` + art. 7, 1805, 1813, 1815 (specjaliści) + art. 8 (administracja) |
| Grupy → Misie · Zajączki · Żabki · Kotki · Wiewiórki · Jeżyki | opisy kategorii 12–17 + moduły „Zajęcia stałe" + moduły „zajęcia z logopedą" |
| Jadłospis | art. 22 |
| Ramowy rozkład dnia | art. 24 (Misie, Zajączki), 197 (Żabki), 23 (starszaki) — scalone w jedną stronę |
| Opłaty | art. 25 |
| Kącik logopedy | opis kategorii 19 (godziny pracy) |
| Dokumenty | art. 21 (sam wstęp) |
| Dofinansowanie | art. 178 |
| Kontakt | art. 9 |
| Deklaracja dostępności | art. 823 |
| Polityka prywatności | art. 1274 — **tylko kontakt do IOD**, nie polityka |

### Ustalenia, które wypadły przy okazji

**Dane kontaktowe placówki** (art. 9 + moduł 95):
ul. św. Jadwigi Królowej 4, 32-090 Słomniki · tel. 510 217 005 ·
sekretariat@przedszkoleslomniki.pl

**Godziny otwarcia: 6:30–17:00.** Z ramowego rozkładu dnia dla starszaków —
schodzenie się dzieci od 6:30, zajęcia dodatkowe do 17:00. Młodsze grupy mają
rozkład do 15:00, po tej godzinie przechodzą pod opiekę nauczyciela grupy starszej.
To potwierdza tymczasową wartość wpisaną w sekcji „Dlaczego my" (Etap 3).

**Obsada grup** (opisy kategorii 12–17) — wychowawcy, pomoc i przedział wiekowy
dla każdej z sześciu grup.

### Czego w zrzucie nie ma

| Strona | Stan | Najbliższy materiał |
|---|---|---|
| **O przedszkolu** | brak podstrony → **strona usunięta 2026-09-14** | art. 1 „Witamy w nowym przedszkolu" i art. 93 „Uroczyste otwarcie" — to aktualności z 2015–2016, nie opis placówki |
| **Oferta** | brak podstrony → **strona usunięta 2026-09-14** | moduły „Zajęcia stałe" (angielski, religia) + ogłoszenie o kółkach zainteresowań — to grafik, nie opis oferty |
| **Grupy** (strona nadrzędna) | brak | stare menu prowadziło prosto do listy grup |
| **Dla rodziców** (strona nadrzędna) | brak | kategoria bez opisu; pod spodem 19 artykułów poradnikowych |
| **Galeria** | brak → **strona usunięta 2026-09-14** | art. 191 „Galeria prac plastycznych" jest niepublikowany i zawiera pusty shortcode Joomli |
| **Polityka prywatności** | niepełna | jest wyłącznie kontakt do Inspektora Ochrony Danych. Właściwej polityki i klauzuli RODO trzeba napisać |
| **Statut, Koncepcja pracy** | tylko nazwy plików → **statut odzyskany poza migracją** | 6 PDF-ów podpiętych do art. 21 leżało na FTP starej strony i przepadło. Przedszkole dostarczyło pliki wprost: statut, ubezpieczenie i klauzulę informacyjną (2026-09-17) oraz standardy ochrony małoletnich (2026-09-21). Koncepcji pracy nadal nie ma |
| **Zdjęcia w treści** | wycięte | 5 plików: 1 w Kontakcie, 4 logotypy dofinansowania. Też na FTP |

### Uwaga o aktualności danych

Migrowane strony niosą dane z roku szkolnego 2025/2026 — jadłospis na konkretny
tydzień czerwca 2026 i harmonogramy zajęć logopedycznych z datami dziennymi.
Przed wdrożeniem trzeba je odświeżyć albo usunąć.

---

## Migracja logopedy (wykonana 2026-09-16)

Uzupełnienie migracji aktualności (wyżej): kategoria 19 Joomli („Logopeda")
wykracza poza zakres lat szkolnych 2023/24–2025/26 objęty `migracja_wpisow.py`
— sięga 2015 roku. Narzędzie: `tools/migracja_logopedy.py` (`--zastosuj`
wgrywa do WordPressa), ta sama baza robocza `joomla`.

**Korekta 2026-09-16.** Kategoria 19 miała w zrzucie **27 opublikowanych
artykułów**, nie 4, jak sugerowała tabela „Rozkład 440 migrowanych artykułów"
— patrz PLAN.md, Etap 4. Ten wiersz liczył tylko artykuły z zakresu
ówczesnej migracji (2023/24–2025/26). Do WordPressa wtedy weszły cztery,
pozostałe 23 dobrał `migracja_logopedy.py`.

Z tych 23, dwa artykuły pominięte świadomie:
- **Artykuł 32 „Godziny pracy logopedy"** (2015) — pięć akapitów z godzinami
  `Poniedziałek 10:40 - 14:40`, sprzecznymi z aktualną treścią strony „Kącik
  logopedy" (`poniedziałek 12:00 - 16:00`). Import dałby dwie sprzeczne
  wersje tej samej informacji na stronie.
- **Artykuł 1224 „Co robimy na zajęciach logopedycznych?"** — treść to
  wyłącznie `<img src="images/259377452_….jpg">`, plik przepadł ze starym
  serwerem (patrz „Pliki ze starego serwera: przepadły"). Po wycięciu
  znacznika zostaje pustka; istniejąca maszyneria konwersji (`zbuduj()`
  w `migracja_tresci.py`) odrzuca taki wpis sama.

Kategoria 64 („Archiwum Logopedy", 16 odcinków serii #zostańwdomu z okresu
pandemii) pominięta w całości — na starej stronie była niepublikowana.

**Wynik: 25 wpisów w kategorii `logopeda`** — 21 nowych z importu plus
4 istniejące, które migracja z Etapu 2b przypisała do „Ogłoszeń" (Joomla ID
1897, 1899, 1613, 1614; WP 333, 331, 592, 591). Importer rozpoznaje wpis po
meta `_joomla_id` i przy dopasowaniu **zastępuje** jego kategorie — te cztery
wpisy przeszły na `logopeda` w tym samym przebiegu co import nowych,
zamiast osobnego kroku „przepięcia po slugu" pierwotnie przewidzianego
w specyfikacji. Skutek uboczny: te cztery wpisy dostały też tytuł, slug
i treść odtworzone ze zrzutu — świadomie zaakceptowane, bo weszły automatem
i nikt ich ręcznie nie poprawiał.
