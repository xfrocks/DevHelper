<?php

class DevHelper_Model_Config extends XenForo_Model
{
    /**
     * @param $addOn
     * @return DevHelper_Config_Base
     */
    public function loadAddOnConfig($addOn)
    {
        $className = DevHelper_Generator_File::getClassName($addOn['addon_id'], 'DevHelper_Config');

        if (class_exists($className)) {
            /** @var DevHelper_Config_Base $config */
            $config = new $className();
        } else {
            eval("class $className extends DevHelper_Config_Base {}");

            /** @var DevHelper_Config_Base $config */
            $config = new $className();

            $configGeneratorClassName = $className . 'Gen';
            $configGeneratorCallback = array(
                $configGeneratorClassName,
                'generateConfig'
            );
            if (class_exists($configGeneratorClassName)) {
                call_user_func($configGeneratorCallback, $config);
            }

            $created = true;
        }

        $upgraded = $config->upgradeConfig();

        if (!empty($created) OR !empty($upgraded)) {
            $this->saveAddOnConfig($addOn, $config);
        }

        return $config;
    }

    public function saveAddOnConfig($addOn, DevHelper_Config_Base $config)
    {
        $className = DevHelper_Generator_File::getClassName($addOn['addon_id'], 'DevHelper_Config');
        DevHelper_Generator_File::writeClass($className, $config->outputSelf());
    }

}
