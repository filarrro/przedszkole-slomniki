#!/usr/bin/env bash
# Tworzy menu „Na skróty” — kafelki na stronie głównej.
#
# Menu, a nie kod: dyrekcja zmienia zestaw kafelków w Wyglądzie → Menu.
# Opis pod tytułem to pole „Opis” pozycji menu (Opcje ekranu → Opis).
#
# Uruchomienie: ./tools/skroty.sh

set -euo pipefail

wp() { ddev exec wp --path=wp "$@" </dev/null; }

if wp menu list --fields=slug --format=csv | grep -qx "na-skroty"; then
	echo "Menu „Na skróty” już istnieje — pomijam."
else
	wp menu create "Na skróty"
fi

dodaj() {
	local strona="$1" tytul="$2" opis="$3"
	local id
	id=$( wp post list --post_type=page --name="$strona" --field=ID --format=csv )

	if [ -z "$id" ]; then
		echo "Brak strony o slugu $strona — pomijam."
		return
	fi

	local pozycja
	pozycja=$( wp menu item add-post na-skroty "$id" --title="$tytul" --porcelain )
	wp post update "$pozycja" --post_content="$opis"
}

# Kafelki tylko wtedy, gdy menu jest puste — skrypt można uruchomić ponownie
# bez mnożenia pozycji.
if [ "$( wp menu item list na-skroty --format=count )" = "0" ]; then
	dodaj grupy        "Nasze grupy"   "Sześć grup, każda z własnym kącikiem i wychowawcą"
	dodaj dla-rodzicow "Dla rodziców"  "Jadłospis, rozkład dnia, opłaty i dokumenty"
	dodaj galeria      "Galeria"       "Zdjęcia z wycieczek, uroczystości i zajęć"
	dodaj kontakt      "Kontakt"       "Adres, telefon i godziny pracy sekretariatu"
fi

wp menu location assign na-skroty skroty

echo
echo "Gotowe. Kafelki na stronie głównej bierze się z menu „Na skróty”."
