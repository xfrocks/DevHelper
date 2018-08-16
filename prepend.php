<?php

// phpcs:ignoreFile

function DevHelper_verifyPhpApacheVersionId()
{
    $expected = '2018081601';
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
    if ($contents === false) {
        return null;
    }
    $contents = preg_replace('#^\s*<\?php#', '', $contents, -1, $count);
    if ($count !== 1) {
        return null;
    }

    $classParts = explode('\\', $class);
    $contents = preg_replace(
        '#(\n' . 'namespace\s+)(' . preg_quote($classParts[0], '#') . ')#',
        '$1DevHelper\\\\$2',
        $contents,
        -1,
        $count
    );
    if ($count !== 1) {
        return null;
    }

    $contents = preg_replace(
        '#(\n' . 'class\s+)(' . preg_quote($classParts[count($classParts) - 1], '#') . ')#',
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