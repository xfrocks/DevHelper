<?php
class DevHelper_ViewAdmin_AddOn_DataManager extends XenForo_ViewAdmin_Base {
	public function renderHtml() {
		foreach ($this->_params['dataClasses'] as &$dataClass) {
			$dataClass['fieldsList'] = implode(', ', array_keys($dataClass['fields']));
		}
		
		if (!empty($this->_params['focusedDataClass'])) {
			$this->_params['focusedDataClass']['sqlCreate'] = DevHelper_Generator_Db::createTable($this->_params['config'], $this->_params['focusedDataClass']);
		}
	}
}