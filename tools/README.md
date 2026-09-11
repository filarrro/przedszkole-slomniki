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
