<?php

class DevHelper_Generator_Code_Listener
{
    public static function generateFileHealthCheck(array $addOn, DevHelper_Config_Base $config)
    {
        $existingContents = self::_getClassContents($addOn, $config);
        $existingMethods = DevHelper_Helper_Php::extractMethods($existingContents);
        if (in_array('file_health_check', $existingMethods, true)) {
            return false;
        }

        $fileSumsClass = DevHelper_Generator_File::getClassName($addOn['addon_id'], 'FileSums', $config);
        $methodCode = <<<EOF
public static function file_health_check(XenForo_ControllerAdmin_Abstract \$controller, array &\$hashes)
{
    \$hashes += {$fileSumsClass}::getHashes();
}
EOF;

        $contents = DevHelper_Helper_Php::appendMethod($existingContents, $methodCode);
        if (self::_write($addOn, $config, $contents))
        {
            /** @var XenForo_DataWriter_CodeEventListener $dw */
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_CodeEventListener');
            $dw->setImportMode(true);
            $dw->bulkSet(array(
                'event_id' => 'file_health_check',
                'callback_class' => self::getClassName($addOn, $config),
                'callback_method' => 'file_health_check',
                'addon_id' => $addOn['addon_id'],
            ));
            return $dw->save();
        }

        return false;
    }

    public static function generateLoadClass($realClazz, array $addOn, DevHelper_Config_Base $config)
    {
        $method = sprintf('load_class_%s', $realClazz);

        $existingContents = self::_getClassContents($addOn, $config);
        $existingMethods = DevHelper_Helper_Php::extractMethods($existingContents);
        if (in_array($method, $existingMethods, true)) {
            return false;
        }

        $ourClazz = sprintf('%s_%s', $config->getClassPrefix(), $realClazz);
        $methodCode = <<<EOF
public static function {$method}(\$class, array &\$extend)
{
    if (\$class === '{$realClazz}') {
        \$extend[] = '{$ourClazz}';
    }
}
EOF;

        $contents = DevHelper_Helper_Php::appendMethod($existingContents, $methodCode);
        if (!self::_write($addOn, $config, $contents)) {
            return false;
        }

        if (!DevHelper_Helper_Xfcp::generateXfcpClass($ourClazz, $realClazz, $config)) {
            return false;
        }

        if (!DevHelper_Helper_Xfcp::generateOurClass($ourClazz, $config)) {
            return false;
        }

        return $method;
    }

    public static function getClassName(array $addOn, DevHelper_Config_Base $config)
    {
        return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Listener', $config);
    }

    protected static function _getClassContents(array $addOn, DevHelper_Config_Base $config)
    {
        $className = self::getClassName($addOn, $config);
        $path = DevHelper_Generator_File::getClassPath($className);

        if (file_exists($path)) {
            $contents = file_get_contents($path);
        }

        if (empty($contents)) {
            $contents = <<<EOF
<?php

class {$className}
{}
EOF;
        }

        return $contents;
    }

    protected static function _write(array $addOn, DevHelper_Config_Base $config, $contents) {
        $className = self::getClassName($addOn, $config);
        $path = DevHelper_Generator_File::getClassPath($className);

        return DevHelper_Generator_File::writeFile($path, $contents, true, false) === true;
    }
}
