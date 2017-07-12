<?php

class DevHelper_XenForo_ControllerPublic_Thread extends XFCP_DevHelper_XenForo_ControllerPublic_Thread
{
    protected $_DevHelper_disableAssertPostOnly = false;

    public function actionDevHelperReply()
    {
        $response = null;
        $this->_DevHelper_disableAssertPostOnly = true;

        $limit = $this->_input->filterSingle('limit', XenForo_Input::UINT, array('default' => 10));
        for ($i = 0; $i < $limit; $i++) {
            $this->_request->setParam('message', sprintf(
                'Post Body %d-%d',
                XenForo_Application::$time,
                $i
            ));

            try {
                $response = $this->actionAddReply();
            } catch (Exception $e) {
                return $this->responseError($e->getMessage());
            }
        }

        return $response;
    }

    protected function _assertPostOnly()
    {
        if ($this->_DevHelper_disableAssertPostOnly) {
            return;
        }

        parent::_assertPostOnly();
    }
}
