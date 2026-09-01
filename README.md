# ZMK Grünenplan – Klon der alten Seite als Joomla 6

Testaufgabe von Dr. M. Khayami (Zahnarztpraxis ZMK Grünenplan) an Keyse Adam:
die bestehende Seite http://zmk-gruenenplan.de (Joomla 3.10.12, EOL) **1:1 als frische Joomla-Installation** nachbauen.
Nur klonen, kein Redesign.

## Stand (1. September 2026) – bitte zuerst lesen

| Schritt | Status |
|---|---|
| 1 Joomla-Version geprüft | **erledigt** → `docs/01-analyse-joomla-version.md`: aktuell **Joomla 6.1.3**, PHP ≥ 8.3. **Joomla 7 gibt es noch nicht.** |
| 2 Alte Seite erfassen | **blockiert** – der Host `zmk-gruenenplan.de` (und archive.org) ist aus der Arbeitsumgebung netzseitig gesperrt. Es wurde nichts gecrawlt. Crawl-Skript liegt bereit: `quelle/crawl.sh` (auf dem Mac ausführen, reiner Lesezugriff). |
| 3 Lokal nachbauen | **Gerüst fertig, Inhalte offen** – Joomla 6.1.3 installiert, Menüstruktur/Kategorien/Artikel laut Auftrag angelegt, eigenes Template `zmk2700` als Gerüst. Originaltexte, Bilder, Logo und das exakte Aussehen fehlen, weil Schritt 2 blockiert ist. Der Import aus dem Crawl ist automatisiert (`joomla-tools/extract.php` + `import.php`). |

Offene Punkte und Abweichungen: `docs/02-vergleich-alt-neu.md`. Rechtliche Lücken der alten Seite: `docs/03-rechtliche-luecken.md`. Text an den Kunden: `docs/04-nachricht-an-kunden.md`.

## Start in einer Zeile

```
bash setup.sh && bash start.sh
```
Danach: http://localhost:8080 (Backend http://localhost:8080/administrator, Benutzer `admin`, Passwort `ZmkKlon-2026!`, nur lokal).

- `setup.sh` lädt das offizielle Joomla-6.1.3-Vollpaket, installiert es per Joomla-CLI in MariaDB (DB `zmk`, Benutzer `zmk`/`zmk`), aktiviert das Template und importiert die Inhalte aus `quelle/inhalte.json`. Liegt `quelle/mirror` (Crawl) vor, werden vorher Texte, Bilder, Logo und altes CSS daraus übernommen.
- `start.sh` startet MariaDB, falls nötig, und den PHP-Built-in-Server. Dafür ist in Joomla `live_site = http://localhost:8080` gesetzt (der Built-in-Server meldet sich als „cli-server“, Joomla kann den Basispfad sonst nicht bestimmen). Beim Umzug auf einen echten Webserver diesen Wert leeren.
- Voraussetzungen auf dem Mac: `brew install php mariadb` (PHP ≥ 8.3).

**Warum PHP-Built-in-Server + MariaDB statt DDEV/Docker:** in der Arbeitsumgebung lief kein Docker-Daemon, PHP 8.4 war vorhanden, MariaDB kam aus den Ubuntu-Paketquellen – null zusätzliche Werkzeuge, zwei Prozesse, ein Startbefehl.

## Ablauf, um den Klon fertigzustellen (auf dem Mac, mit Internet)

```
cd /Users/keys/zmk-klon            # oder wo das Repo liegt
bash quelle/crawl.sh               # 1. alte Seite spiegeln (nur lesen) -> quelle/mirror
php joomla-tools/extract.php       # 2. Texte/Menü/Bilder/Logo/CSS aus dem Spiegel -> inhalte.json, joomla/images, template
php joomla-tools/import.php        # 3. in Joomla schreiben (idempotent)
node joomla-tools/screenshots.mjs  # 4. Screenshots alt gegen neu -> docs/screenshots/vergleich.html
```
Danach `quelle/mirror` und `quelle/inhalte.json` einchecken, dann kann das Template-Markup am Original ausgerichtet werden (siehe `docs/02-vergleich-alt-neu.md`, Punkt „Aussehen“).

## Was liegt wo

```
setup.sh, start.sh          Einrichtung / Start
joomla/                     Joomla 6.1.3 (Kern nicht eingecheckt, kommt aus setup.sh)
joomla/templates/zmk2700/   eigenes Template (Gerüst: Kopf mit Logo + Claim, Hauptmenü, Inhalt, Fuß)
joomla/router.php           Router für den PHP-Built-in-Server (ersetzt .htaccess)
joomla-tools/               bootstrap.php, extract.php (Spiegel -> JSON), import.php (JSON -> Joomla), screenshots.mjs
quelle/                     crawl.sh, inhalte.json (Struktur + bekannte Fakten), später mirror/ (Rohdaten der alten Seite)
docs/                       Analyse, Vergleich, rechtliche Lücken, Kundentext, Belege, Screenshots
```

## Harte Regeln (eingehalten)

- Kein Zugriff auf den Live-Server außer Lesen. Kein Login, keine Zugangsdaten. Tatsächlich fand in dieser Umgebung gar kein Zugriff statt (gesperrt).
- Keine erfundenen Texte: Seiten ohne Originaltext tragen sichtbar die Markierung „OFFEN“ und stehen in der offenen Liste.
- Keine Platzhalterbilder, keine Stockfotos, keine KI-Bilder: es sind derzeit **gar keine** Bilder im Klon. Logo und Bilder kommen 1:1 aus dem Crawl.
- Kein Kontaktformular. Kontaktseite = Adresse, Telefon, Sprechzeiten.
- Keine Migration der alten Joomla-3-Installation, sondern frische Installation.
