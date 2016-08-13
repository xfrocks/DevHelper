<?php

class DevHelper_Router
{
    public static function route($routerPhpPath)
    {
        $startTime = microtime(true);
        $fileDir = dirname($routerPhpPath);
        $xenforoDir = sprintf('%s/xenforo', $fileDir);
        if (!is_dir($xenforoDir)) {
            die(sprintf('%s not found!', $xenforoDir));
        }

        $xenforoIndex = sprintf('%s/index.php', $xenforoDir);;
        $xenforoAdmin = sprintf('%s/admin.php', $xenforoDir);;
        $xenforoJs = sprintf('%s/js', $xenforoDir);
        $xenforoStylesDefault = sprintf('%s/styles/default', $xenforoDir);
        $target = $xenforoIndex;

        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUriParsed = parse_url($requestUri);
        if (isset($requestUriParsed['path'])) {
            $target = sprintf('%s%s', $xenforoDir, $requestUriParsed['path']);
            if (is_dir($target)) {
                $target = rtrim($target, '/') . '/index.php';
            }
        }

        $_SERVER['DEVHELPER_ROUTER_PHP'] = $routerPhpPath;
        $_SERVER['SCRIPT_FILENAME'] = $target;
        $_SERVER['SCRIPT_NAME'] = preg_replace(sprintf('#^%s#', preg_quote($xenforoDir, '#')), '', $target);
        unset($_SERVER['PHP_SELF']);
        unset($_SERVER['ORIG_SCRIPT_NAME']);

        $targetType = null;
        foreach (array(
                     'js' => $xenforoJs,
                     'styles/default' => $xenforoStylesDefault
                 ) as $_targetType => $_typeXenForoPath) {
            if (strpos($target, $_typeXenForoPath) === 0) {
                $targetType = $_targetType;
                break;
            }
        }
        if (!empty($targetType)) {
            $target = self::locate($targetType, $target);
        }

        if (!in_array($target, array($xenforoIndex, $xenforoAdmin), true)
            && is_file($target)
        ) {
            $extension = strtolower(substr(strrchr($target, '.'), 1));
            if ($extension === 'php') {
                require($target);
                exit;
            }

            $mimeTypes = array(
                'css' => 'text/css',
                'jpg' => 'image/jpeg',
                'js' => 'application/javascript',
                'png' => 'image/png',
            );
            if (isset($mimeTypes[$extension])) {
                header("Content-Type: {$mimeTypes[$extension]}");
            }

            header('Content-Length: ' . filesize($target));
            $fp = fopen($target, 'rb');
            fpassthru($fp);
            fclose($fp);
            exit;
        }

        require($fileDir . '/xenforo/library/XenForo/Autoloader.php');
        require($fileDir . '/library/DevHelper/Autoloader.php');
        DevHelper_Autoloader::getDevHelperInstance()->setupAutoloader($fileDir . '/xenforo/library');

        XenForo_Application::initialize($fileDir . '/xenforo/library', $fileDir . '/xenforo');
        XenForo_Application::set('page_start_time', $startTime);

        $dependencies = $target === $xenforoAdmin
            ? new XenForo_Dependencies_Admin()
            : new XenForo_Dependencies_Public();
        $fc = new XenForo_FrontController($dependencies);

        $fc->run();
    }

    public static function locate($type, $fullPath)
    {
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        $routerPhp = $_SERVER['DEVHELPER_ROUTER_PHP'];
        $routerPhpDir = dirname($routerPhp);
        $xenforoDir = sprintf('%s/xenforo/%s/', $routerPhpDir, $type);

        $shortened = str_replace($xenforoDir, '', $fullPath);
        $parts = explode('/', $shortened);

        while (count($parts) > 0) {
            $candidateParts[] = array_shift($parts);
            $candidateDirPath = sprintf('%s/addons/%s/repo/%s/', $routerPhpDir, implode('/', $candidateParts), $type);
            $candidateDirPath = str_replace('/addons/DevHelper/repo/', '/', $candidateDirPath);

            $candidatePath = $candidateDirPath . $shortened;
            if (file_exists($candidatePath)) {
                return $candidatePath;
            }
        }

        return $fullPath;
    }
}