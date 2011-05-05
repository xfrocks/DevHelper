<?php
class DevHelper_Generator_Db {
	public static function createTable(DevHelper_Config_Base $config, array $dataClass) {
		$tableName = self::getTableName($config, $dataClass['name']);
		
		$fields = array();
		foreach ($dataClass['fields'] as $field) {
			$fields[] = "`$field[name]` " . self::_getFieldDefinition($field);
		}
		$fields = implode("\n\t,", $fields);
		
		if (!empty($dataClass['primaryKey'])) {
			$primaryKey = ", PRIMARY KEY (`" . implode('`,`', $dataClass['primaryKey']) . "`)";
		} else {
			$primaryKey = '';
		}
		
		$indeces = array();
		foreach ($dataClass['indeces'] as $index) {
			$indeces[] = ($index['type'] != 'NORMAL' ? $index['type'] : '') . " INDEX `$index[name]` (`" . implode('`,`', $index['fields']) . "`)"; 
		}
		$indeces = implode("\n\t,", $indeces);
		if (!empty($indeces)) $indeces = ',' . $indeces;
		
		$sql = <<<EOF
CREATE TABLE IF NOT EXISTS `$tableName` (
	$fields
	$primaryKey
	$indeces
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
EOF;

		return $sql;
	}
	
	public static function showColumns(DevHelper_Config_Base $config, $table, array $field) {
		$fieldName = $field['name'];
		
		return "SHOW COLUMNS FROM `$table` LIKE '$fieldName'";
	}
	
	public static function alterTableAddColumn(DevHelper_Config_Base $config, $table, array $field) {
		$fieldName = $field['name'];
		$fieldDefinition = self::_getFieldDefinition($field);
		
		return "ALTER TABLE `$table` ADD COLUMN `$fieldName` $fieldDefinition";
	}
	
	public static function getTableName(DevHelper_Config_Base $config, $name) {
		return 'xf_' . self::getFieldName($config, $name);
	}
	
	public static function getFieldName(DevHelper_Config_Base $config, $name) {
		if (strpos($name, '_') !== false) {
			return strtolower($name);
		} else {
			$configClassName = get_class($config);
			$parts = explode('_', $configClassName);
			$prefix = array_shift($parts);
			
			return strtolower($prefix . '_' . $name);
		}
	}
	
	public static function getIntFields(array $fields) {
		$intFields = array();
		$intTypes = array(
			XenForo_DataWriter::TYPE_INT,
			XenForo_DataWriter::TYPE_UINT,
			XenForo_DataWriter::TYPE_UINT_FORCED,
		);
		
		foreach ($fields as $field) {
			if (in_array($field['type'], $intTypes)) {
				$intFields[] = $field['name'];
			}
		}
		
		return $intFields;
	}
	
	public static function getDataTypes() {
		return $types = array(
			XenForo_DataWriter::TYPE_BOOLEAN,
			XenForo_DataWriter::TYPE_STRING,
			XenForo_DataWriter::TYPE_BINARY,
			XenForo_DataWriter::TYPE_INT,
			XenForo_DataWriter::TYPE_UINT,
			XenForo_DataWriter::TYPE_UINT_FORCED,
			XenForo_DataWriter::TYPE_FLOAT,
			XenForo_DataWriter::TYPE_SERIALIZED,
		);
	}
	
	protected static function _getFieldDefinition($field) {
		switch ($field['type']) {
			case XenForo_DataWriter::TYPE_BOOLEAN:
				$dbType = 'TINYINT(4) UNSIGNED';
				break;
			case XenForo_DataWriter::TYPE_STRING:
				if (empty($field['length']) OR $field['length'] > 255) {
					$dbType = 'TEXT';
				} else {
					if (!empty($field['allowedValues'])) {
						// ENUM 
						$dbType = 'ENUM (\'' . implode('\',\'', $field['allowedValues']) . '\')';
					} else {
						$dbType = 'VARCHAR(' . $field['length'] . ')';
					}
				}
				break;
			case XenForo_DataWriter::TYPE_BINARY:
				if ($field['length'] > 255) {
					$dbType = 'BLOB';
				} else {
					$dbType = 'VARBINARY(' . $field['length'] . ')';
				}
				break;
			case XenForo_DataWriter::TYPE_INT:
				$dbType = 'INT(11)';
				break;
			case XenForo_DataWriter::TYPE_UINT:
			case XenForo_DataWriter::TYPE_UINT_FORCED:
				$dbType = 'INT(10) UNSIGNED';
				break;
			case XenForo_DataWriter::TYPE_FLOAT:
				$dbType = 'FLOAT';
				break;
			case XenForo_DataWriter::TYPE_SERIALIZED:
				$dbType = 'MEDIUMBLOB';
				break;
		}
		
		return $dbType 
			. (!empty($field['required']) ? ' NOT NULL': '') 
			. (isset($field['default']) ? " DEFAULT '{$field['default']}'" : '')
			. (!empty($field['autoIncrement']) ? ' AUTO_INCREMENT' : '');
	}
}