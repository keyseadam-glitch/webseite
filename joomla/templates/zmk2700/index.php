<?php
/**
 * Template zmk2700 – Nachbau der alten Seite zmk-gruenenplan.de (Template "zmk2700", 2013).
 * Struktur: Kopf mit Logo + Claim, horizontales Hauptmenü, Inhalt, Fuß.
 * Das originale Stylesheet der alten Seite wird – sobald gecrawlt – als css/zmk2700-original.css
 * eingebunden (siehe joomla-tools/extract.php). css/template.css enthält nur das Grundgerüst.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */
$app  = Factory::getApplication();
$wa   = $this->getWebAssetManager();
$tpl  = 'templates/' . $this->template;
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');

$wa->registerAndUseStyle('template.zmk2700', $tpl . '/css/template.css');
if (is_file(JPATH_THEMES . '/' . $this->template . '/css/zmk2700-original.css')) {
    $wa->registerAndUseStyle('template.zmk2700.original', $tpl . '/css/zmk2700-original.css', [], [], ['template.zmk2700']);
}

// Echtes Logo der alten Seite (wird von extract.php nach images/zmk kopiert). Kein Platzhalter.
$logo = null;
foreach (['logo.png', 'logo.jpg', 'logo.gif', 'logo.svg'] as $candidate) {
    if (is_file(JPATH_ROOT . '/images/zmk/' . $candidate)) { $logo = 'images/zmk/' . $candidate; break; }
}

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$pageclass = $app->getMenu()->getActive()->getParams()->get('pageclass_sfx', '') ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>
<body class="site<?php echo $pageclass ? ' ' . htmlspecialchars($pageclass) : ''; ?>">
	<div id="wrapper">
		<header id="header">
			<div id="logo">
				<a href="<?php echo Uri::root(); ?>">
				<?php if ($logo) : ?>
					<img src="<?php echo Uri::root() . $logo; ?>" alt="<?php echo $sitename; ?>">
				<?php else : ?>
					<span class="sitename"><?php echo $sitename; ?></span>
				<?php endif; ?>
				</a>
				<span id="claim">We like it smiley!</span>
			</div>
			<jdoc:include type="modules" name="header" style="none" />
		</header>
		<nav id="mainnav">
			<jdoc:include type="modules" name="menu" style="none" />
		</nav>
		<div id="main">
			<jdoc:include type="modules" name="content-top" style="none" />
			<jdoc:include type="message" />
			<main id="content">
				<jdoc:include type="component" />
			</main>
			<?php if ($this->countModules('sidebar')) : ?>
			<aside id="sidebar">
				<jdoc:include type="modules" name="sidebar" style="none" />
			</aside>
			<?php endif; ?>
			<jdoc:include type="modules" name="content-bottom" style="none" />
		</div>
		<footer id="footer">
			<jdoc:include type="modules" name="footer" style="none" />
		</footer>
	</div>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
