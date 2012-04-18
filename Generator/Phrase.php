<?php
class DevHelper_Generator_Phrase {
	public static function getPhraseName(array $addOn, DevHelper_Config_Base $config, array $dataClass, $phrase) {
		return strtolower($addOn['addon_id'] . '_' . $phrase);
	}
	
	public static function generatePhrase(array $addOn, $title, $phraseText) {
		if (self::checkPhraseExists($title)) {
			return false;
		}
		
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Phrase');
		$writer->bulkSet(array(
			'title' => $title,
			'phrase_text' => $phraseText,
			'language_id' => 0,
			'global_cache' => 0,
			'addon_id' => $addOn['addon_id'],
		));
		$writer->updateVersionId();
		$writer->save();

		return true;
	}
	
	public static function generatePhraseAutoCamelCaseStyle(array $addOn, DevHelper_Config_Base $config, array $dataClass, $dashText) {
		$camelCase = ucwords(str_replace('_', ' ', $dashText));
		$title = self::getPhraseName($addOn, $config, $dataClass, $dashText);
		self::generatePhrase($addOn, $title, $camelCase);
		
		return $title;
	}
	
	public static function checkPhraseExists($title) {
		$info = self::_getPhraseModel()->getPhrasesInLanguageByTitles(array($title), 0);
		
		return !empty($info);
	}
	
	protected static function _getPhraseModel() {
		static $model = null;
		
		if ($model === null) {
			$model = XenForo_Model::create('XenForo_Model_Phrase');
		}
		
		return $model;
	}
}