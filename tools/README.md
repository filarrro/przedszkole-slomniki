# Narzędzia pomocnicze

## `podglad.py`

Pobiera stronę z lokalnego WordPressa i zapisuje ją jako pojedynczy plik HTML
z wklejonym CSS i JS. Służy do oglądania wyglądu motywu w panelu podglądu,
który blokuje ładowanie plików podrzędnych z `*.ddev.site`.

```bash
python3 tools/podglad.py /                    # strona główna  -> podglad.html
python3 tools/podglad.py /aktualnosci/ a.html # lista wpisów   -> a.html
```

Pliki `podglad.html` i `*.podglad.html` są w `.gitignore`.
Do normalnej pracy używaj po prostu http://przedszkole.ddev.site w swojej przeglądarce.

## `struktura.sh`

Odtwarza szkielet treści z Etapu 4: strony (z zagnieżdżeniem), menu główne,
menu w stopce, kategorie, strona główna i strona wpisów.

```bash
tools/struktura.sh
```

Idempotentny w zakresie stron — strona o istniejącym slugu nie powstaje drugi raz,
aktualizowany jest tylko rodzic i kolejność. **Menu są kasowane i budowane od nowa**,
więc ręczne zmiany w menu przepadną.

Skrypt tworzy wyłącznie puste strony. Treść wchodzi w Etapie 7 i przy migracji.

## `skroty.sh`

Tworzy menu „Na skróty" — kafelki na stronie głównej (Etap 7). Cztery pozycje:
Grupy, Dla rodziców, Galeria, Kontakt, każda z opisem pod tytułem.

```bash
tools/skroty.sh
```

Idempotentny: istniejącego menu nie kasuje i nie dokłada pozycji drugi raz.
Dalsze zmiany — w Wyglądzie → Menu. Opis pod tytułem to pole „Opis" pozycji
menu, widoczne po włączeniu w Opcjach ekranu.

Bez tego menu sekcja „Na skróty" nie pojawia się na stronie głównej.

## `uzytkownicy.sh`

Zakłada konta z Etapu 6: zbiorcze `przedszkole` (rola Editor — dyrekcja) i sześć
kont grupowych `grupa-misie` … `grupa-kotki` (rola Author — nauczycielki).

```bash
tools/uzytkownicy.sh
```

Idempotentny: istniejącemu kontu poprawia tylko rolę, hasła nie rusza.

Hasła są losowe i **wypisywane raz, na ekranie** — nigdzie ich nie zapisujemy.
Zgub je, a zostaje odzyskiwanie hasła z panelu, które wymaga działającej skrzynki.
Adresy są zmyślone z nazwy konta (`grupa-misie@przedszkoleslomniki.pl`); przed
wdrożeniem muszą to być realne skrzynki — patrz PLAN.md, Etap 6.

Własnych ról nie tworzymy. Natywne Editor i Author pokrywają potrzeby przedszkola,
uzasadnienie i audyt uprawnień są w PLAN.md.

## `kadra_zdjecie.py`

Wstawia zdjęcia osób w kafelki na stronie „Kadra" — podmienia zawartość lewej
kolumny kafelka (inicjały albo poprzednie zdjęcie) na wskazany plik.

```bash
python3 tools/kadra_zdjecie.py              # podgląd, nic nie zapisuje
python3 tools/kadra_zdjecie.py --zastosuj   # wgrywa do WordPressa
```

Nowa osoba ze zdjęciem: dopisz linię do słownika `ZDJECIA` (nazwisko dokładnie
jak w nagłówku `h3`, bez „mgr") i uruchom. Plik nieobecny w bibliotece mediów
jest importowany przy `--zastosuj`.

Idempotentny — powtórne uruchomienie z tym samym słownikiem nic nie zmienia.

To następca `kadra_kafelki.py` w zakresie zdjęć: tamten skrypt jest jednorazowy
i na przepisanej stronie już nie zadziała, a zdjęcia przychodzą pojedynczo.

**Zdjęcia muszą być kwadratowe** — kafelek kadruje je do koła. Przygotowanie:

```bash
sips -s format jpeg -c 905 905 zrodlo.png --out media/kadra/awatar-6.jpg
sips -z 800 800 media/kadra/awatar-6.jpg
```

Dwa przebiegi, nie jeden: `sips` łączy `-c` z `-Z` w nieprzewidywalnej kolejności
i wychodzi obrazek mniejszy, niż się prosiło.

Wyrównanie kolumn (`top`/`center`) zależy od długości biogramu, nie od zdjęcia —
skrypt go nie rusza.

## `migracja_tresci.py`

Przenosi treści **statyczne** ze zrzutu Joomli do stron WordPressa — kadrę,
opisy grup, rozkład dnia, opłaty, kontakt i resztę podstron spod menu starej
strony. Aktualności to osobna migracja.

```bash
python3 tools/migracja_tresci.py             # podglad: buduje JSON, nic nie zapisuje
python3 tools/migracja_tresci.py --zastosuj  # wgrywa do WordPressa
```

Wymaga bazy roboczej `joomla` w kontenerze — patrz [MIGRACJA.md](../MIGRACJA.md),
sekcja „Jak odtworzyć analizę od zera".

**Nadpisuje treść stron w całości.** Ręczne zmiany w migrowanych stronach przepadną.

Obrazki są wycinane — pliki leżą na FTP starej strony. Skrypt wypisuje na końcu
listę tego, czego w zrzucie nie ma.

## `kadra_kafelki.py`

Przepisuje stronę „Kadra" z prozy na kafelki osób — jeden `is-style-kafelek-osoby`
na dyrektora, nauczycielkę i specjalistkę. Biogramy bierze z obecnej treści strony,
nie z pamięci; dokłada inicjały i linię wykształcenia, poprawia literówki po Joomli.

```bash
python3 tools/kadra_kafelki.py              # podgląd na stdout, nic nie zapisuje
python3 tools/kadra_kafelki.py --zastosuj   # wgrywa do WordPressa
```

Sekcja „Pracownicy administracji i obsługi" zostaje listą akapitów — 11 osób
opisanych jedną linijką nie ma czym wypełnić kafelka.

**Jednorazowy.** Na już przepisanej stronie odmawia działania: parser oczekuje
układu `h3` + akapity, a w kafelkach nagłówki siedzą w kolumnach.
Linia wykształcenia jest w skrypcie wpisana z ręki — nowa osoba w kadrze wymaga
dopisania jej do słownika `WYKSZTALCENIE` albo wstawienia wzorca z edytora.

Zdjęcia bierze ze słownika `ZDJECIA` (nazwisko → plik w `media/kadra/`). Kto go
tam nie ma, dostaje inicjały. Plik nieobecny w bibliotece mediów jest importowany
przy `--zastosuj`; podgląd niczego nie wgrywa.
