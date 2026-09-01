# Prompt für die Claude-Browser-Erweiterung: alte Seite 1:1 abholen

Zweck: die Erweiterung läuft in deinem Browser und darf zmk-gruenenplan.de sehen. Sie liefert den
Quelltext jeder Seite, das CSS des Templates und die Bilder als Text-Dump. Den Dump gibst du mir
(hier in den Chat einfügen, oder auf GitHub als `quelle/dump.txt` im Branch
`claude/zmk-gruenenplan-joomla-clone-qit6mb` anlegen). Ich zerlege ihn mit
`php joomla-tools/dump2mirror.php` in den Spiegel und baue daraus die Joomla-Seite.

Kopiere ab hier alles in die Erweiterung:

---

Du hast nur Lesezugriff auf http://zmk-gruenenplan.de. Nicht einloggen, nichts ändern, keine Formulare
absenden. Ziel: eine vollständige, unveränderte Kopie aller Seiten als Text-Dump, damit die Seite
1:1 in Joomla nachgebaut werden kann. Nichts zusammenfassen, nichts umformulieren, nichts weglassen.

Ausgabeformat, exakt so, ein Block pro Datei:

===== DATEI: <Pfad relativ zur Domain> =====
<kompletter, unveränderter Quelltext>
===== ENDE =====

===== BILD: <Pfad relativ zur Domain> =====
<Bilddaten als Base64>
===== ENDE =====

Schritte:

1. Öffne http://zmk-gruenenplan.de/. Lies den Quelltext (view-source:http://zmk-gruenenplan.de/).
   Gib ihn als Block "DATEI: index.html" aus, vollständig, vom <!DOCTYPE bis </html>.
2. Sammle aus dem Hauptmenü alle Links, auch die Unterpunkte: Praxis (Philosophie, Räume, Team),
   Behandlungsspektrum (alle 11 Unterseiten), Kontakt, Links, Impressum und alles, was sonst noch im
   Menü steht. Öffne jede dieser Seiten und gib ihren Quelltext als eigenen DATEI-Block aus. Als Pfad
   den URL-Pfad verwenden, z. B. "praxis/philosophie" oder "index.php/praxis/philosophie", genau so,
   wie er in der Adresszeile steht.
3. Suche im Quelltext von index.html alle <link rel="stylesheet"> und alle <script src=...>, die auf
   dieselbe Domain zeigen (meist unter templates/zmk2700/...). Öffne jede dieser Dateien und gib sie
   als DATEI-Block mit ihrem Pfad aus. Auch CSS-Dateien, die per @import in anderen CSS-Dateien
   geladen werden.
4. Bilder: sammle alle <img src=...> aus allen Seiten und alle url(...) aus den CSS-Dateien, die auf
   dieselbe Domain zeigen, inklusive Logo und Hintergrundbilder. Gib zuerst eine Liste aller Bild-URLs
   aus. Dann, wenn du Bilddaten lesen kannst, jedes Bild als BILD-Block in Base64. Wenn du das nicht
   kannst, lade jedes Bild über den Browser herunter (Rechtsklick, Speichern unter), Dateiname und
   Ordnerstruktur wie im Pfad, und schreibe am Ende eine Liste, welche Bilder heruntergeladen wurden.
5. Gib zusätzlich am Ende einen Block "DATEI: _menue.txt" aus: das Hauptmenü als eingerückte Liste
   mit Titel und URL jedes Punkts in der Reihenfolge der Seite.
6. Mache von jeder Seite einen Screenshot der gesamten Seite und speichere ihn als
   <pfad-mit-bindestrichen>.alt.png, z. B. "praxis-philosophie.alt.png".

Wenn der Dump zu lang für eine Antwort wird, teile ihn in mehrere Antworten und schreibe oben in jede
Antwort "Teil 1 von N", "Teil 2 von N" usw. Blöcke nicht mitten im Inhalt trennen. Erfinde nichts:
wenn eine Seite nicht lädt oder ein Bild nicht lesbar ist, schreibe genau das an die Stelle.

---

## Danach (mache ich)

```
php joomla-tools/dump2mirror.php quelle/dump.txt   # Dump -> quelle/mirror/zmk-gruenenplan.de
php joomla-tools/extract.php                       # Texte, Menü, Bilder, Logo, altes CSS
php joomla-tools/import.php                        # nach Joomla schreiben
```
Heruntergeladene Bilder und alt-Screenshots kommen nach `quelle/mirror/zmk-gruenenplan.de/images/`
bzw. `docs/screenshots/`.
