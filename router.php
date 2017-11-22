<?php

if (empty($_SERVER['DEVHELPER_PHP_APACHE_VERSION_ID']) ||
    $_SERVER['DEVHELPER_PHP_APACHE_VERSION_ID'] !== '2017112201'
) {
    die('Please make sure the Docker container is up to date.');
}

require(__DIR__ . '/src/addons/DevHelper/Router.php');
\DevHelper\Router::route(__FILE__);
