<?php
\defined('_JEXEC') or die;
$wa = $this->getWebAssetManager();
$wa->registerAndUseStyle('template.zmk2700', 'templates/' . $this->template . '/css/template.css');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>
<body class="contentpane">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
</body>
</html>
