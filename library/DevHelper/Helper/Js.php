<?php

class DevHelper_Helper_Js
{
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
                @unlink($minPath);

                exec(
                    sprintf(
                        'nodejs /usr/local/bin/uglifyjs --compress --mangle'
                        . ' --output %2$s --source-map root=full,base="%4$s",url=%5$s.map -- %1$s',
                        escapeshellarg($fullPath),
                        escapeshellarg($minPath),
                        escapeshellarg(dirname($path)),
                        escapeshellarg(dirname($fullPath)),
                        escapeshellarg(basename($minPath))
                    ),
                    $uglifyOutput,
                    $uglifyResult
                );

                if (!file_exists($minPath) || $uglifyResult !== 0) {
                    throw new XenForo_Exception($uglifyResult . var_export($uglifyOutput, true));
                }
            }
        }
    }
}
