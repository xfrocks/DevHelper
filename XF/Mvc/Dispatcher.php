<?php

namespace DevHelper\XF\Mvc;

class Dispatcher extends XFCP_Dispatcher
{
    /**
     * @param mixed $routePath
     * @return \XF\Mvc\RouteMatch
     */
    public function route($routePath)
    {
        $prefix = 'styles/default/';
        if (substr($routePath, 0, strlen($prefix)) === $prefix) {
            $withoutPrefix = substr($routePath, strlen($prefix));
            $addOns = \XF::app()->container('addon.cache');
            $foundAddOnId = '';

            foreach ($addOns as $addOnId => $versionId) {
                if (substr($withoutPrefix, 0, strlen($addOnId)) !== $addOnId) {
                    continue;
                }

                if (strlen($addOnId) > strlen($foundAddOnId)) {
                    $foundAddOnId = $addOnId;
                }
            }

            if ($foundAddOnId !== '') {
                $assetPath = "/var/www/html/src/addons/{$foundAddOnId}/_files/$routePath";
                if (file_exists($assetPath)) {
                    $mimeType = mime_content_type($assetPath);
                    $contents = file_get_contents($assetPath);

                    header("X-Add-On-Id: {$foundAddOnId}");
                    header("X-Asset-Path: {$assetPath}");
                    if ($mimeType !== false) {
                        header("Content-Type: {$mimeType}");
                    }
                    die($contents);
                }
            }

            header('HTTP/1.0 404 Not Found');
            echo("Unable to resolve add-on file for `{$routePath}`<br/>\n");
            echo("Please make sure that the add-on is enabled and its files are put into `_files/styles/default/addOnId/` directory.<br/>\n");
            exit;
        }

        return parent::route($routePath);
    }
}

// phpcs:disable
if (false) {
    class XFCP_Dispatcher extends \XF\Mvc\Dispatcher
    {
    }
}
