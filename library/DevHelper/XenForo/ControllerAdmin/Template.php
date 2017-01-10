<?php

class DevHelper_XenForo_ControllerAdmin_Template extends XFCP_DevHelper_XenForo_ControllerAdmin_Template
{
    public function actionAdd()
    {
        if (XenForo_Application::debugMode()
            && XenForo_Visitor::getInstance()->isSuperAdmin()
            && $this->_input->filterSingle('style_id', XenForo_Input::UINT) > 0
        ) {
            return $this->responseError('Please use non-super admin account to create non-master template.');
        }

        return parent::actionAdd();
    }

    public function actionEdit()
    {
        if (XenForo_Application::debugMode()
            && XenForo_Visitor::getInstance()->isSuperAdmin()
            && $this->_input->filterSingle('style_id', XenForo_Input::UINT) > 0
        ) {
            return $this->responseError('Please use non-super admin account to edit non-master template.');
        }

        return parent::actionEdit();
    }
}