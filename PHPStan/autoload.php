<?php

foreach (glob('/var/www/html/src/addons/DevHelper/PHPStan/**/*.php') as $file) {
    /** @noinspection PhpIncludeInspection */
    require_once($file);
}

$dir = '/var/www/html';
/** @noinspection PhpIncludeInspection */
require($dir . '/src/XF.php');
XF::start($dir);

$app = XF::setupApp('XF\Pub\App');

spl_autoload_register(function ($class) use ($app) {
    if (strpos($class, 'XFCP_') === false) {
        return null;
    }

    $parts = explode('XFCP_', $class);
    if (count($parts) !== 2) {
        return null;
    }

    $classWithoutPrefix = implode($parts);
    /** @var \DevHelper\XF\Extension $extension */
    $extension = $app->extension();
    $classExtensions = $extension->getClassExtensionsForDevHelper();

    foreach ($classExtensions as $base => $extensions) {
        foreach ($extensions as $extension) {
            if ($extension === $classWithoutPrefix) {
                class_alias($base, $class);
                return true;
            }
        }
    }

    return null;
});

return XF::$autoLoader;
