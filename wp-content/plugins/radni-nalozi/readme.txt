=== Radni Nalozi ===
Contributors: yourname
Tags: work orders, orders, print shop, clothing, custom orders
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin za kreiranje i upravljanje radnim nalozima za štampu odeće.

== Description ==

Radni Nalozi je WordPress plugin koji omogućava vašim klijentima da kreiraju i upravljaju radnim nalozima za štampu odeće direktno sa vašeg sajta.

= Glavne funkcionalnosti =

* Korisnici se prijavljuju na frontendu i vide samo svoje naloge
* Kreiranje novih radnih naloga sa podacima o kupcu i proizvodima
* Dinamičko dodavanje više proizvoda u jedan nalog
* Upload slika za svaki proizvod
* Automatsko generisanje jedinstvenog broja naloga (format: username-rednibroj-godina)
* Editovanje naloga dok je status "U obradi"
* Pregled i storniranje naloga
* Responzivan dizajn

= Podaci o kupcu =

* Ime kupca
* Adresa (ulica, broj, poštanski broj, grad)
* Kontakt telefon

= Podaci o proizvodu =

* Naziv printa
* Boja majice/dukserice
* Veličina (XS - XXXL)
* Broj komada
* Prodajna cena
* Slika proizvoda
* Napomena

= Statusi naloga =

* U obradi - nalog se može menjati
* U izradi - nalog je u procesu izrade
* Storniran - nalog je otkazan
* Završeno - nalog je završen

== Installation ==

1. Uploadujte folder `radni-nalozi-plugin` u `/wp-content/plugins/` direktorijum
2. Aktivirajte plugin kroz 'Plugins' meni u WordPressu
3. Kreirajte novu stranicu i dodajte shortcode `[radni_nalozi]`
4. Korisnici se mogu prijaviti sa svojim WordPress nalogom

== Frequently Asked Questions ==

= Kako korisnici pristupaju sistemu? =

Korisnici koriste svoje postojeće WordPress naloge za prijavu. Admin treba da kreira naloge za klijente.

= Kako se menja status naloga? =

Trenutno se status može menjati direktno u bazi podataka. U budućim verzijama će biti dodat admin panel.

= Mogu li korisnici da vide tuđe naloge? =

Ne, svaki korisnik vidi samo svoje radne naloge.

== Changelog ==

= 1.0.0 =
* Inicijalna verzija
* Kreiranje i upravljanje radnim nalozima
* Frontend login sistem
* Upload slika za proizvode
* Dinamičko dodavanje stavki

== Upgrade Notice ==

= 1.0.0 =
Inicijalna verzija plugina.
