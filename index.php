<?php

// Preset PHP settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');

// Define this as parent file
define('INDEX', 1);

// Define root directory
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

// Define directories
define('INCLUDES', ROOT . 'includes' . DS);
define('LIBRARIES', ROOT . 'libraries' . DS);
define('LOGS', ROOT . 'logs' . DS);
define('PAGES', ROOT . 'pages' . DS);
define('STORAGE', ROOT . 'storage' . DS);

ob_start();

// Load libraries
require LIBRARIES . 'autoload.php';

// Error Handler
new \Error\Handler(function ($file, $line, $error) {
	// Log error
	file_put_contents(LOGS . 'error.log', json_encode([
		'date'  => date('Y-m-d H:i:s', strtotime('+8 hours')),
		'error' => $error,
		'file'  => $file . ': ' . $line,
		'ip'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
		'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? '-',
		'url'   => $_SERVER['REQUEST_URI'] ?? '-',
	]) . "\n", FILE_APPEND);

	ob_clean();

	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
	include ROOT . '50x.html';
	ob_end_flush();
	exit;
});

// Configuration
require ROOT . 'configuration.php';

if (!isset($config['username'])) {
	header('Location: ./install');
	exit;
}

// Core functions
require INCLUDES . 'functions.php';

// Page Request
$request = new \Http\Request();

// Session
$session = new \Session\Session();

// Cookie
$cookie = new \Cookie\Cookie($config['privateKey']);

// SQLite Database
try {
	$db = new \SQLite\Database(STORAGE . substr(hash('sha256', $config['privateKey']), 0, 32) . '.sqlite');
	$db->saveErrorLog(LOGS . 'sqlite-error.log');
} catch (Exception $e) {
	ob_clean();

	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	include ROOT . '50x.html';
	ob_end_flush();

	file_put_contents(LOGS . 'sqlite-error.log', implode("\t", [
		gmdate('Y-m-d H:i:s'),
		$e->getFile(),
		$e->getLine(),
		$e->getMessage(),
	]) . "\n", FILE_APPEND);

	exit;
}

// Cookie authentication
if (!$session->get('user') && strpos((string) $cookie->get('auth'), ';') !== false) {
	list($username, $hash) = explode(';', $cookie->get('auth'));

	if ($username == $config['username'] && $hash == hash('sha256', $config['privateKey'] . $config['password'] . $config['privateKey'])) {
		$session->set('user', true);
	}
}

// Get requested page
$_PAGE = basename(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']));
$_PAGE = (substr($_PAGE, -5) == '.json') ? ('json.' . substr($_PAGE, 0, -5)) : ((!empty($_PAGE)) ? $_PAGE : 'core');

// Validate pages to prevent file inclusion vulnerability
$pages = [];
$files = scandir(PAGES);

foreach ($files as $file) {
	if (substr($file, -4) != '.php') {
		continue;
	}

	$pages[substr($file, 0, -4)] = true;
}

// Requested page is not found
if (!isset($pages[$_PAGE])) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	require ROOT . '404.html';
	ob_end_flush();
	exit;
}

// CSRF protection
$form = new \Security\Form(true, 86400);

// Display requested page
require PAGES . $_PAGE . '.php';

ob_end_flush();
