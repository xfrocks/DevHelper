<?php

class DevHelper_XenForo_ControllerPublic_Forum extends XFCP_DevHelper_XenForo_ControllerPublic_Forum
{
    protected $_disableAssertPostOnly = false;

    public function actionDevHelperCreateThreads()
    {
        $response = null;
        $this->_disableAssertPostOnly = true;

        $limit = $this->_input->filterSingle('limit', XenForo_Input::UINT, array('default' => 10));
        for ($i = 0; $i < $limit; $i++) {
            $this->_request->setParam('title', sprintf('Thread Title %d-%d',
                XenForo_Application::$time, $i));
            $this->_request->setParam('message', sprintf('Thread Body %d-%d',
                XenForo_Application::$time, $i));

            try {
                $response = $this->actionAddThread();
            } catch (Exception $e) {
                // ignore
            }
        }

        return $response;
    }

    protected function _assertPostOnly()
    {
        if ($this->_disableAssertPostOnly) {
            return;
        }

        parent::_assertPostOnly();
    }


}