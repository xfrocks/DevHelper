<?php

foreach (glob('/var/www/html/src/addons/DevHelper/PHPStan/**/*.php') as $file) {
    /** @noinspection PhpIncludeInspection */
    require_once($file);
}

$dir = '/var/www/html';
$appClass = 'XF\Pub\App';

$srcPath = getenv('DEVHELPER_PHPSTAN_SRC_PATH');
if (empty($srcPath)) {
    echo("DEVHELPER_PHPSTAN_SRC_PATH is missing");
    die(1);
}
$autogenPath = $srcPath . '/DevHelper/autogen.json';
if (file_exists($autogenPath)) {
    $autogenJson = file_get_contents($autogenPath);
    $autogen = json_decode($autogenJson, true);
    if (is_array($autogen) && !empty($autogen['phpstan'])) {
        $autogenPhpstan = $autogen['phpstan'];
        if (isset($autogenPhpstan['autoload'])) {
            /** @noinspection PhpIncludeInspection */
            require($autogenPhpstan['autoload']);
        }

        if (isset($autogenPhpstan['appClass'])) {
            $appClass = $autogenPhpstan['appClass'];
        }
    }
}

/** @noinspection PhpIncludeInspection */
require($dir . '/src/XF.php');
XF::start($dir);

$app = XF::setupApp($appClass);

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
