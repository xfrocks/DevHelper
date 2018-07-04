<?php

// phpcs:ignoreFile

function DevHelper_verifyPhpApacheVersionId()
{
    $expected = '2018070302';
    $actual = getenv('DEVHELPER_PHP_APACHE_VERSION_ID');
    if ($actual === $expected) {
        return;
    }

    printf("Please rebuild container, expected v%s, actual v%s\n", $expected, $actual);
    exit(1);
}

DevHelper_verifyPhpApacheVersionId();

/** @noinspection PhpUndefinedClassInspection */
/**
 * @param string $class
 * @param \Composer\Autoload\ClassLoader $al
 * @return bool|null
 */
function DevHelper_patchClass($class, $al)
{
    $file = $al->findFile($class);
    if (!$file) {
        return null;
    }

    $contents = file_get_contents($file);
    $contents = preg_replace('#^\s*<\?php#', '', $contents, -1, $count);
    if ($count !== 1) {
        return null;
    }

    $classParts = explode('\\', $class);
    $classFirst = reset($classParts);
    $contents = preg_replace(
        '#(\n' . 'namespace\s+)(' . preg_quote($classFirst, '#') . ')#',
        '$1DevHelper\\\\$2',
        $contents,
        -1,
        $count
    );
    if ($count !== 1) {
        return null;
    }

    $classLast = end($classParts);
    $contents = preg_replace(
        '#(\n' . 'class\s+)(' . preg_quote($classLast, '#') . ')#',
        '$1DevHelperCP_$2',
        $contents,
        -1,
        $count
    );
    if ($count !== 1) {
        return null;
    }

    $ourClass = 'DevHelper\\' . $class;
    $ourFile = $al->findFile($ourClass);
    if (!$ourFile) {
        die($ourClass . ' could not be find');
    }

    eval($contents);

    /** @noinspection PhpIncludeInspection */
    require($ourFile);

    class_alias($ourClass, $class, false);

    return true;
}

function DevHelper_autoload()
{
    $targetClasses = [
        'XF\Extension',
        'XF\Util\File',
    ];

    $unregistered = false;

    spl_autoload_register(function ($class) use ($targetClasses, &$unregistered) {
        if (!class_exists('XF')) {
            return null;
        }

        $al = \XF::$autoLoader;
        if (empty($al)) {
            return null;
        }

        if (!$unregistered) {
            $al->unregister();
            $unregistered = true;
        }

        if (in_array($class, $targetClasses, true)) {
            if (DevHelper_patchClass($class, $al) === true) {
                return true;
            }
        }

        return $al->loadClass($class);
    }, true, true);
}

DevHelper_autoload();