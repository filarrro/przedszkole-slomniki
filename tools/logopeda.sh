#!/usr/bin/env bash
# Kategoria „Kącik logopedy" i dopasowanie sluga strony.
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

if wp term list category --slug="$SLUG" --field=term_id --format=csv | grep -q '[0-9]'; then
	echo "Kategoria „$NAZWA” już jest — pomijam."
else
	wp term create category "$NAZWA" --slug="$SLUG" --description="$OPIS"
	echo "Utworzona kategoria „$NAZWA” ($SLUG)."
fi

ID=$(wp post list --post_type=page --name=kacik-logopedy --field=ID --format=csv | head -1)

if [ -n "$ID" ]; then
	wp post update "$ID" --post_name="$SLUG"
	echo "Strona $ID: slug zmieniony na $SLUG."
else
	ID=$(wp post list --post_type=page --name="$SLUG" --field=ID --format=csv | head -1)
	if [ -n "$ID" ]; then
		echo "Strona $ID ma już slug $SLUG — pomijam."
	else
		echo "UWAGA: nie znalazłem strony „Kącik logopedy”. Sprawdź ręcznie." >&2
		exit 1
	fi
fi
