# Schritt 3 – Vergleich alt gegen neu: was ist identisch, was nicht, und warum

Stand 1.9.2026. Grundlage: Angaben aus dem Auftrag; die alte Seite selbst war nicht abrufbar (Netzsperre in der Arbeitsumgebung, siehe README). Alles, was hier „identisch“ heißt, ist identisch **mit den Vorgaben aus dem Auftrag**, nicht geprüft gegen die Live-Seite.

## Identisch (nach Vorgabe)

| Punkt | alt | neu |
|---|---|---|
| CMS-Familie | Joomla 3.10.12 | Joomla 6.1.3 (frische Installation, keine Migration) |
| Seitenname | ZMK Grünenplan | ZMK Grünenplan |
| Claim | „We like it smiley!“ | „We like it smiley!“ im Kopf, fester Text im Template |
| Menüstruktur | Praxis (Philosophie / Räume / Team), Behandlungsspektrum (11 Unterseiten), Kontakt, Links, Impressum | gleiche Punkte, gleiche Reihenfolge, gleiche Verschachtelung; Behandlungsspektrum-Unterseiten siehe „offen“ |
| Kontaktdaten | Untere Hilsstr. 16, 31073 Grünenplan, Tel. 05187-75656 | wörtlich übernommen |
| Sprechzeiten | Mo–Fr 8–13 Uhr, Mo/Di/Do zusätzlich 14–18 Uhr | wörtlich übernommen (Tabelle auf „Kontakt“) |
| Kontaktformular | keins | keins |
| Template-Name | zmk2700 | zmk2700 (eigenes Template, kein Framework-Template) |
| URL-Schema | Joomla-SEF-URLs | SEF-URLs mit Rewrite (`/praxis/philosophie`); ob die alte Seite `index.php/` in den URLs hatte, ist ungeprüft |

## Nicht identisch – und warum

| Punkt | Zustand im Klon | Grund | Wie es identisch wird |
|---|---|---|---|
| **Texte** von Home, Philosophie, Räume, Team, Links, Impressum | fehlen, Seite zeigt Markierung „OFFEN“ | Host gesperrt, nichts gecrawlt; erfinden verboten | `quelle/crawl.sh` → `extract.php` → `import.php` |
| **11 Unterseiten Behandlungsspektrum** | Oberpunkt vorhanden, keine Unterseiten | Titel und Reihenfolge stehen nicht im Auftrag | dito, `extract.php` liest sie aus dem Menü der alten Seite |
| **Logo** | fehlt, stattdessen Seitenname als Text | echtes Logo nicht abrufbar; Platzhalter verboten | `extract.php` kopiert es nach `joomla/images/zmk/logo.*`, Template zeigt es dann automatisch |
| **Bilder** (Räume, Team, …) | keine | dito | `extract.php` kopiert `/images` 1:1 mit gleichen Pfaden |
| **Aussehen** (Farben, Schrift, Breite, Menüoptik) | neutrales Gerüst mit 960px-Breite, Arial, ohne Farben | altes CSS/HTML nicht einsehbar | 1. `extract.php` sichert das alte Template und bindet dessen CSS ein; 2. danach `templates/zmk2700/index.php` an das Markup in `quelle/struktur.html` angleichen (IDs/Klassen), damit das alte CSS greift. Das ist der eine Handgriff, der noch Arbeit ist. |
| **Kontaktseite Wortlaut/Aufbau** | Adresse, Telefon, Sprechzeiten in einfacher Form | Originaltext ungeprüft | wird durch Originaltext ersetzt |
| **Home-Menüpunkt** | vorhanden („Home“) | ob die alte Seite einen sichtbaren Home-Punkt hat und was auf der Startseite steht, ist ungeprüft | extract.php |
| **„Praxis“ / „Behandlungsspektrum“** | reine Überschriften ohne eigene Seite | ungeprüft, ob sie eigene Seiten sind | extract.php erkennt Links vs. Überschrift |
| Sprache Backend/Systemtexte | Englisch | deutsches Sprachpaket nicht ladbar (Netzsperre); auf der Seite selbst sind keine Systemtexte sichtbar | im Backend „Install Languages → German“ |
| Kein HTTPS, keine Datenschutzerklärung | lokal nicht relevant / weiterhin keine | bewusst nicht Teil der Testaufgabe | siehe `03-rechtliche-luecken.md` |
| Screenshots alt/neu | nur „neu“ möglich (`docs/screenshots/*.neu.png`) | alt nicht abrufbar | `node joomla-tools/screenshots.mjs` erzeugt beides und `vergleich.html` |

## Offene Liste (kurz)

1. Texte: Home, Philosophie, Räume, Team, Links, Impressum, Kontakt (Originalwortlaut).
2. Behandlungsspektrum: 11 Titel + Texte.
3. Logo, alle Bilder.
4. Altes CSS/HTML → Template-Markup angleichen.
5. Prüfen: Home-Punkt sichtbar? Praxis/Behandlungsspektrum eigene Seiten? URL-Schema mit oder ohne `index.php/`?
6. Deutsches Sprachpaket.
