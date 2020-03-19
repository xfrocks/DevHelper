<?php

foreach (glob('/var/www/html/src/addons/DevHelper/PHPStan/**/*.php') as $file) {
    /** @noinspection PhpIncludeInspection */
    require_once($file);
}

$dir = '/var/www/html';
$appClass = 'XF\Pub\App';

$srcPath = strval(getenv('DEVHELPER_PHPSTAN_SRC_PATH'));
if (strlen($srcPath) === 0) {
    echo("DEVHELPER_PHPSTAN_SRC_PATH is missing");
    die(1);
}
$srcAutoloadPaths = [
    "${srcPath}/vendor/autoload.php",
    "${srcPath}/_files/dev/phpstan.php",
];
foreach ($srcAutoloadPaths as $srcAutoloadPath) {
    if (file_exists($srcAutoloadPath)) {
        /** @noinspection PhpIncludeInspection */
        require($srcAutoloadPath);
    }
}

/** @noinspection PhpIncludeInspection */
require($dir . '/src/XF.php');
XF::start($dir);

$app = XF::setupApp($appClass);

stream_wrapper_restore('phar');

spl_autoload_register(function ($class) use ($app) {
    if (strpos($class, 'XFCP_') === false) {
        return null;
    }

    $parts = explode('XFCP_', $class);
    if (count($parts) !== 2) {
        return null;
    }

    $classWithoutPrefix = implode($parts);
    static $classExtensions = null;
    if ($classExtensions === null) {
        /** @var \DevHelper\XF\Extension $extension */
        $extension = $app->extension();
        $reflection = new ReflectionClass($extension);
        $property = $reflection->getProperty('classExtensions');
        $property->setAccessible(true);
        $classExtensions = $property->getValue($extension);
    }

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
