<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
$app = Factory::getApplication();
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8">
	<title><?php echo $this->error->getCode(); ?> – <?php echo $sitename; ?></title>
	<link rel="stylesheet" href="<?php echo Uri::root(); ?>templates/zmk2700/css/template.css">
</head>
<body class="site error">
	<div id="wrapper">
		<header id="header"><div id="logo"><a href="<?php echo Uri::root(); ?>"><span class="sitename"><?php echo $sitename; ?></span></a></div></header>
		<div id="main"><main id="content">
			<h1><?php echo $this->error->getCode(); ?></h1>
			<p><?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
			<p><a href="<?php echo Uri::root(); ?>">Zur Startseite</a></p>
		</main></div>
	</div>
</body>
</html>
