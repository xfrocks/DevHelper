<?php

class DevHelper_Helper_Xfcp
{
    protected static $_lookingForXfcpClasses = false;
    protected static $_foundXfcpClasses = array();

    public static function startLookingForXfcpClasses()
    {
        self::$_lookingForXfcpClasses = true;
        self::$_foundXfcpClasses = array();
    }

    public static function finishLookingForXfcpClasses($addOn, DevHelper_Config_Base $config)
    {
        $prefix = $config->getClassPrefix() . '_';
        $prefixLength = strlen($prefix);

        foreach (self::$_foundXfcpClasses as $clazz => $paths) {
            if (substr($clazz, 0, $prefixLength) !== $prefix) {
                // not prefixed with our add-on ID, ignore it
                continue;
            }

            $clazzWithoutPrefix = substr($clazz, $prefixLength);
            $realClazz = $clazzWithoutPrefix;
            $realPath = DevHelper_Generator_File::getClassPath($realClazz);
            if (!file_exists($realPath)) {
                // the real class could not be found, hmm...
                // try to replace 'Extend_' with 'XenForo_' to support our legacy add-ons
                if (substr($realClazz, 0, 7) !== 'Extend_') {
                    // no hope
                    continue;
                }

                $realClazz = 'XenForo_' . substr($realClazz, 7);
                $realPath = DevHelper_Generator_File::getClassPath($realClazz);
                if (!file_exists($realPath)) {
                    // not found either... bye!
                    continue;
                }
            }

            if (self::generateXfcpClass($clazz, $realClazz, $config)) {
                echo "<span style='color: #ddd'>Generated      XFCP_{$clazz} ({$realClazz})</span>\n";
            }
        }
    }

    public static function generateOurClass($clazz, DevHelper_Config_Base $config)
    {
        $path = DevHelper_Generator_File::getClassPath($clazz);
        $contents = <<<EOF
<?php

class {$clazz} extends XFCP_{$clazz}
{
}
EOF;

        return DevHelper_Generator_File::writeFile($path, $contents, true, false);
    }

    public static function generateXfcpClass($clazz, $realClazz, DevHelper_Config_Base $config)
    {
        $ghostClazz = str_replace($config->getClassPrefix(), $config->getClassPrefix() . '_DevHelper_XFCP', $clazz);
        $ghostPath = DevHelper_Generator_File::getClassPath($ghostClazz);
        if (file_exists($ghostPath)) {
            // ghost file exists, yay!
            return true;
        }

        $ghostContents = "<?php\n\nclass XFCP_{$clazz} extends {$realClazz}\n{\n}\n";

        return DevHelper_Generator_File::filePutContents($ghostPath, $ghostContents);
    }

    public static function parsePhpForXfcpClass($path, $contents)
    {
        if (self::$_lookingForXfcpClasses == false) {
            return;
        }

        $offset = 0;
        $extendsXfcp = '#\sextends\sXFCP_(?<' . 'clazz>[^\s]+)\s#';
        while (true) {
            if (preg_match($extendsXfcp, $contents, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $clazz = $matches['clazz'][0];
                $offset = $matches[0][1] + strlen($matches[0][0]);
                self::$_foundXfcpClasses[$clazz][$path] = true;
            } else {
                break;
            }
        }
    }

}
