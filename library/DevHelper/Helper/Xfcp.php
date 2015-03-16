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

    public static function finishLookingForXfcpClasses($addOn)
    {
        $prefix = DevHelper_Generator_File::getClassName($addOn['addon_id']) . '_';
        $prefixLength = strlen($prefix);

        foreach (self::$_foundXfcpClasses as $clazz => $paths) {
            if (substr($clazz, 0, $prefixLength) !== $prefix) {
                // not prefixed with our add-on ID, ignore it
                continue;
            }

            $realClazz = substr($clazz, $prefixLength);
            if (!class_exists($realClazz)) {
                // the real class could not be found, hmm...
                continue;
            }

            $ghostClazz = $prefix . 'DevHelper_XFCP_' . $clazz;
            $ghostPath = DevHelper_Generator_File::getClassPath($ghostClazz);
            if (file_exists($ghostPath)) {
                // ghost file exists, yay!
                continue;
            }

            $ghostContents = "<?php\n\nclass XFCP_{$clazz} extends {$realClazz}\n{\n}\n";

            DevHelper_Generator_File::filePutContents($ghostPath, $ghostContents);
            echo "<span style='color: #ddd'>Generated      {$ghostClazz}</span>\n";
        }
    }

    public static function parsePhpForXfcpClass($path, $contents)
    {
        if (self::$_lookingForXfcpClasses == false)
        {
            return false;
        }

        $offset = 0;
        $extendsXfcp = '#\sextends\sXFCP_(?<clazz>[^\s]+)\s#';
        while (true)
        {
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
