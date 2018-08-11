<?php

chdir('/var/www/html/xenforo');
$_SERVER['DEVHELPER_ROUTER_PHP_TARGET'] = '/var/www/html/addons/xfrocks/bdImportCmd/library/bdImportCmd/deferred.php';

if (!file_exists($_SERVER['DEVHELPER_ROUTER_PHP_TARGET'])) {
    die('Please clone bdImportCmd into addons/xfrocks/bdImportCmd before continue');
}

require(__DIR__ . '/router.php');
