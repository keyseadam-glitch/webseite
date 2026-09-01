<?php
/**
 * Importiert die Struktur/Inhalte aus quelle/inhalte.json in die lokale Joomla-Installation.
 * Idempotent: Kategorien/Artikel werden über den Alias wiedererkannt und aktualisiert,
 * das Hauptmenü (mainmenu) wird komplett neu aufgebaut.
 * Aufruf:  php joomla-tools/import.php
 */
$app = require __DIR__ . '/bootstrap.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Category;
use Joomla\CMS\Table\Content;
use Joomla\CMS\Table\Menu;
use Joomla\Database\ParameterType;

$db   = Factory::getDbo();
$json = __DIR__ . '/../quelle/inhalte.json';
$data = json_decode(file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);
$now  = Factory::getDate()->toSql();

$userId = (int) $db->setQuery("SELECT id FROM #__users ORDER BY id ASC LIMIT 1")->loadResult();
$comContent = (int) $db->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_content'")->loadResult();

$OFFEN = '<div class="offen"><strong>OFFEN:</strong> Der Text dieser Seite konnte noch nicht aus dem Original übernommen werden '
       . '(zmk-gruenenplan.de war aus der Arbeitsumgebung nicht erreichbar). Nach <code>bash quelle/crawl.sh</code> füllt '
       . '<code>joomla-tools/extract.php</code> diese Seite mit dem Originaltext. Es wird nichts erfunden.</div>';

// --- 1. Template zmk2700 als Standard-Stil ---------------------------------------------------
$styleId = (int) $db->setQuery("SELECT id FROM #__template_styles WHERE client_id=0 AND template='zmk2700'")->loadResult();
if ($styleId) {
    $db->setQuery("UPDATE #__template_styles SET home='0' WHERE client_id=0")->execute();
    $db->setQuery("UPDATE #__template_styles SET home='1', title='zmk2700 - Standard' WHERE id=$styleId")->execute();
    echo "Template-Stil zmk2700 (id $styleId) ist Standard.\n";
} else {
    echo "WARNUNG: Template zmk2700 ist nicht installiert (extension:discover:install).\n";
}

// --- 2. Kategorien ------------------------------------------------------------------------------
$catIds = [];
foreach ($data['kategorien'] as $title) {
    $alias = \Joomla\CMS\Filter\OutputFilter::stringUrlSafe($title);
    $id = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_content' AND alias=" . $db->quote($alias))->loadResult();
    if (!$id) {
        $cat = new Category($db);
        $cat->setLocation(1, 'last-child');
        $cat->bind([
            'title' => $title, 'alias' => $alias, 'extension' => 'com_content', 'published' => 1, 'access' => 1,
            'language' => '*', 'params' => '{"category_layout":"","image":"","image_alt":""}',
            'metadata' => '{"author":"","robots":""}', 'description' => '', 'created_user_id' => $userId, 'created_time' => $now,
        ]);
        if (!$cat->check() || !$cat->store()) { throw new RuntimeException("Kategorie $title: " . $cat->getError()); }
        $id = (int) $cat->id;
        echo "Kategorie angelegt: $title (id $id)\n";
    }
    $catIds[$title] = $id;
}

// --- 3. Artikel ---------------------------------------------------------------------------------
$artIds = [];
foreach ($data['artikel'] as $key => $a) {
    $alias = $a['alias'] ?? $key;
    $body  = $a['body'] ?? null;
    $text  = $body !== null && trim($body) !== '' ? $body : $OFFEN;
    $catId = $catIds[$a['kategorie']] ?? $catIds['Allgemein'];
    $id = (int) $db->setQuery("SELECT id FROM #__content WHERE alias=" . $db->quote($alias))->loadResult();
    $art = new Content($db);
    if ($id) { $art->load($id); }
    $art->bind([
        'title' => $a['title'], 'alias' => $alias, 'introtext' => $text, 'fulltext' => '', 'state' => 1, 'catid' => $catId,
        'created' => $art->created ?: $now, 'created_by' => $userId, 'modified' => $now, 'modified_by' => $userId,
        'publish_up' => $art->publish_up ?: $now, 'publish_down' => null, 'images' => '{}', 'urls' => '{}', 'attribs' => '{}',
        'metakey' => '', 'metadesc' => $a['metadesc'] ?? '', 'metadata' => '{"robots":"","author":"","rights":""}',
        'access' => 1, 'featured' => 0, 'language' => '*', 'note' => $body === null ? 'OFFEN: Originaltext fehlt' : '',
    ]);
    if (!$art->check() || !$art->store()) { throw new RuntimeException("Artikel {$a['title']}: " . $art->getError()); }
    $artIds[$key] = (int) $art->id;
    echo ($id ? "Artikel aktualisiert: " : "Artikel angelegt: ") . $a['title'] . ' (id ' . $art->id . ($body === null ? ', TEXT OFFEN' : '') . ")\n";
}

// --- 4. Hauptmenü neu aufbauen ---------------------------------------------------------------------
$db->setQuery("DELETE FROM #__menu WHERE client_id=0 AND menutype='mainmenu'")->execute();
$root = new Menu($db);
$root->rebuild(1); // Nested-Set-Werte nach dem Löschen reparieren
$homeId = 0;
$addItems = function (array $items, int $parentId, int $level) use (&$addItems, &$homeId, $db, $artIds, $comContent) {
    foreach ($items as $it) {
        $m = new Menu($db);
        $m->setLocation($parentId, 'last-child');
        $type = $it['typ'] ?? (isset($it['artikel']) ? 'component' : 'heading');
        $row = [
            'menutype' => 'mainmenu', 'title' => $it['title'], 'alias' => $it['alias'], 'type' => $type, 'published' => 1,
            'access' => 1, 'language' => '*', 'client_id' => 0, 'img' => '', 'home' => 0, 'level' => $level,
            'params' => '{"menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_text":1,"menu_show":1}',
        ];
        if ($type === 'component') {
            $aid = $artIds[$it['artikel']] ?? null;
            if (!$aid) { throw new RuntimeException("Menüpunkt {$it['title']}: Artikel {$it['artikel']} fehlt"); }
            $row['link'] = 'index.php?option=com_content&view=article&id=' . $aid;
            $row['component_id'] = $comContent;
        } elseif ($type === 'url') {
            $row['link'] = $it['url'];
            $row['component_id'] = 0;
        } else {
            $row['link'] = '';
            $row['component_id'] = 0;
        }
        $m->bind($row);
        if (!$m->check() || !$m->store()) { throw new RuntimeException("Menüpunkt {$it['title']}: " . $m->getError()); }
        echo str_repeat('  ', $level - 1) . "Menü: {$it['title']} ($type, id {$m->id})\n";
        if (!empty($it['home'])) { $homeId = (int) $m->id; }
        if (!empty($it['children'])) { $addItems($it['children'], (int) $m->id, $level + 1); }
    }
};
$addItems($data['menu'], 1, 1);
if (!$homeId) {
    $homeId = (int) $db->setQuery("SELECT id FROM #__menu WHERE menutype='mainmenu' AND type='component' ORDER BY lft LIMIT 1")->loadResult();
}
$db->setQuery("UPDATE #__menu SET home=0 WHERE client_id=0")->execute();
$db->setQuery("UPDATE #__menu SET home=1 WHERE id=$homeId")->execute();
(new Menu($db))->rebuild(1);

// --- 5. Module: Hauptmenü in Position "menu", Rest der Beispielmodule aus ----------------------------
$db->setQuery("UPDATE #__modules SET published=0 WHERE client_id=0 AND module IN ('mod_login','mod_breadcrumbs')")->execute();
$menuMod = (int) $db->setQuery("SELECT id FROM #__modules WHERE client_id=0 AND module='mod_menu' ORDER BY id LIMIT 1")->loadResult();
$params = json_encode(['menutype' => 'mainmenu', 'base' => '', 'startLevel' => 1, 'endLevel' => 0, 'showAllChildren' => 1,
    'tag_id' => '', 'class_sfx' => '', 'window_open' => '', 'layout' => '_:default', 'moduleclass_sfx' => '', 'cache' => 0, 'cache_time' => 900, 'cachemode' => 'itemid']);
$db->setQuery("UPDATE #__modules SET title='Hauptmenü', position='menu', published=1, showtitle=0, params=" . $db->quote($params) . " WHERE id=$menuMod")->execute();
$db->setQuery("DELETE FROM #__modules_menu WHERE moduleid=$menuMod")->execute();
$db->setQuery("INSERT INTO #__modules_menu (moduleid, menuid) VALUES ($menuMod, 0)")->execute();
echo "Hauptmenü-Modul (id $menuMod) in Position 'menu'.\n";

// --- 6. Sitename/Claim ----------------------------------------------------------------------------
echo "Fertig. Offene Texte: " . count(array_filter($data['artikel'], fn($a) => ($a['body'] ?? null) === null)) . "\n";

// --- 7. Artikelanzeige wie auf einer schlichten Praxisseite: nur Titel + Text ----------------------------
// (kein Kategorie-/Autor-/Datums-/Zugriffszähler-Block, keine Druck-/Mail-Icons, keine Tags, keine Vor/Zurück-Navigation)
$p = json_decode($db->setQuery("SELECT params FROM #__extensions WHERE type='component' AND element='com_content'")->loadResult(), true) ?: [];
$p = array_merge($p, ['show_title' => '1', 'link_titles' => '0', 'show_category' => '0', 'show_parent_category' => '0', 'show_author' => '0',
    'show_create_date' => '0', 'show_modify_date' => '0', 'show_publish_date' => '0', 'show_item_navigation' => '0', 'show_hits' => '0',
    'record_hits' => '0', 'show_tags' => '0', 'show_print_icon' => '0', 'show_email_icon' => '0', 'show_vote' => '0', 'show_associations' => '0',
    'show_readmore' => '0', 'show_publishing_options' => '0', 'show_article_options' => '0', 'show_urls_images_frontend' => '0']);
$db->setQuery("UPDATE #__extensions SET params=" . $db->quote(json_encode($p)) . " WHERE type='component' AND element='com_content'")->execute();
echo "com_content: Anzeigeoptionen auf 'nur Titel + Text' gesetzt.\n";
