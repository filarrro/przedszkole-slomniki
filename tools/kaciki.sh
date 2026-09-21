#!/usr/bin/env bash
# Kąciki specjalistów: kategorie i strony o zgodnych slugach.
#
# Motyw wiąże stronę z kategorią po slugu — tak samo jak strony grup
# (`page.php` woła template part z `post_name`). Dlatego strona i kategoria
# muszą mieć ten sam slug: `logopeda`, `pedagog`. Lista kącików po stronie
# motywu siedzi w `przedszkole_kaciki()` (theme/przedszkole/inc/helpers.php);
# dopisanie kolejnego specjalisty to linia tam i linia w tablicy `KACIKI`
# niżej.
#
# `logopeda`, nie `kacik-logopedy`, bo stary adres Joomli to
# `dla-rodzicow/logopeda` — przekierowania 301 z Etapu 9 robią się trywialne.
# `pedagog` idzie tym samym wzorem.
#
# Strony tworzy `struktura.sh`; ten skrypt zakłada kategorie, sprawdza, że
# strona o danym slugu istnieje, i przenosi historyczny slug `kacik-logopedy`
# na `logopeda` (instalacje sprzed 2026-09-16).
#
# Idempotentny: istniejącej kategorii nie zakłada drugi raz, sluga nie rusza,
# jeśli już jest poprawny.

set -euo pipefail

cd "$(dirname "$0")/.."

wp() { ddev exec wp --path=wp "$@" </dev/null; }

# slug | nazwa kategorii | opis | slug historyczny (pusty = brak)
KACIKI=(
	"logopeda|Kącik logopedy|Artykuły i porady logopedy przedszkolnego.|kacik-logopedy"
	"pedagog|Kącik pedagoga|Artykuły i porady pedagoga przedszkolnego.|"
)

# Zwraca ID jedynej strony o danym slugu (puste, jeśli żadnej). Przerywa
# skrypt, jeśli slug pasuje do więcej niż jednej strony — zmiana sluga jest
# nieodwracalna, więc zgadywanie „która to ta właściwa” byłoby gorsze niż
# jawny błąd. `wc -l`, nie `grep -c`: ten drugi zwraca kod 1 przy zerze
# dopasowań, co pod `set -e` wywaliłoby skrypt bez czytelnego komunikatu.
#
# Status wyliczony jawnie, bo „Kącik pedagoga” czeka jako szkic na
# potwierdzenie treści przez przedszkole (PLAN.md, Etap 9.4), a domyślne
# `publish` w `wp post list` udawałoby, że strony nie ma. **Nie `any`** —
# w połączeniu z `--name=` zwraca pustkę zamiast szkicu (sprawdzone
# 2026-09-16 na WP 7.1; `--post_status=draft` przy tym samym slugu znajduje).
id_strony() {
	local slug="$1" wyniki liczba
	wyniki=$(wp post list --post_type=page --name="$slug" --field=ID --format=csv --post_status=publish,draft,pending,private)
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

for POZYCJA in "${KACIKI[@]}"; do
	IFS='|' read -r SLUG NAZWA OPIS STARY <<<"$POZYCJA"

	if [ -n "$(wp term list category --slug="$SLUG" --field=term_id --format=csv)" ]; then
		echo "Kategoria „$NAZWA” już jest — pomijam."
	else
		wp term create category "$NAZWA" --slug="$SLUG" --description="$OPIS" >/dev/null
		echo "Utworzona kategoria „$NAZWA” ($SLUG)."
	fi

	ID=""
	if [ -n "$STARY" ]; then
		ID=$(id_strony "$STARY")
	fi

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
			echo "UWAGA: nie znalazłem strony „$NAZWA”. Uruchom najpierw tools/struktura.sh." >&2
			exit 1
		fi
	fi
done
