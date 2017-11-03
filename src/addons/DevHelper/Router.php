<?php

namespace DevHelper;

class Router
{
    const PATH_SLASH_SRC_ADDONS_SLASH = '/src/addons/';

    public static function route($routerPhpPath)
    {
        $_SERVER['DEVHELPER_ROUTER_PHP'] = $routerPhpPath;
        $fileDir = dirname($routerPhpPath);
        $xenforoDir = sprintf('%s/xenforo', $fileDir);

        if (defined('DEVHELPER_CMD_PHP')) {
            $target = sprintf('%s/cmd.php', $xenforoDir);
        } else {
            $requestUri = $_SERVER['REQUEST_URI'];
            $requestUriParsed = parse_url($requestUri);
            $target = sprintf('%s/index.php', $xenforoDir);
            if (isset($requestUriParsed['path'])) {
                $target = sprintf('%s%s', $xenforoDir, $requestUriParsed['path']);
                if (is_dir($target)) {
                    $target = rtrim($target, '/') . '/index.php';
                }
            }
        }

        $targetOriginal = $target;
        $target = self::locate($target);
        if (!is_file($target)) {
            $target = $xenforoDir . '/index.php';
            $targetOriginal = $target;
        }

        $_SERVER['SCRIPT_FILENAME'] = $targetOriginal;
        $_SERVER['SCRIPT_NAME'] = preg_replace(sprintf('#^%s#', preg_quote($xenforoDir, '#')), '', $targetOriginal);
        unset($_SERVER['PHP_SELF']);
        unset($_SERVER['ORIG_SCRIPT_NAME']);

        $extension = strtolower(substr(strrchr($target, '.'), 1));
        if ($extension === 'php') {
            self::setupAutoloader();

            /** @noinspection PhpIncludeInspection */
            require($target);
            exit;
        }

        $contentType = null;
        switch ($extension) {
            case 'css':
                $contentType = 'text/css';
                break;
            case 'js':
                $contentType = 'application/javascript';
                break;
        }
        if ($contentType === null && function_exists('mime_content_type')) {
            $contentType = mime_content_type($target);
        }
        if (!empty($contentType)) {
            header('Content-Type: ' . $contentType);
        }


        header('Content-Length: ' . filesize($target));
        $fp = fopen($target, 'rb');
        fpassthru($fp);
        fclose($fp);
        exit;
    }

    public static function setupAutoloader()
    {
        require(__DIR__ . '/Autoloader.php');
        Autoloader::getInstance()->setupAutoloader();
    }

    public static function locateCached($fullPath)
    {
        $key = basename($fullPath) . md5($fullPath);
        $cached = apcu_fetch($key);
        $located = null;
        $success = false;
        if (is_array($cached)
            && $cached['fullPath'] === $fullPath) {
            $located = $cached['located'];
        }

        if (empty($located)) {
            $located = static::locate($fullPath, $success);
        }

        if ($success) {
            $cacheEntry = array(
                'fullPath' => $fullPath,
                'located' => $located,
            );

            apcu_store($key, $cacheEntry);
        }

        return $located;
    }

    public static function locate($fullPath, &$success = null)
    {
        if (file_exists($fullPath)) {
            $success = true;
            return $fullPath;
        }

        list($xenforoDir, $addOnPaths) = static::getLocatePaths();
        $shortened = preg_replace(
            '#^' . preg_quote($xenforoDir, '#') . '#',
            '',
            $fullPath
        );

        $srcAddOnsPath = null;
        if (strpos($shortened, static::PATH_SLASH_SRC_ADDONS_SLASH) === 0) {
            $srcAddOnsPath = substr($shortened, strlen(static::PATH_SLASH_SRC_ADDONS_SLASH));
        }

        foreach ($addOnPaths as $addOnPathSuffix => $addOnPath) {
            $candidatePaths = [];

            if ($srcAddOnsPath !== null) {
                if (strpos($srcAddOnsPath, $addOnPathSuffix) === 0) {
                    $candidatePaths[] = $addOnPath . '/' . substr($srcAddOnsPath, strlen($addOnPathSuffix));
                } elseif (strpos($addOnPathSuffix, $srcAddOnsPath) === 0) {
                    $parentPathSuffix = dirname($addOnPathSuffix);
                    $parentPath = dirname($addOnPath);
                    if (strpos($srcAddOnsPath, $parentPathSuffix) === 0) {
                        $candidatePaths[] = $parentPath;
                    }
                }
            } else {
                // for non-PHP files, we support 2 types of add-on files structure
                $fullSuffix = '/src/addons/' . $addOnPathSuffix;
                if (substr($addOnPath, -strlen($fullSuffix)) === $fullSuffix) {
                    // `full` type has js files in addons/AddOnId/js/
                    // and `addon.json` is at addons/AddOnId/src/addons/AddOnId/addon.json
                    $candidatePaths[] = substr($addOnPath, 0, -strlen($fullSuffix)) . $shortened;
                } else {
                    // `repo` type  has js files in addons/AddOnId/_files/js/
                    // and `addon.json` is at addons/AddOnId/addon.json
                    $candidatePaths[] = $addOnPath . '/_files' . $shortened;
                }
            }

            foreach ($candidatePaths as $candidatePath) {
                if (file_exists($candidatePath)) {
                    $success = true;
                    return $candidatePath;
                }
            }
        }

        if (!$success && $srcAddOnsPath !== null) {
            $candidatePath = '/var/www/html/addons/' . $srcAddOnsPath;
            if (file_exists($candidatePath)) {
                $success = true;
                return $candidatePath;
            }
        }

        return $fullPath;
    }

    public static function getLocatePaths()
    {
        static $xenforoDir = null;
        static $addOnPaths = null;
        static $addonsDir = null;

        if ($addOnPaths === null) {
            $routerPhp = $_SERVER['DEVHELPER_ROUTER_PHP'];
            $routerPhpDir = dirname($routerPhp);
            $xenforoDir = sprintf('%s/xenforo', $routerPhpDir);
            $addonsDir = sprintf('%s/addons', $routerPhpDir);
            $addOnPaths = array();

            $txtPath = sprintf('%s/internal_data/addons2.txt', $xenforoDir);
            if (!file_exists($txtPath)) {
                exec('/usr/local/bin/find-addons2.sh');
            }
            $lines = file($txtPath);
            $prefix = 'addons';
            $prefixLength = strlen($prefix);

            foreach ($lines as $line) {
                $line = trim($line);
                if (strlen($line) === 0) {
                    continue;
                }

                if (substr($line, 0, $prefixLength) !== $prefix) {
                    continue;
                }

                $lineWithoutAddOnJson = preg_replace('#/addon.json$#', '', $line, -1, $count);
                if ($count !== 1) {
                    continue;
                }

                $addOnPath = sprintf('%s/%s', $routerPhpDir, $lineWithoutAddOnJson);
                $addOnPathSuffix = trim(preg_replace('#^.+addons#', '', $addOnPath), '/');
                $addOnPaths[$addOnPathSuffix] = $addOnPath;
            }
        }

        return array($xenforoDir, $addOnPaths, $addonsDir);
    }

    public static function locateReset()
    {
        apcu_clear_cache();
        exec('/usr/local/bin/find-addons2.sh');
    }
}
