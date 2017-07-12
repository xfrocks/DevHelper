<?php

class DevHelper_XenForo_ControllerAdmin_Phrase extends XFCP_DevHelper_XenForo_ControllerAdmin_Phrase
{
    public function actionAdd()
    {
        if (XenForo_Application::debugMode()
            && XenForo_Visitor::getInstance()->isSuperAdmin()
            && $this->_input->filterSingle('language_id', XenForo_Input::UINT) > 0
        ) {
            return $this->responseError('Please use non-super admin account to create non-master phrase.');
        }

        return parent::actionAdd();
    }

    public function actionEdit()
    {
        if (XenForo_Application::debugMode()
            && XenForo_Visitor::getInstance()->isSuperAdmin()
            && $this->_input->filterSingle('language_id', XenForo_Input::UINT) > 0
        ) {
            return $this->responseError('Please use non-super admin account to edit non-master phrase.');
        }

        return parent::actionEdit();
    }
}
