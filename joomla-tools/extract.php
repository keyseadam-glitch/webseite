<?php
/**
 * Liest den wget-Spiegel der alten Seite (quelle/mirror/zmk-gruenenplan.de) aus und
 *  - füllt quelle/inhalte.json mit Menüstruktur, Seitentiteln und Original-HTML der Artikel,
 *  - kopiert alle Bilder (/images) 1:1 nach joomla/images (gleiche Pfade wie im Original),
 *  - kopiert das Logo nach joomla/images/zmk/logo.<ext>,
 *  - kopiert das alte Template (CSS, Bilder) nach joomla/templates/zmk2700/original und bindet
 *    dessen Stylesheets als css/zmk2700-original.css ein,
 *  - sichert das Markup der alten Startseite als quelle/struktur.html (Vorlage für den Template-Nachbau).
 * Reine Lese-/Kopieroperationen auf dem lokalen Spiegel. Nichts wird erfunden: Seiten ohne erkennbaren
 * Artikeltext bleiben body=null und damit OFFEN.
 * Aufruf:  php joomla-tools/extract.php   (danach php joomla-tools/import.php)
 *
 * HINWEIS: Dieses Skript wurde ohne Zugriff auf die echte Seite geschrieben (Host gesperrt). Die
 * Selektoren entsprechen der Standardausgabe von Joomla 3 (div.item-page, [itemprop=articleBody],
 * ul.menu). Nach dem ersten Crawl bitte das Ergebnis prüfen.
 */
$root   = realpath(__DIR__ . '/..');
$mirror = $root . '/quelle/mirror/zmk-gruenenplan.de';
$jsonF  = $root . '/quelle/inhalte.json';
if (!is_dir($mirror)) { fwrite(STDERR, "Kein Spiegel unter $mirror. Erst quelle/crawl.sh ausführen.\n"); exit(1); }
$data = json_decode(file_get_contents($jsonF), true, 512, JSON_THROW_ON_ERROR);

function loadHtml(string $file): DOMXPath {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $html = file_get_contents($file);
    if (!preg_match('/<meta[^>]+charset/i', $html)) { $html = '<meta charset="utf-8">' . $html; }
    $doc->loadHTML($html);
    libxml_clear_errors();
    return new DOMXPath($doc);
}
function innerHtml(DOMNode $n): string {
    $h = '';
    foreach ($n->childNodes as $c) { $h .= $n->ownerDocument->saveHTML($c); }
    return trim($h);
}
function aliasFromHref(string $href): string {
    $p = parse_url($href, PHP_URL_PATH) ?? '';
    $p = preg_replace('#^.*?zmk-gruenenplan\.de#', '', $p);
    $p = preg_replace('#(index\.php/|\.html$|/$)#', '', $p);
    $p = trim($p, '/');
    $seg = $p === '' ? 'home' : basename($p);
    return preg_replace('/[^a-z0-9\-]/', '-', strtolower($seg));
}

// --- Startseite: Menü, Logo, Claim, Markup -------------------------------------------------------
$index = is_file("$mirror/index.html") ? "$mirror/index.html" : null;
if (!$index) { fwrite(STDERR, "index.html fehlt im Spiegel.\n"); exit(1); }
$xp = loadHtml($index);
copy($index, $root . '/quelle/struktur.html');

// Menü: erste <ul> mit Klasse menu/nav
$ul = $xp->query('//ul[contains(concat(" ",normalize-space(@class)," ")," menu ") or contains(concat(" ",normalize-space(@class)," ")," nav ")]')->item(0);
$pages = []; // alias => [title, file]
$buildMenu = function (DOMNode $ul) use (&$buildMenu, &$pages, $mirror, $xp) {
    $items = [];
    foreach ($xp->query('./li', $ul) as $li) {
        $a = $xp->query('./a|./span', $li)->item(0);
        if (!$a) { continue; }
        $title = trim($a->textContent);
        $href  = $a->getAttribute('href');
        $alias = aliasFromHref($href ?: $title);
        $item  = ['title' => $title, 'alias' => $alias];
        $isHeading = $a->nodeName === 'span' || $href === '' || $href === '#';
        if ($isHeading) { $item['typ'] = 'heading'; }
        elseif (preg_match('#^https?://#', $href) && !str_contains($href, 'zmk-gruenenplan.de')) { $item['typ'] = 'url'; $item['url'] = $href; }
        else {
            $item['artikel'] = $alias;
            $rel = ltrim(preg_replace('#^.*?zmk-gruenenplan\.de/?#', '', $href), '/');
            $cands = [$rel, "$rel.html", rtrim($rel, '/') . '/index.html', "$rel/index.html"];
            if ($rel === '' || $rel === 'index.html') { $cands = ['index.html']; }
            foreach ($cands as $c) { if ($c !== '' && is_file("$mirror/$c")) { $pages[$alias] = ['title' => $title, 'file' => "$mirror/$c"]; break; } }
            if (!isset($pages[$alias])) { $pages[$alias] = ['title' => $title, 'file' => null]; }
        }
        $sub = $xp->query('./ul', $li)->item(0);
        if ($sub) { $item['children'] = $buildMenu($sub); }
        $items[] = $item;
    }
    return $items;
};
if ($ul) {
    $menu = $buildMenu($ul);
    // Home-Markierung: der Punkt, dessen Ziel index.html ist, sonst erster Artikel-Punkt
    $marked = false;
    $mark = function (array &$items) use (&$mark, &$marked, $pages, $mirror) {
        foreach ($items as &$it) {
            if (!$marked && isset($it['artikel']) && ($pages[$it['artikel']]['file'] ?? '') === "$mirror/index.html") { $it['home'] = true; $marked = true; }
            if (!empty($it['children'])) { $mark($it['children']); }
        }
    };
    $mark($menu);
    if (!$marked) { $menu = array_merge([['title' => 'Home', 'alias' => 'home', 'artikel' => 'home', 'home' => true]], $menu); $pages['home'] = ['title' => 'Home', 'file' => "$mirror/index.html"]; }
    $data['menu'] = $menu;
    echo "Menü übernommen: " . count($menu) . " Hauptpunkte\n";
} else {
    echo "WARNUNG: Kein Menü (ul.menu/ul.nav) in index.html gefunden – Menü aus inhalte.json bleibt.\n";
}

// --- Artikel ---------------------------------------------------------------------------------------
$artikel = [];
$catFor = function (array $menu, string $alias, ?string $parent = null) use (&$catFor) {
    foreach ($menu as $it) {
        if (($it['artikel'] ?? '') === $alias) { return $parent ?? 'Allgemein'; }
        if (!empty($it['children'])) { $r = $catFor($it['children'], $alias, $it['title']); if ($r) { return $r; } }
    }
    return null;
};
$kategorien = ['Allgemein'];
foreach ($pages as $alias => $pg) {
    $cat = $catFor($data['menu'], $alias) ?? 'Allgemein';
    if (!in_array($cat, $kategorien, true)) { $kategorien[] = $cat; }
    $entry = ['title' => $pg['title'], 'kategorie' => $cat, 'body' => null];
    if ($pg['file']) {
        $px = loadHtml($pg['file']);
        $bodyNode = $px->query('//*[@itemprop="articleBody"]')->item(0)
            ?: $px->query('//div[contains(@class,"item-page")]')->item(0)
            ?: $px->query('//div[contains(@class,"blog")]')->item(0);
        $h = $px->query('//div[contains(@class,"item-page")]//h1|//div[contains(@class,"item-page")]//h2|//*[@itemprop="headline"]')->item(0);
        if ($h) { $entry['title'] = trim($h->textContent); }
        if ($bodyNode) {
            $html = innerHtml($bodyNode);
            // Pfade auf den Spiegel zurück auf Site-Pfade (images/... bleibt identisch)
            $html = preg_replace('#(src|href)="(\.\./)*(images/[^"]+)"#', '$1="$3"', $html);
            $entry['body'] = $html;
        }
        $t = $px->query('//title')->item(0); if ($t) { $entry['seitentitel'] = trim($t->textContent); }
        $md = $px->query('//meta[@name="description"]/@content')->item(0); if ($md) { $entry['metadesc'] = trim($md->nodeValue); }
    }
    $artikel[$alias] = $entry;
    echo ($entry['body'] === null ? "OFFEN   " : "ok      ") . "$alias  ({$entry['title']})\n";
}
$data['artikel'] = $artikel;
$data['kategorien'] = $kategorien;

// --- Bilder & Logo ---------------------------------------------------------------------------------
$copyDir = function (string $src, string $dst) use (&$copyDir) {
    if (!is_dir($src)) { return 0; }
    $n = 0; @mkdir($dst, 0755, true);
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)) as $f) {
        $rel = substr($f->getPathname(), strlen($src) + 1);
        @mkdir(dirname("$dst/$rel"), 0755, true);
        copy($f->getPathname(), "$dst/$rel"); $n++;
    }
    return $n;
};
echo "Bilder kopiert: " . $copyDir("$mirror/images", "$root/joomla/images") . "\n";
$logo = $xp->query('//*[@id="logo"]//img|//img[contains(@src,"logo")]')->item(0);
if ($logo) {
    $src = preg_replace('#^(\.\./)*#', '', $logo->getAttribute('src'));
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION)) ?: 'png';
    if (is_file("$mirror/$src")) { @mkdir("$root/joomla/images/zmk", 0755, true); copy("$mirror/$src", "$root/joomla/images/zmk/logo.$ext"); echo "Logo: $src -> images/zmk/logo.$ext\n"; }
} else { echo "WARNUNG: Logo nicht automatisch erkannt (kein #logo img / *logo*). Bitte manuell nach joomla/images/zmk/logo.png kopieren.\n"; }
if (!str_contains(file_get_contents($index), 'We like it smiley')) { echo "HINWEIS: Claim 'We like it smiley!' nicht in index.html gefunden – Stelle/Schreibweise prüfen.\n"; }

// --- Altes Template sichern & CSS einbinden --------------------------------------------------------
$n = $copyDir("$mirror/templates/zmk2700", "$root/joomla/templates/zmk2700/original");
if ($n) {
    $imports = '';
    foreach (glob("$root/joomla/templates/zmk2700/original/css/*.css") ?: [] as $css) { $imports .= '@import url("../original/css/' . basename($css) . "\");\n"; }
    file_put_contents("$root/joomla/templates/zmk2700/css/zmk2700-original.css", "/* Original-Stylesheets des alten Templates zmk2700 (unverändert kopiert) */\n" . $imports);
    echo "Altes Template gesichert ($n Dateien), Stylesheets eingebunden.\n";
} else { echo "HINWEIS: templates/zmk2700 nicht im Spiegel (wget --page-requisites sollte CSS mitnehmen).\n"; }

file_put_contents($jsonF, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
echo "inhalte.json aktualisiert. Jetzt: php joomla-tools/import.php\n";
