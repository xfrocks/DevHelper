<?php
class DevHelper_Generator_Code_DataWriter {
	public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		$className = self::getClassName($addOn, $config, $dataClass);
		$tableName = DevHelper_Generator_Db::getTableName($config, $dataClass['name']);
		$tableFields = $dataClass['fields'];
		foreach ($tableFields as &$field) {
			unset($field['name']);
			if (!empty($field['length'])) {
				$field['maxLength'] = $field['length'];
				unset($field['length']);
			}
		}
		$tableFields = DevHelper_Generator_File::varExport($tableFields, 3);
		$primaryKey = DevHelper_Generator_File::varExport($dataClass['primaryKey']);
		$modelClassName = DevHelper_Generator_Code_Model::getClassName($addOn, $config, $dataClass);
		
		$contents = <<<EOF
<?php
class $className extends XenForo_DataWriter {
	protected function _getFields() {
		return array(
			'$tableName' => $tableFields
		);
	}

	protected function _getExistingData(\$data) {
		if (!\$id = \$this->_getExistingPrimaryKey(\$data, '{$dataClass['id_field']}')) {
			return false;
		}

		return array('$tableName' => \$this->_get{$dataClass['camelCase']}Model()->get{$dataClass['camelCase']}ById(\$id));
	}

	protected function _getUpdateCondition(\$tableName) {
		\$conditions = array();
		
		foreach ($primaryKey as \$field) {
			\$conditions[] = \$field . ' = ' . \$this->_db->quote(\$this->getExisting(\$field));
		}
		
		return implode(' AND ', \$conditions);
	}
	
	protected function _get{$dataClass['camelCase']}Model() {
		return \$this->getModelFromCache('$modelClassName');
	}
}
EOF;
		
		return array($className, $contents);
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'DataWriter_' . $dataClass['camelCase']);
	}
}