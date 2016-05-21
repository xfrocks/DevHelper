<?php

class DevHelper_Helper_ShippableHelper
{
    public static function getVersionId($class, $path, $contents)
    {
        if (XenForo_Application::debugMode()) {
            return filemtime($path);
        }

        if (preg_match('#/\*\*.+?Class '
            . preg_quote($class, '#')
            . '.+?@version (?<version>\d+)\s.+?\*/#s',
            $contents, $matches)) {
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

        $classPrefix = substr($targetClass, 0, strpos($targetClass, 'ShippableHelper_'));
        $offset = 0;
        while (true) {
            if (!preg_match('#DevHelper_Helper_ShippableHelper_[a-zA-Z_]+#',
                $targetContents, $matches, PREG_OFFSET_CAPTURE, $offset)
            ) {
                break;
            }

            $siblingSourceClass = $matches[0][0];
            $offset = $matches[0][1];
            $siblingTargetClass = str_replace('DevHelper_Helper_', $classPrefix, $siblingSourceClass);
            $targetContents = substr_replace($targetContents, $siblingTargetClass, $offset,
                strlen($siblingSourceClass));

            class_exists($siblingTargetClass);

            $offset += 1;
        }

        $targetContents = preg_replace('#\* @version \d+\s*\n#', '$0 * @see ' . $sourceClass . "\n",
            $targetContents, -1, $count);

        return DevHelper_Generator_File::filePutContents($targetPath, $targetContents);
    }

    public static function checkForUpdate($path)
    {
        if (strpos($path, '/ShippableHelper/') !== false) {
            $contents = DevHelper_Generator_File::fileGetContents($path);

            if (preg_match('#class\s(?<class>[^\s]+_ShippableHelper_[^\s]+)\s#', $contents, $matches)) {
                class_exists($matches['class']);
            }
        }
    }
}