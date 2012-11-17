<?php
class DevHelper_Generator_Code_DataWriter extends DevHelper_Generator_Code_Common {
	
	protected $_addOn = null;
	protected $_config = null;
	protected $_dataClass = null;
	
	protected function __construct(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		$this->_addOn = $addOn;
		$this->_config = $config;
		$this->_dataClass = $dataClass;
	}
	
	protected function _generate() {
		$className = $this->_getClassName();
		$tableName = DevHelper_Generator_Db::getTableName($this->_config, $this->_dataClass['name']);
		$tableFields = $this->_dataClass['fields'];
		foreach ($tableFields as &$field) {
			unset($field['name']);
			if (!empty($field['length'])) {
				$field['maxLength'] = $field['length'];
				unset($field['length']);
			}
		}
		$tableFields = DevHelper_Generator_File::varExport($tableFields, 1);
		$primaryKey = DevHelper_Generator_File::varExport($this->_dataClass['primaryKey']);
		$modelClassName = DevHelper_Generator_Code_Model::getClassName($this->_addOn, $this->_config, $this->_dataClass);
		
		$this->_setClassName($className);
		$this->_setBaseClass('XenForo_DataWriter');
		
		$this->_addMethod('_getFields', 'protected', array(), "

return array(
	'{$tableName}' => {$tableFields}
);

		");
		
		$this->_addMethod('_getExistingData', 'protected', array('$data'), "

if (!\$id = \$this->_getExistingPrimaryKey(\$data, '{$this->_dataClass['id_field']}')) {
	return false;
}

return array('$tableName' => \$this->_get{$this->_dataClass['camelCase']}Model()->get{$this->_dataClass['camelCase']}ById(\$id));

		");
		
		$this->_addMethod('_getUpdateCondition', 'protected', array('$tableName'), "

\$conditions = array();

foreach ($primaryKey as \$field) {
	\$conditions[] = \$field . ' = ' . \$this->_db->quote(\$this->getExisting(\$field));
}

return implode(' AND ', \$conditions);

		");
		
		$this->_addMethod("_get{$this->_dataClass['camelCase']}Model", 'protected', array(), "

return \$this->getModelFromCache('$modelClassName');

		");
		
		$this->_generateImageCode();
		$this->_generatePhrasesCode();
		
		return parent::_generate();
	}
	
	protected function _generateImageCode() {
		$imageField = DevHelper_Generator_Db::getImageField($this->_dataClass['fields']);
		if ($imageField === false) {
			// no image field...
			return false;
		}
		
		$modelClassName = DevHelper_Generator_Code_Model::getClassName($this->_addOn, $this->_config, $this->_dataClass);
		
		$this->_addConstant('DATA_IMAGE_PREPARED', '\'imagePrepared\'');
		$this->_addConstant('IMAGE_SIZE_ORIGINAL', '-1');
		$this->_addProperty('$imageQuality', 'public static $imageQuality = 85');
		
		$this->_addMethod('setImage', 'public', array('$upload' => 'XenForo_Upload $upload'), "
		
if (!\$upload->isValid()) {
	throw new XenForo_Exception(\$upload->getErrors(), true);
}

if (!\$upload->isImage()) {
	throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
};

\$imageType = \$upload->getImageInfoField('type');
if (!in_array(\$imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
	throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
}

\$this->setExtraData(self::DATA_IMAGE_PREPARED, \$this->_prepareImage(\$upload));
\$this->set('{$imageField}', XenForo_Application::\$time);
		
		");
		
		$this->_addMethod('_prepareImage', 'protected', array('$upload' => 'XenForo_Upload $upload'), "
		
\$outputFiles = array();
\$fileName = \$upload->getTempFile();
\$imageType = \$upload->getImageInfoField('type');
\$outputType = \$imageType;
\$width = \$upload->getImageInfoField('width');
\$height = \$upload->getImageInfoField('height');

\$imageSizes = \$this->getImageSizes();
reset(\$imageSizes);

while (list(\$sizeCode, \$maxDimensions) = each(\$imageSizes)) {
	\$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xfa');
	
	if (\$maxDimensions == self::IMAGE_SIZE_ORIGINAL) {
		copy(\$fileName, \$newTempFile);
	} else {
		\$image = XenForo_Image_Abstract::createFromFile(\$fileName, \$imageType);
		if (!\$image) {
			continue;
		}

		\$image->thumbnail(\$maxDimensions, \$maxDimensions);

		\$image->output(\$outputType, \$newTempFile, self::\$imageQuality);
		unset(\$image);
	}

	\$outputFiles[\$sizeCode] = \$newTempFile;
}

if (count(\$outputFiles) != count(\$imageSizes)) {
	foreach (\$outputFiles AS \$tempFile) {
		if (\$tempFile != \$fileName) {
			@unlink(\$tempFile);
		}
	}
	
	throw new XenForo_Exception('Non-image passed in to _prepareImage');
}

return \$outputFiles;
		
		");
		
		$this->_addMethod('_moveImages', 'protected', array('$uploaded'), "
		
if (is_array(\$uploaded)) {
	\$data = \$this->getMergedData();
	foreach (\$uploaded as \$sizeCode => \$tempFile) {
		\$filePath = {$modelClassName}::getImageFilePath(\$data, \$sizeCode);
		\$directory = dirname(\$filePath);
 
		if (XenForo_Helper_File::createDirectory(\$directory, true) && is_writable(\$directory)) {
			if (file_exists(\$filePath)) {
				unlink(\$filePath);
			}
					
			\$success = @rename(\$tempFile, \$filePath);
			if (\$success) {
				XenForo_Helper_File::makeWritableByFtpUser(\$filePath);
			}
		}
	}
}
		
		");
		
		$this->_addMethod('_postSave', 'protected', array(), "
		
\$uploaded = \$this->getExtraData(self::DATA_IMAGE_PREPARED);
if (\$uploaded) {
	\$this->_moveImages(\$uploaded);

	if (\$this->isUpdate()) {
		// removes old image
		\$existingData = \$this->getMergedExistingData();
		foreach (array_keys(\$this->getImageSizes()) as \$sizeCode) {
			\$filePath = {$modelClassName}::getImageFilePath(\$existingData, \$sizeCode);
			@unlink(\$filePath);
		}
	}
}
		
		");
		
		$this->_addMethod('_postDelete', 'protected', array(), "
		
\$existingData = \$this->getMergedExistingData();
foreach (array_keys(\$this->getImageSizes()) as \$sizeCode) {
	\$filePath = {$modelClassName}::getImageFilePath(\$existingData, \$sizeCode);
	@unlink(\$filePath);
}
		
		");
		
		$this->_addMethod('getImageSizes', 'public', array(), "
		
return array(
	'x' => self::IMAGE_SIZE_ORIGINAL,
	'l' => 96,
	'm' => 48,
	's' => 24
);
		
		");
		
		return true;
	}
	
	protected function _generatePhrasesCode() {
		if (!empty($this->_dataClass['phrases'])) {
			foreach ($this->_dataClass['phrases'] as $phraseType) {
				$camelCase = ucwords(str_replace('_', ' ', $phraseType));
				$constantName = self::generateDataPhraseConstant($this->_addOn, $this->_config, $this->_dataClass, $phraseType);
				$getPhraseTitleFunction = DevHelper_Generator_Code_Model::generateGetPhraseTitleFunctionName($this->_addOn, $this->_config, $this->_dataClass, $phraseType);
				
				$this->_addConstant($constantName, "'phrase{$camelCase}'");
				
				$this->_addMethod('_postSave', 'protected', array(), "
		
\$phrase{$camelCase} = \$this->getExtraData(self::{$constantName});
if (\$phrase{$camelCase} !== null) {
	\$this->_insertOrUpdateMasterPhrase(\$this->_get{$this->_dataClass['camelCase']}Model()->{$getPhraseTitleFunction}(\$this->get('{$this->_dataClass['id_field']}')), \$phrase{$camelCase});
}
		
				");
				
				$this->_addMethod('_postDelete', 'protected', array(), "

\$this->_deleteMasterPhrase(\$this->_get{$this->_dataClass['camelCase']}Model()->{$getPhraseTitleFunction}(\$this->get('{$this->_dataClass['id_field']}')));
		
				");
			}
		}
	}
	
	protected function _getClassName() {
		return self::getClassName($this->_addOn, $this->_config, $this->_dataClass);
	}
	
	public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		$g = new self($addOn, $config, $dataClass);
		
		return array($g->_getClassName(), $g->_generate());
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'DataWriter_' . $dataClass['camelCase']);
	}
	
	public static function generateDataPhraseConstant(array $addOn, DevHelper_Config_Base $config, array $dataClass, $phraseType) {
		$camelCase = ucwords(str_replace('_', ' ', $phraseType));
		return 'DATA_PHRASE_' . strtoupper($phraseType);
	}
}