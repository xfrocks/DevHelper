<?php
class DevHelper_Model_Config extends XenForo_Model {
	public function loadAddOnConfig($addOn) {
		$className = DevHelper_Generator_File::getClassName($addOn['addon_id'], 'DevHelper_Config');
		
		if (class_exists($className)) {
			$config = new $className();
		} else {
			eval("class $className extends DevHelper_Config_Base {}");
			$config = new $className();
			
			$configGeneratorClassName = $className . 'Gen';
			$configGeneratorCallback = array($configGeneratorClassName, 'generateConfig');
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
	
	public function saveAddOnConfig($addOn, DevHelper_Config_Base $config) {
		$className = DevHelper_Generator_File::getClassName($addOn['addon_id'], 'DevHelper_Config');
		DevHelper_Generator_File::write($className, $config->outputSelf());
	}
}