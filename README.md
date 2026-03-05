# radni-nalozi (mono-repo)

Ovaj repo se koristi kao **mono-repo** za WordPress `wp-content` kod koji razvijamo.

## Struktura

```
wp-content/
  plugins/
    radni-nalozi/         # postojeći plugin (štampa odeće) - NE DIRATI / NE MEŠATI
    rn-print-ponude/      # novi plugin (kalkulator + ponude + poručivanje)
  themes/
    (po potrebi samo child tema; Salient parent se ne commituje)
```

> Napomena: `kalkulator/ponude` je **Git branch**, nije folder. Folder `wp-content/plugins/kalkulator/...` ne treba da postoji.

---

## Postojeći plugin: `radni-nalozi`

- Služi za **radne naloge za štampu odeće**
- Mora ostati odvojen i funkcionalan za postojeće korisnike
- Ne uvoditi promene koje mogu da utiču na postojeće tabele/shortcode/flow

---

## Novi plugin: `rn-print-ponude` (ŠTA GRADIMO)

Cilj: napraviti **frontend kalkulator** (kao aio.rs) za štampu na raznim materijalima + **ponude (RFQ)** + opcija **poručivanja**.

### Funkcionalnosti (target)

#### Frontend forma + kalkulacija
- izbor kategorije / proizvoda / materijala
- jedinice zavise od materijala:
  - m² (unos širina/visina; preračun površine)
  - komad
  - tabak (flajeri/tabaci)
  - A formati (A1/A2/A3/A4 preset)
- količina
- dorade (finishing options) koje zavise od materijala
- opcionalno rok izrade
- **instant cena** (AJAX kalkulacija)

#### Fajl ili link
- upload fajlova: `PDF/AI/TIFF/PSD/JPG/PNG`
- tipično 1–150MB
- alternativa: input za **WeTransfer/Google Drive link**
- pravilo: **mora fajl ili link**

#### RFQ + poručivanje
- “Pošalji upit” kreira **ponudu** u bazi
- prikaži cenu odmah:
  - preporuka: “izračunata cena” + status “čeka potvrdu”
- admin može potvrditi/korigovati cenu
- nakon potvrde: opcija “Poruči”
  - MVP: interna porudžbina (bez WooCommerce checkout-a)
  - faza 2: WooCommerce integracija ako treba

#### User flow (preporuka)
- kalkulator + slanje upita dostupno i bez logina (posetioci) uz obavezno email/telefon
- “moje ponude/porudžbine” samo za ulogovane (faza 2 ili odmah, po dogovoru)

---

## Admin panel (rn-print-ponude)

- CRUD: materijali/proizvodi
- CRUD: dorade
- mapiranje: koje dorade pripadaju kom materijalu
- cenovnik sa pragovima:
  - npr. do X m² = cena1, preko = cena2...
  - dorade: fiksno / po m² / po komadu
- pregled ponuda:
  - detalji unosa
  - fajl/link
  - status
  - potvrda/korekcija cene

---

## Bezbednost (upload)

MVP minimum:
- whitelist ekstenzija + MIME provera
- limit veličine (npr. 150MB)
- random naziv fajla
- storage bez mogućnosti izvršavanja

Opcionalno (ako server podrži):
- ClamAV skeniranje fajla + status `scan_pending/scan_ok/scan_failed`

---

## Predlog arhitekture (rn-print-ponude)

- custom DB tabele (ne wp_posts), npr:
  - `rnp_materials`
  - `rnp_finishes`
  - `rnp_material_finishes` (pivot)
  - `rnp_price_rules`
  - `rnp_quotes` (ponude)
  - `rnp_quote_files` (upload meta + link + scan status)
- shortcode: `[rn_print_ponuda]`
- AJAX/REST:
  - `rnp_calculate_price`
  - `rnp_submit_quote`
  - `rnp_upload_file`

---

## MVP (prva verzija – redosled)

1) Plugin bootstrap + shortcode output (da se aktivira u WP)
2) `.gitignore` (uploads/cache/logovi)
3) DB migrate na activation (dbDelta)
4) Admin: materijali + osnovne price rules
5) Frontend: m² kalkulacija + 1–2 dorade + instant price
6) Submit ponude sa fajlom ili linkom
7) Admin lista ponuda + detalji

---

## Napomena o Salient temi
Salient je premium. Preporuka:
- commitovati samo child temu (ako postoji)
- parent Salient tema se instalira ručno