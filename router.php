<?php

if (isset($_SERVER['DEVHELPER_PHP_APACHE_VERSION_ID'])) {
    $versionExpected = '2018011202';
    $versionActual = $_SERVER['DEVHELPER_PHP_APACHE_VERSION_ID'];
    if ($versionActual !== $versionExpected) {
        die(sprintf('Please rebuild Docker image. Expected %s, actual %s', $versionExpected, $versionActual));
    }
}

require(__DIR__ . '/src/addons/DevHelper/Router.php');
\DevHelper\Router::route(__FILE__);
