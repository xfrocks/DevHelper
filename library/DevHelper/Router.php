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

        $target = self::locate($target);
        if (!is_file($target)) {
            $target = $xenforoDir . '/index.php';
        }

        $_SERVER['SCRIPT_FILENAME'] = $target;
        $_SERVER['SCRIPT_NAME'] = preg_replace(sprintf('#^%s#', preg_quote($xenforoDir, '#')), '', $target);
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

    public static function locate($fullPath)
    {
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        $routerPhp = $_SERVER['DEVHELPER_ROUTER_PHP'];
        $routerPhpDir = dirname($routerPhp);
        $xenforoDir = sprintf('%s/xenforo', $routerPhpDir);
        $shortened = preg_replace(
            '#^' . preg_quote($xenforoDir, '#') . '#',
            '',
            $fullPath
        );
        $addOnPaths = file(sprintf('%s/internal_data/addons.txt', $xenforoDir));

        foreach ($addOnPaths as $addOnPath) {
            $candidatePath = sprintf('%s/%s%s', $routerPhpDir, trim($addOnPath), $shortened);
            $candidatePath = str_replace('/addons/DevHelper/', '/', $candidatePath);
            if (file_exists($candidatePath)) {
                return $candidatePath;
            }
        }

        return $fullPath;
    }
}
