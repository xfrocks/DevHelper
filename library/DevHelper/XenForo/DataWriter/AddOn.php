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
}
