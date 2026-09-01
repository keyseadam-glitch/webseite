<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
$app = Factory::getApplication();
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>">
<head><jdoc:include type="metas" /><jdoc:include type="styles" /><jdoc:include type="scripts" /></head>
<body class="site offline">
	<div id="wrapper"><div id="main"><main id="content">
		<h1><?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8'); ?></h1>
		<p><?php echo htmlspecialchars($app->get('offline_message'), ENT_QUOTES, 'UTF-8'); ?></p>
	</main></div></div>
</body>
</html>
