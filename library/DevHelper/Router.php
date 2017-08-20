<?php

class DevHelper_Router
{
    public static function route($routerPhpPath)
    {
        $_SERVER['DEVHELPER_ROUTER_PHP'] = $routerPhpPath;
        $fileDir = dirname($routerPhpPath);
        $xenforoDir = sprintf('%s/xenforo', $fileDir);

        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUriParsed = parse_url($requestUri);
        $target = sprintf('%s/index.php', $xenforoDir);
        if (isset($requestUriParsed['path'])) {
            $target = sprintf('%s%s', $xenforoDir, $requestUriParsed['path']);
            if (is_dir($target)) {
                $target = rtrim($target, '/') . '/index.php';
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
            self::setupAutoloader($fileDir);

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

    public static function setupAutoloader($fileDir)
    {
        /** @noinspection PhpIncludeInspection */
        require($fileDir . '/library/DevHelper/Autoloader.php');
        DevHelper_Autoloader::getDevHelperInstance()->setupAutoloader($fileDir . '/xenforo/library');
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

        foreach ($addOnPaths as $addOnPath) {
            $candidatePath = $addOnPath . $shortened;
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

        if ($addOnPaths === null) {
            $routerPhp = $_SERVER['DEVHELPER_ROUTER_PHP'];
            $routerPhpDir = dirname($routerPhp);
            $xenforoDir = sprintf('%s/xenforo', $routerPhpDir);
            $addOnPaths = array($routerPhpDir);

            $txtPath = sprintf('%s/internal_data/addons.txt', $xenforoDir);
            $lines = array();
            if (file_exists($txtPath)) {
                $lines = file($txtPath);
            }

            foreach ($lines as $line) {
                $line = trim($line);
                if (strlen($line) === 0) {
                    continue;
                }

                $addOnPaths[] = sprintf('%s/%s', $routerPhpDir, $line);
            }
        }

        return array($xenforoDir, $addOnPaths);
    }
}
