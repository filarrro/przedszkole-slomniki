# Migracja treści: Joomla → WordPress

Dokument roboczy Etapu 2b. Analiza wykonana 2026-09-11 na zrzucie `icrdslom_dbj34_1789116422.sql.gz`.

---

## Co zastaliśmy

**Stara strona:** Przedszkole w Słomnikach, Joomla **3.10.5**, prefiks tabel `l6hwz_`, 83 tabele, 84 MB po rozpakowaniu.

**Domena:** `przedszkoleslomniki.pl`
**Katalog na serwerze:** `/home/icrdslom/domains/przedszkoleslomniki.pl/public_html/`

Zrzut zaimportowany lokalnie do osobnej bazy `joomla` (obok bazy WordPressa) — służy tylko do odczytu przy migracji.

```bash
ddev mysql joomla        # konsola do bazy Joomli
```

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
| Artykuł z `rok-szkolny-*` | Wpis, kategoria „Aktualności" |
| Artykuł z `<grupa>projekty/…` | Wpis, kategoria „Projekty" + grupa |
| Kadra, Specjaliści, Dokumenty, Jadłospis, Opłaty, Rozkład dnia, Dofinansowanie, Deklaracja dostępności | **Strony** (nie wpisy) |
| `created` | `post_date` |
| Kategoria → grupa | `post_author` (konto grupowe) |
| `alias` | `post_name` (slug) |
| `images.image_fulltext` | Obrazek wyróżniający |
| `introtext` + `fulltext` | `post_content` (bez `<!-- pagebreak -->`) |
| `metadesc` | Meta description |

**Rok szkolny** wyprowadzamy z daty publikacji, nie z kategorii — nie potrzeba archiwalnych kategorii, WordPress ma archiwa po dacie z pudełka.

### Kroki

- [x] Import zrzutu do lokalnej bazy roboczej `joomla`
- [x] Analiza: liczby, struktura, zdjęcia, autorzy
- [x] Eksport 440 artykułów do JSON
- [ ] **Pobranie katalogu `images/` ze starego FTP** ⚠️ przed usunięciem starej strony
- [ ] Ustalenie zakresu z klientem (patrz „Decyzje do podjęcia")
- [ ] Skrypt konwertujący: JSON → import do WordPressa przez `wp-cli`
- [ ] Czyszczenie HTML: usunięcie stylów inline, `<font>`, pustych `<p>`, atrybutów Joomli
- [ ] Konwersja treści na bloki Gutenberga (`wp post create` + parser)
- [ ] Upload zdjęć do Media Library + przypisanie obrazków wyróżniających
- [ ] Upload załączników (118 plików) do Media Library + podlinkowanie w treści
- [ ] Podmiana ścieżek `images/…` w treści na adresy z `wp-content/uploads`
- [ ] Weryfikacja: liczba wpisów, losowa próbka 20 artykułów, martwe linki
- [ ] Mapa przekierowań 301 ze starych adresów (SEO)

### ⚠️ Krytyczne: pobrać pliki przed usunięciem starej strony

Zdjęcia **nie są w bazie** — to pliki na serwerze. Zrzut SQL zawiera tylko ścieżki.
Przed skasowaniem starej strony (Etap 9) trzeba pobrać przez FTP co najmniej:

Katalog główny starej strony:
```
/home/icrdslom/domains/przedszkoleslomniki.pl/public_html/
```

- [ ] `public_html/images/` — zdjęcia artykułów, 782 pliki (obejmuje `images/aktualnosci/2023–2026/`)
- [ ] `public_html/attachments/` — 118 załączników, 77 MB (PDF-y, dokumenty)
- [ ] `public_html/templates/` — logo i grafiki szablonu (opcjonalnie, do odtworzenia identyfikacji wizualnej)

Obecność `administrator/`, `components/`, `configuration.php` potwierdza, że to katalog główny Joomli.

**Bez tych plików migracja jest niemożliwa, a operacja nieodwracalna.**

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
- [ ] **Strony statyczne** (Kadra, Dokumenty, Jadłospis, Opłaty, Deklaracja dostępności) — migrować niezależnie od daty. Domyślnie: tak.
- [ ] **Stare artykuły (2015–2023)** — skasować, czy zostawić dostępne w archiwum?
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
