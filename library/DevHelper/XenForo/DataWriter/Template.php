<?php

class DevHelper_XenForo_DataWriter_Template extends XFCP_DevHelper_XenForo_DataWriter_Template
{
	public function DevHelper_saveTemplate()
	{
		$template = $this->getMergedData();

		$filePath = DevHelper_Helper_Template::getTemplateFilePath($template);

		XenForo_Helper_File::createDirectory(dirname($filePath));
		file_put_contents($filePath, $template['template']);
	}

	protected function _postSaveAfterTransaction()
	{
		$this->DevHelper_saveTemplate();

		return parent::_postSaveAfterTransaction();
	}

}
