#!/bin/bash
# Etap 6 - konta i role.
# Idempotentny: istniejacemu kontu tylko poprawia role, nie dotyka hasla.
#
# Nie tworzymy wlasnych rol. Natywne Editor i Author pokrywaja potrzeby
# przedszkola co do joty - uzasadnienie w PLAN.md, Etap 6.
set -euo pipefail

# Odsiewamy wylacznie szum "Deprecated" z biblioteki wp-cli pod PHP 8.5.
# Prawdziwe bledy musza dojsc, inaczej `set -e` przerwie skrypt bez sladu.
wp() {
	ddev exec wp --path=wp "$@" </dev/null \
		2> >(grep -v 'Using null as an array offset' >&2)
}

# Domena kont. Adresy musza byc unikalne i - na produkcji - realne,
# inaczej nie zadziala odzyskiwanie hasla. Patrz PLAN.md, Etap 6.
DOMENA="przedszkoleslomniki.pl"

HASLA=""

# konto <login> <rola> <nazwa wyswietlana> [e-mail]
konto() {
	local login="$1" rola="$2" nazwa="$3" mail="${4:-$1@$DOMENA}" haslo

	if [ -n "$(wp user list --login="$login" --field=ID)" ]; then
		wp user set-role "$login" "$rola" >/dev/null
		HASLA+="$login|$rola|(konto istnialo, haslo bez zmian)"$'\n'
		return
	fi

	# Haslo losowe - zadnego nie wpisujemy do repozytorium.
	# `cut`, nie `head -c`: head zamyka potok po 20 znakach, co zabija
	# poprzednika SIGPIPE-em i wywraca skrypt przez `set -o pipefail`.
	haslo=$(openssl rand -base64 32 | LC_ALL=C tr -dc 'A-Za-z0-9' | cut -c1-20)
	wp user create "$login" "$mail" \
		--role="$rola" \
		--user_pass="$haslo" \
		--display_name="$nazwa" \
		--first_name="$nazwa" \
		--porcelain >/dev/null
	HASLA+="$login|$rola|$haslo"$'\n'
}

echo "== Konto zbiorcze dyrekcji =="
konto przedszkole editor "Przedszkole Samorządowe" "sekretariat@$DOMENA"

echo "== Konta grupowe nauczycieli =="
konto grupa-misie      author "Misie"
konto grupa-wiewiorki  author "Wiewiórki"
konto grupa-zajaczki   author "Zajączki"
konto grupa-zabki      author "Żabki"
konto grupa-jezyki     author "Jeżyki"
konto grupa-kotki      author "Kotki"

echo
echo "== Hasla =="
echo "Zapisz je teraz - nigdzie ich nie przechowujemy."
echo
printf '%s' "$HASLA" | column -t -s '|'
echo
echo "Konto administratora sluzy do konfiguracji, nie do codziennej pracy."
