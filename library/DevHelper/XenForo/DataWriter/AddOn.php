<?php

class DevHelper_XenForo_DataWriter_AddOn extends XFCP_DevHelper_XenForo_DataWriter_AddOn
{
    protected function _preSave()
    {
        if (isset($GLOBALS[DevHelper_Listener::XENFORO_CONTROLLERADMIN_ADDON_SAVE])) {
            /** @var DevHelper_XenForo_ControllerAdmin_AddOn $controller */
            $controller = $GLOBALS[DevHelper_Listener::XENFORO_CONTROLLERADMIN_ADDON_SAVE];
            $controller->DevHelper_actionSave($this);
        }

        parent::_preSave();
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        if ($this->get('addon_id') !== 'devHelper'
            && $this->isChanged('active')
            && $this->get('active') > 0
        ) {
            DevHelper_Generator_Code_XenForoConfig::updateConfig('development.default_addon', $this->get('addon_id'));
        }
    }


}
