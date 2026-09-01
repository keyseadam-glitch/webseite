<?php
/**
 * Template-Override für mod_menu: schlichte verschachtelte Liste ohne Aufklapp-Buttons/Icons,
 * wie sie ein Joomla-3-Menü (alte Seite) ausgibt. Unterpunkte werden immer mit ausgegeben.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$id = $params->get('tag_id') ? ' id="' . htmlspecialchars($params->get('tag_id')) . '"' : '';
?>
<ul class="menu<?php echo $class_sfx; ?>"<?php echo $id; ?>>
<?php foreach ($list as $i => &$item) :
    $class = 'item-' . $item->id;
    if ($item->id == $active_id) { $class .= ' current'; }
    if (\in_array($item->id, $path)) { $class .= ' active'; }
    elseif ($item->type === 'alias') {
        $aliasToId = $item->getParams()->get('aliasoptions');
        if (\count($path) > 0 && $aliasToId == $path[\count($path) - 1]) { $class .= ' active'; }
        elseif (\in_array($aliasToId, $path)) { $class .= ' alias-parent-active'; }
    }
    if ($item->type === 'separator') { $class .= ' divider'; }
    if ($item->deeper) { $class .= ' deeper'; }
    if ($item->parent) { $class .= ' parent'; }
    echo '<li class="' . $class . '">';
    switch ($item->type) :
        case 'separator':
        case 'component':
        case 'heading':
        case 'url':
            require ModuleHelper::getLayoutPath('mod_menu', 'default_' . $item->type);
            break;
        default:
            require ModuleHelper::getLayoutPath('mod_menu', 'default_url');
            break;
    endswitch;
    if ($item->deeper) { echo '<ul class="sub">'; }
    elseif ($item->shallower) { echo '</li>' . str_repeat('</ul></li>', $item->level_diff); }
    else { echo '</li>'; }
endforeach; ?>
</ul>
