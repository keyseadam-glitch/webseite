<?php
/** Bootstrap für Kommandozeilen-Skripte gegen die lokale Joomla-Installation (../joomla). */
const _JEXEC = 1;
define('JPATH_BASE', realpath(__DIR__ . '/../joomla'));
require_once JPATH_BASE . '/includes/defines.php';
if (!is_file(JPATH_CONFIGURATION . '/configuration.php')) {
    fwrite(STDERR, "Joomla ist noch nicht installiert (configuration.php fehlt). Erst setup.sh ausführen.\n");
    exit(1);
}
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(\Joomla\CMS\Application\ConsoleApplication::class);
Factory::$application = $app;
$app->getLanguage()->load('com_content', JPATH_ADMINISTRATOR);
return $app;
