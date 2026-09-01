<?php
// Router für den PHP-Built-in-Server (php -S ... joomla/router.php). Ersetzt die .htaccess:
// echte Dateien werden direkt ausgeliefert, alle anderen Pfade gehen an Joomla (index.php).
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
$dir = rtrim($path, '/');
if ($dir !== '' && is_dir(__DIR__ . $dir) && is_file(__DIR__ . $dir . '/index.php')) {
    // z. B. /administrator oder /api
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $dir . '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . $dir . '/index.php';
    chdir(__DIR__ . $dir);
    require __DIR__ . $dir . '/index.php';
    return true;
}
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
chdir(__DIR__);
require __DIR__ . '/index.php';
