<?php
/**
 * Zerlegt einen Text-Dump der alten Seite (z. B. von der Claude-Browser-Erweiterung erzeugt)
 * in den Spiegel-Ordner quelle/mirror/zmk-gruenenplan.de, so als hätte wget die Seite gespiegelt.
 * Danach laufen extract.php und import.php wie gewohnt.
 *
 * Dump-Format (Blöcke, Reihenfolge egal):
 *   ===== DATEI: index.html =====
 *   <html> ... (Quelltext der Seite, unverändert) ...
 *   ===== ENDE =====
 *   ===== DATEI: templates/zmk2700/css/template.css =====
 *   ... CSS ...
 *   ===== ENDE =====
 *   ===== BILD: images/logo.png =====
 *   iVBORw0KGgo... (Base64, darf Zeilenumbrüche enthalten)
 *   ===== ENDE =====
 *
 * Aufruf:  php joomla-tools/dump2mirror.php quelle/dump.txt [weitere Dumps ...]
 */
$root   = realpath(__DIR__ . '/..');
$mirror = $root . '/quelle/mirror/zmk-gruenenplan.de';
$files  = array_slice($argv, 1) ?: [$root . '/quelle/dump.txt'];
$n = 0;
foreach ($files as $f) {
    if (!is_file($f)) { fwrite(STDERR, "Datei fehlt: $f\n"); exit(1); }
    $txt = file_get_contents($f);
    if (!preg_match_all('/^=====\s*(DATEI|BILD):\s*(.+?)\s*=====\r?\n(.*?)\r?\n=====\s*ENDE\s*=====/ms', $txt, $m, PREG_SET_ORDER)) {
        fwrite(STDERR, "Keine Blöcke in $f gefunden (Format siehe Kopf dieses Skripts).\n"); continue;
    }
    foreach ($m as [$all, $art, $pfad, $inhalt]) {
        $pfad = ltrim(str_replace(['\\', '..'], ['/', ''], trim($pfad)), '/');
        $pfad = preg_replace('#^(https?://)?(www\.)?zmk-gruenenplan\.de/?#', '', $pfad);
        if ($pfad === '' || str_ends_with($pfad, '/')) { $pfad .= 'index.html'; }
        if ($art === 'DATEI' && !preg_match('/\.(html?|css|js|txt|xml)$/i', $pfad)) { $pfad .= '.html'; }
        $ziel = "$mirror/$pfad";
        @mkdir(dirname($ziel), 0755, true);
        $data = $art === 'BILD' ? base64_decode(preg_replace('/\s+/', '', $inhalt), true) : $inhalt;
        if ($data === false) { echo "WARNUNG: Base64 ungültig für $pfad\n"; continue; }
        file_put_contents($ziel, $data);
        echo str_pad($art, 6) . " $pfad (" . strlen($data) . " Bytes)\n";
        $n++;
    }
}
echo "$n Dateien nach $mirror geschrieben. Jetzt: php joomla-tools/extract.php && php joomla-tools/import.php\n";
