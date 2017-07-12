<?php

class DevHelper_Helper_Js
{
    public static function minify($js)
    {
        require_once(dirname(__FILE__) . '/../Lib/jsmin-php/jsmin.php');
        return JSMin::minify($js);
    }

    public static function processJsFiles(array $jsFiles)
    {
        foreach ($jsFiles as $path) {
            if (strpos($path, 'js') !== 0) {
                // ignore non js files
                continue;
            }

            if (strpos($path, 'js/xenforo/') === 0) {
                // ignore xenforo files
                continue;
            }

            if (!preg_match('#\.min\.js$#', $path)) {
                // ignore non .min.js files
                continue;
            }

            /** @var XenForo_Application $application */
            $application = XenForo_Application::getInstance();
            $fullPath = sprintf(
                '%s/%s/full/%s',
                $application->getRootDir(),
                dirname($path),
                preg_replace('#\.min\.js$#', '.js', basename($path))
            );
            if (!empty($_SERVER['DEVHELPER_ROUTER_PHP'])) {
                $fullPath = DevHelper_Router::locate($fullPath);
            }

            $minPath = sprintf('%s/%s', dirname(dirname($fullPath)), basename($path));

            if (file_exists($fullPath)
                && (!file_exists($path)
                    || (filemtime($fullPath) > filemtime($path)))
            ) {
                $fullContents = file_get_contents($fullPath);

                if (strpos($fullContents, '/* no minify */') === false) {
                    $minified = self::minify($fullContents);
                } else {
                    // the file requested not to be minify... (debugging?)
                    $minified = $fullContents;
                }

                DevHelper_Generator_File::writeFile($minPath, $minified, false, false);
            }
        }
    }
}
