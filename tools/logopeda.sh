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
