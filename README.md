# Strona Przedszkola Samorządowego w Słomnikach

Lekki motyw WordPress dla konkretnego przedszkola. Nie SaaS, nie uniwersalny CMS.

**Dokumenty projektu:**
- [PLAN.md](PLAN.md) — plan w 12 etapach, stan prac, rejestr decyzji, pytania otwarte
- [MIGRACJA.md](MIGRACJA.md) — migracja treści ze starej strony (Joomla 3.10.5)
- [CLAUDE.md](CLAUDE.md) — konwencje dla agentów AI i developerów
- [tools/README.md](tools/README.md) — narzędzia pomocnicze

---

## Start w 30 sekund

```bash
ddev start
ddev launch          # otwiera http://przedszkole.ddev.site
```

| | |
|---|---|
| Strona | http://przedszkole.ddev.site |
| Panel | http://przedszkole.ddev.site/wp-admin |
| Login | `dev` / `dev12345` — **tylko lokalnie**, nie trafia na produkcję |
| Poczta (Mailpit) | http://przedszkole.ddev.site:8025 |

Wymagania: Docker + [DDEV](https://ddev.com). Nic poza tym.

---

## Co gdzie leży

```
przedszkole-wp/
├── theme/przedszkole/   ← NASZ KOD, jedyne co wersjonujemy
├── wp/                  WordPress (w .gitignore, nie edytować)
│   └── wp-content/themes/przedszkole → symlink do ../../../theme/przedszkole
├── tools/               narzędzia pomocnicze
├── logo.png             logo przedszkola (źródło palety kolorów)
└── *.sql.gz             zrzuty baz — NIGDY do repo (dane osobowe)
```

**Git wersjonuje wyłącznie motyw.** Rdzeń WordPressa, wtyczki i uploady to cudzy kod
albo dane — nie należą do repozytorium.

**Symlink działa tylko lokalnie.** Przy wdrożeniu motyw wgrywamy jako zwykły katalog.

---

## Stan prac

| Etap | Stan |
|---|---|
| 1. Analiza hostingu | ✅ cyber_Folks wystarcza, brak blokerów |
| 2. Środowisko lokalne | ✅ DDEV + WordPress 7.1 + PHP 8.5 |
| 2b. Migracja z Joomli | ✅ 16 stron + 428 wpisów; pliki z FTP przepadły |
| 3. Motyw | ✅ szkielet, szablony, identyfikacja wizualna |
| 4. Struktura treści | ✅ strony, menu główne + stopka, kategorie |
| 5. Gutenberg | ✅ wzorce, warianty stylów, style bloków |
| 6. Użytkownicy | ✅ role natywne, konta grupowe, panel odchudzony |
| 7. Frontend | ✅ widoki, skróty, podstrony, mapa, obrazy |
| 8. SEO / wydajność / bezpieczeństwo | 🔄 motyw gotowy, ustawienia serwera przy wdrożeniu |
| 9. Wdrożenie | ⬜ |
| 10–12 | ⬜ |

Szczegóły i kryteria odbioru: [PLAN.md](PLAN.md).

---

## Co zostało z migracji

**Pliki ze starego serwera przepadły.** Hosting wyłączył starą witrynę — domena
oddaje 403, ścieżki do plików 404, FTP niedostępny. Nie ma skąd wziąć 407 obrazków
wyróżniających ani 118 załączników (PDF-y, wnioski, zarządzenia).

Treść przeniesiona w całości: **16 stron** i **428 wpisów**, bez utraty tekstu.
Zdjęcia z wydarzeń nie zginęły — siedzą w Google Photos i wpisy linkują do 395 albumów.

Do dokończenia ręcznie:
- 7 wpisów, które na starej stronie były samą zajawką dla pliku PDF
- 11 wpisów z treścią, ale bez dokumentu
- przekierowania 301 ze starych adresów
- zdjęcia kadry — 12 osób ma inicjały, 2 stockowe zastępniki czekają na podmianę

Listy z nazwami plików: [MIGRACJA.md](MIGRACJA.md), sekcja „Do dokończenia ręcznie".
