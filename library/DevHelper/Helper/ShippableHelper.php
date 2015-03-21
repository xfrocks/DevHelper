<?php

class DevHelper_Helper_ShippableHelper
{
    public static function getVersionId($class, $path, $contents)
    {
        if (XenForo_Application::debugMode()) {
            return filemtime($path);
        }

        if (preg_match('#/\*\*.+?Class ' . preg_quote($class, '#') . '.+?@version (?<version>\d+)\s.+?\*/#s', $contents, $matches)) {
            return intval($matches['version']);
        } else {
            return false;
        }
    }

    public static function update($targetClass, $targetPath, $sourceClass, $sourcesContents)
    {
        $targetContents = str_replace($sourceClass, $targetClass, $sourcesContents);

        $php = '<?php';
        $pos = utf8_strpos($targetContents, $php);
        if ($pos !== false) {
            $replacement = sprintf("%s\n\n// updated by %s at %s", $php, __CLASS__, date('c'));
            $targetContents = utf8_substr_replace($targetContents, $replacement, $pos, utf8_strlen($php));
        }

        return DevHelper_Generator_File::filePutContents($targetPath, $targetContents);
    }

    public static function checkForUpdate($path) {
        if (strpos($path, '/ShippableHelper/') !== false) {
            $contents = DevHelper_Generator_File::fileGetContents($path);

            if (preg_match('#class\s(?<class>[^\s]+_ShippableHelper_[^\s]+)\s#', $contents, $matches)) {
                class_exists($matches['class']);
            }
        }
    }
}