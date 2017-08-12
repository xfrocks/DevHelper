<?php

class DevHelper_Helper_File
{
    const FIND_XML_IF_PATHS_COUNT_LESS_THAN = 3;

    public static function findXml(array $paths)
    {
        if (count($paths) > static::FIND_XML_IF_PATHS_COUNT_LESS_THAN) {
            return '';
        }

        $subPaths = array();
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $contentPaths = glob(sprintf('%s/*', rtrim($path, '/')));
            foreach ($contentPaths as $contentPath) {
                if (is_dir($contentPath)) {
                    $subPaths[] = $contentPath . '/';
                } else {
                    $ext = XenForo_Helper_File::getFileExtension($contentPath);
                    if ($ext === 'xml') {
                        return $contentPath;
                    }
                }
            }
        }

        return static::findXml($subPaths);
    }
}
