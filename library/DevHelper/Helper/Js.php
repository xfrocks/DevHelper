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

        XenForo_Helper_File::createDirectory(dirname($minPath));

        $command = sprintf(
            'uglifyjs --compress --mangle'
            . ' --output %2$s --source-map %3$s -- %1$s 2>&1',
            escapeshellarg($fullPath),
            escapeshellarg($minPath),
            escapeshellarg(sprintf(
                'root=full,base="%1$s",url=%2$s.map',
                dirname($fullPath),
                basename($minPath)
            ))
        );

        exec($command, $uglifyOutput, $uglifyResult);

        if (!file_exists($minPath) || $uglifyResult !== 0) {
            echo(sprintf("%s: %s\n%s -> %d<br />\n", __METHOD__, $fullPath, $command, $uglifyResult));
            var_export($uglifyOutput);
            exit($uglifyResult);
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
