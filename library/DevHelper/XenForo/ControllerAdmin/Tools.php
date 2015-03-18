<?php

class DevHelper_XenForo_ControllerAdmin_Tools extends XFCP_DevHelper_XenForo_ControllerAdmin_Tools
{
    public function actionDevHelperSync()
    {
        if (DevHelper_Installer::checkAddOnVersion()) {
            return $this->responseNoPermission();
        }

        /** @var XenForo_Model_AddOn $addOnModel */
        $addOnModel = $this->getModelFromCache('XenForo_Model_AddOn');

        $addOn = $addOnModel->getAddOnById('devHelper');
        $xmlPath = DevHelper_Generator_File::getAddOnXmlPath($addOn);

        $addOnModel->installAddOnXmlFromFile($xmlPath, $addOn['addon_id']);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('index')
        );
    }
}
