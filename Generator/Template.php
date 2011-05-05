<?php
class DevHelper_Generator_Template {
	public static function getTemplateTitle(array $addOn, DevHelper_Config_Base $config, array $dataClass, $title) {
		return strtolower($addOn['addon_id'] . '_' . $title);
	}
	
	public static function generateAdminTemplate(array $addOn, $title, $template) {
		if (self::checkAdminTemplateExists($title)) {
			return false;
		}
		
		$propertyModel = self::_getStylePropertyModel();

		$properties = $propertyModel->keyPropertiesByName(
			$propertyModel->getEffectiveStylePropertiesInStyle(-1)
		);
		$propertyChanges = $propertyModel->translateEditorPropertiesToArray(
			$template, $template, $properties
		);

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_AdminTemplate');
		$writer->bulkSet(array(
			'title' => $title,
			'template' => $template,
			'addon_id' => $addOn['addon_id'],
		));
		$writer->save();

		$propertyModel->saveStylePropertiesInStyleFromTemplate(-1, $propertyChanges, $properties);

		return true;
	}
	
	public static function checkAdminTemplateExists($title) {
		$info = self::_getAdminTemplateModel()->getAdminTemplateByTitle($title);
		
		return !empty($info);
	}
	
	protected static function _getAdminTemplateModel() {
		static $model = null;
		
		if ($model === null) {
			$model = XenForo_Model::create('XenForo_Model_AdminTemplate');
		}
		
		return $model;
	}
	
	protected static function _getStylePropertyModel() {
		static $model = null;
		
		if ($model === null) {
			$model = XenForo_Model::create('XenForo_Model_StyleProperty');
		}
		
		return $model;
	}
}