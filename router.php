<?php

$startTime = microtime(true);
$fileDir = dirname(__FILE__);
$xenforoDir = sprintf('%s/xenforo', $fileDir);
if (!is_dir($xenforoDir)) {
	die(sprintf('%s not found!', $xenforoDir));
}

$xenforoIndex = sprintf('%s/index.php', $xenforoDir);;
$xenforoAdmin = sprintf('%s/admin.php', $xenforoDir);;
$target = $xenforoIndex;

$requestUri = $_SERVER['REQUEST_URI'];
$requestUriParsed = parse_url($requestUri);
if (isset($requestUriParsed['path'])) {
	$target = sprintf('%s%s', $xenforoDir, $requestUriParsed['path']);
	if (is_dir($target)) {
		$target = rtrim($target, '/') . '/index.php';
	}
}

$_SERVER['DEVHELPER_ROUTER_PHP'] = __FILE__;
$_SERVER['SCRIPT_FILENAME'] = $target;
$_SERVER['SCRIPT_NAME'] = preg_replace(sprintf('#^%s#', preg_quote($xenforoDir, '#')), '', $target);
unset($_SERVER['PHP_SELF']);
unset($_SERVER['ORIG_SCRIPT_NAME']);

if (!in_array($target, array($xenforoIndex, $xenforoAdmin), true)
	&& is_file($target)
) {
	$extension = strtolower(substr(strrchr($target, '.'), 1));
	if ($extension === 'php') {
		require($target);
		exit;
	}

	$mimeTypes = array(
		'css' => 'text/css',
		'jpg' => 'image/jpeg',
		'js' => 'application/javascript',
		'png' => 'image/png',
	);
	if (isset($mimeTypes[$extension])) {
		header("Content-Type: {$mimeTypes[$extension]}");
	}

	header('Content-Length: ' . filesize($target));
	$fp = fopen($target, 'rb');
	fpassthru($fp);
	fclose($fp);
	exit;
}

require($fileDir . '/xenforo/library/XenForo/Autoloader.php');
require($fileDir . '/library/DevHelper/Autoloader.php');
DevHelper_Autoloader::getDevHelperInstance()->setupAutoloader($fileDir . '/xenforo/library');

XenForo_Application::initialize($fileDir . '/xenforo/library', $fileDir . '/xenforo');
XenForo_Application::set('page_start_time', $startTime);

$dependencies = $target === $xenforoAdmin
	? new XenForo_Dependencies_Admin()
	: new XenForo_Dependencies_Public();
$fc = new XenForo_FrontController($dependencies);

$fc->run();