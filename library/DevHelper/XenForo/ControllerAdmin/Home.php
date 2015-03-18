<?php

class DevHelper_XenForo_ControllerAdmin_Home extends XFCP_DevHelper_XenForo_ControllerAdmin_Home
{
    public function actionIndex()
    {
        if (DevHelper_Installer::checkAddOnVersion() == 0) {
            throw new XenForo_Exception('DevHelper version mis-matched');
        }

        return parent::actionIndex();
    }
}
