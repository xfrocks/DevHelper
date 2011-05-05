<?php
class DevHelper_Generator_Code_Installer {
	public static function generate(array $addOn, DevHelper_Config_Base $config) {
		$className = self::getClassName($addOn, $config);
		
		$tables = array();
		$dataClasses = $config->getDataClasses();
		foreach ($dataClasses as $dataClass) {
			$table = array();
			$table['createQuery'] = DevHelper_Generator_Db::createTable($config, $dataClass);
			$table['dropQuery'] = false;
			
			$tables[$dataClass['name']] = $table;
		} 
		$tables = DevHelper_Generator_File::varExport($tables);
		
		$patches = array();
		$dataPatches = $config->getDataPatches();
		foreach ($dataPatches as $table => $tablePatches) {
			foreach ($tablePatches as $dataPatch) {
				$patch = array();
				$patch['table'] = $table;
				$patch['field'] = $dataPatch['name'];
				$patch['showColumnsQuery'] = DevHelper_Generator_Db::showColumns($config, $table, $dataPatch);
				$patch['alterTableAddColumnQuery'] = DevHelper_Generator_Db::alterTableAddColumn($config, $table, $dataPatch);
				$patch['alterTableDropColumnQuery'] = false;
				
				$patches[] = $patch;
			}
		}
		$patches = DevHelper_Generator_File::varExport($patches);
		
		$contents = <<<EOF
<?php
class $className {
	protected static \$_tables = $tables;
	protected static \$_patches = $patches;

	public static function install() {
		\$db = XenForo_Application::get('db');

		foreach (self::\$_tables as \$table) {
			\$db->query(\$table['createQuery']);
		}
		
		foreach (self::\$_patches as \$patch) {
			\$existed = \$db->fetchOne(\$patch['showColumnsQuery']);
			if (empty(\$existed)) {
				\$db->query(\$patch['alterTableAddColumnQuery']);
			}
		}
	}
	
	public static function uninstall() {
		// TODO
	}
}
EOF;

		return array($className, $contents);
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Installer');
	}
}