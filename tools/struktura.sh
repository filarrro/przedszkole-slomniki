#!/bin/bash
# Etap 4 - struktura tresci: strony, menu, kategorie.
# Idempotentny: strona o danym slugu nie jest tworzona drugi raz.
set -euo pipefail

# Odsiewamy wylacznie szum "Deprecated" z biblioteki wp-cli pod PHP 8.5.
# Prawdziwe bledy musza dojsc, inaczej `set -e` przerwie skrypt bez sladu.
wp() {
	ddev exec wp --path=wp "$@" </dev/null \
		2> >(grep -v 'Using null as an array offset' >&2)
}

# Zwraca ID strony o danym slugu; tworzy ja, jesli nie istnieje.
strona() {
	local slug="$1" tytul="$2" rodzic="${3:-0}" kolejnosc="${4:-0}" id
	id=$(wp post list --post_type=page --name="$slug" --field=ID --posts_per_page=1)
	if [ -z "$id" ]; then
		id=$(wp post create --post_type=page --post_status=publish \
			--post_title="$tytul" --post_name="$slug" \
			--post_parent="$rodzic" --menu_order="$kolejnosc" --porcelain)
	else
		wp post update "$id" --post_parent="$rodzic" --menu_order="$kolejnosc" >/dev/null
	fi
	echo "$id"
}

echo "== Strony =="

# Kolejnosc od najczesciej odwiedzanego: aktualnosci sa sercem serwisu,
# dofinansowanie i kontakt zamykaja liste. Numery to `menu_order` stron -
# widoczna kolejnosc menu bierze sie z pozycji nizej, ale trzymamy oba
# zgodnie, zeby lista stron w panelu nie klamala.
#
# Usuniete 2026-09-14 (patrz PLAN.md, Etap 4): „O przedszkolu", „Oferta"
# i „Galeria". Kadra stoi wprost w menu, bez strony nadrzednej.
AKTUALNOSCI=$(strona aktualnosci "Aktualności"      0 1)

GRUPY=$(strona grupy        "Grupy"                 0 2)
strona misie                "Misie"                 "$GRUPY" 1 >/dev/null
strona wiewiorki            "Wiewiórki"             "$GRUPY" 2 >/dev/null
strona zajaczki             "Zajączki"              "$GRUPY" 3 >/dev/null
strona zabki                "Żabki"                 "$GRUPY" 4 >/dev/null
strona jezyki               "Jeżyki"                "$GRUPY" 5 >/dev/null
strona kotki                "Kotki"                 "$GRUPY" 6 >/dev/null

RODZICE=$(strona dla-rodzicow "Dla rodziców"        0 3)
strona dokumenty            "Dokumenty"             "$RODZICE" 1 >/dev/null
strona jadlospis            "Jadłospis"             "$RODZICE" 2 >/dev/null
strona ramowy-rozklad-dnia  "Ramowy rozkład dnia"   "$RODZICE" 3 >/dev/null
strona oplaty               "Opłaty"                "$RODZICE" 4 >/dev/null
strona kacik-logopedy       "Kącik logopedy"        "$RODZICE" 5 >/dev/null

KADRA=$(strona kadra        "Kadra"                 0 4)
DOFINANSOWANIE=$(strona dofinansowanie "Dofinansowanie" 0 5)
KONTAKT=$(strona kontakt    "Kontakt"               0 6)

DOSTEPNOSC=$(strona deklaracja-dostepnosci "Deklaracja dostępności" 0 7)
PRYWATNOSC=$(strona polityka-prywatnosci   "Polityka prywatności"   0 8)

# Polityka prywatnosci - natywny mechanizm WP (Ustawienia -> Prywatnosc)
wp option update wp_page_for_privacy_policy "$PRYWATNOSC" >/dev/null

echo "== Kategorie =="

if ! wp term list category --slug=ogloszenia --field=term_id | grep -q .; then
	wp term create category "Ogłoszenia" --slug=ogloszenia --description="Aktualności ogólne, niezwiązane z konkretną grupą" >/dev/null
fi

# Ogloszenia jako kategoria domyslna, "Bez kategorii" do kasacji
OGLOSZENIA=$(wp term list category --slug=ogloszenia --field=term_id)
wp option update default_category "$OGLOSZENIA" >/dev/null
BEZ=$(wp term list category --slug=bez-kategorii --field=term_id)
if [ -n "$BEZ" ]; then
	wp term delete category "$BEZ" >/dev/null
fi

echo "== Menu glowne =="

wp menu delete menu-glowne >/dev/null || true
wp menu create "Menu główne" >/dev/null
wp menu location assign menu-glowne primary >/dev/null

# Pozycje najwyzszego poziomu - kolejnosc wymuszona jawnie
poz() { wp menu item add-post menu-glowne "$1" --porcelain; }
pozp() { wp menu item add-post menu-glowne "$1" --parent-id="$2" --porcelain; }

poz "$AKTUALNOSCI" >/dev/null

M_G=$(poz "$GRUPY")
for s in misie wiewiorki zajaczki zabki jezyki kotki; do
	pozp "$(wp post list --post_type=page --name=$s --field=ID)" "$M_G" >/dev/null
done

M_R=$(poz "$RODZICE")
for s in dokumenty jadlospis ramowy-rozklad-dnia oplaty kacik-logopedy; do
	pozp "$(wp post list --post_type=page --name=$s --field=ID)" "$M_R" >/dev/null
done

poz "$KADRA" >/dev/null
poz "$DOFINANSOWANIE" >/dev/null
poz "$KONTAKT" >/dev/null

echo "== Menu w stopce =="

wp menu delete menu-w-stopce >/dev/null || true
wp menu create "Menu w stopce" >/dev/null
wp menu location assign menu-w-stopce footer >/dev/null
wp menu item add-post menu-w-stopce "$DOSTEPNOSC" >/dev/null
wp menu item add-post menu-w-stopce "$PRYWATNOSC" >/dev/null
wp menu item add-post menu-w-stopce "$KONTAKT" >/dev/null

echo "== Widgety stopki =="

# Dane teleadresowe z art. 9 starej strony, godziny z ramowego rozkladu dnia
# (patrz MIGRACJA.md). Widget bloku, nie kod w szablonie - dyrekcja poprawi to
# sama z panelu. Ikony dokleja CSS do klas .kontakt__poz--*, wiec w tresci
# widgetu nie ma SVG, ktore uzytkownik moglby przypadkiem skasowac.
KONTAKT_HTML='<!-- wp:html --><address><ul class="kontakt"><li class="kontakt__poz kontakt__poz--adres">ul. św. Jadwigi Królowej 4<br>32-090 Słomniki</li><li class="kontakt__poz kontakt__poz--telefon"><span class="screen-reader-text">Telefon: </span><a href="tel:+48510217005">510 217 005</a></li><li class="kontakt__poz kontakt__poz--email"><span class="screen-reader-text">E-mail: </span><a href="mailto:sekretariat@przedszkoleslomniki.pl">sekretariat@przedszkoleslomniki.pl</a></li></ul></address><!-- /wp:html -->'

GODZINY_HTML='<!-- wp:html --><ul class="kontakt"><li class="kontakt__poz kontakt__poz--godziny">poniedziałek – piątek<br>6:30 – 17:00</li></ul><!-- /wp:html -->'

# Czyscimy obszary przed dodaniem - inaczej kolejne uruchomienie dokleja duplikat.
widget_bloku() {
	local obszar="$1" tresc="$2" w
	for w in $(wp widget list "$obszar" --format=ids); do
		wp widget delete "$w" >/dev/null
	done
	wp widget add block "$obszar" --content="$tresc" >/dev/null
}

widget_bloku footer-kontakt "$KONTAKT_HTML"
widget_bloku footer-godziny "$GODZINY_HTML"

echo "== Strona glowna i strona wpisow =="
wp option update show_on_front page >/dev/null
wp option update page_on_front "$(wp post list --post_type=page --name=strona-glowna --field=ID)" >/dev/null
wp option update page_for_posts "$AKTUALNOSCI" >/dev/null

echo "GOTOWE"
