<?php

class DevHelper_XenForo_DataWriter_Template extends XFCP_DevHelper_XenForo_DataWriter_Template
{
    public function DevHelper_saveTemplate()
    {
        if (DevHelper_Helper_Template::autoExportImport() == false) {
            return false;
        }

        $template = $this->getMergedData();
        $filePath = DevHelper_Helper_Template::getTemplateFilePath($template);
        XenForo_Helper_File::createDirectory(dirname($filePath));

        return file_put_contents($filePath, $template['template']) > 0;
    }

    protected function _postSaveAfterTransaction()
    {
        $this->DevHelper_saveTemplate();

        parent::_postSaveAfterTransaction();
    }
}
