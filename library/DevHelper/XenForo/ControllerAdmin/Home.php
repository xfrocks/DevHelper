<?php

class DevHelper_XenForo_ControllerAdmin_Home extends XFCP_DevHelper_XenForo_ControllerAdmin_Home
{
    public function actionIndex()
    {
        /** @var DevHelper_ControllerHelper_AddOn $helper */
        $helper = $this->getHelper('DevHelper_ControllerHelper_AddOn');
        $helper->selfCheck();

        return parent::actionIndex();
    }
}
