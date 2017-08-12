<?php

class DevHelper_Helper_Js
{
    public static function minifyPath($fullPath)
    {
        $fullDirName = dirname($fullPath);
        if (basename($fullDirName) !== 'full') {
            throw new XenForo_Exception($fullPath . ' is not a full.js file');
        }

        $minFileName = preg_replace('#\.js$#', '.min.js', basename($fullPath));
        $minPath = sprintf('%s/%s', dirname($fullDirName), $minFileName);

        if (file_exists($minPath)) {
            if (filemtime($fullPath) < filemtime($minPath)) {
                return $minPath;
            } else {
                unlink($minPath);
            }
        }

        exec(
            sprintf(
                'nodejs /usr/local/bin/uglifyjs --compress --mangle'
                . ' --output %2$s --source-map root=full,base="%3$s",url=%4$s.map -- %1$s',
                escapeshellarg($fullPath),
                escapeshellarg($minPath),
                escapeshellarg(dirname($fullPath)),
                escapeshellarg(basename($minPath))
            ),
            $uglifyOutput,
            $uglifyResult
        );

        if (!file_exists($minPath) || $uglifyResult !== 0) {
            throw new XenForo_Exception($uglifyResult . implode(', ', $uglifyOutput));
        }

        return $minPath;
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

            if (file_exists($fullPath)) {
                static::minifyPath($fullPath);
            }
        }
    }
}
