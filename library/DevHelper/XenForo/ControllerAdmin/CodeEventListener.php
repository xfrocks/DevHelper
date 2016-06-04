<?php

class DevHelper_XenForo_ControllerAdmin_CodeEventListener extends XFCP_DevHelper_XenForo_ControllerAdmin_CodeEventListener
{

    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $addOns = &$response->params['addOns'];
            $listeners = &$response->params['listeners'];
            $totalListeners =& $response->params['totalListeners'];

            /** @var DevHelper_ControllerHelper_AddOn $helper */
            $helper = $this->getHelper('DevHelper_ControllerHelper_AddOn');
            $totalListeners -= $helper->filterKeepActiveAddOns($listeners, $addOns);
        }

        return $response;
    }

    public function actionSave()
    {
        $dwInput = $this->_input->filter(array(
            'event_id' => XenForo_Input::STRING,
            'description' => XenForo_Input::STRING,
            'callback_class' => XenForo_Input::STRING,
            'callback_method' => XenForo_Input::STRING,
            'hint' => XenForo_Input::STRING,
            'addon_id' => XenForo_Input::STRING,
        ));

        if (!empty($dwInput['event_id'])
            && empty($dwInput['description'])
            && empty($dwInput['callback_class'])
            && empty($dwInput['callback_class'])
            && !empty($dwInput['hint'])
            && !empty($dwInput['addon_id'])
        ) {
            /** @var XenForo_Model_AddOn $addOnModel */
            $addOnModel = $this->getModelFromCache('XenForo_Model_AddOn');
            $addOn = $addOnModel->getAddOnById($dwInput['addon_id']);

            /** @var DevHelper_Model_Config $configModel */
            $configModel = $this->getModelFromCache('DevHelper_Model_Config');
            $config = $configModel->loadAddOnConfig($addOn);

            if (strpos($dwInput['event_id'], 'load_class') === 0) {
                $classPath = DevHelper_Generator_File::getClassPath($dwInput['hint']);
                if (is_file($classPath)) {

                    $method = DevHelper_Generator_Code_Listener::generateLoadClass($dwInput['hint'], $addOn, $config);
                    if ($method) {
                        $clazz = DevHelper_Generator_Code_Listener::getClassName($addOn, $config);

                        $this->_request->setParam('description', $dwInput['hint']);
                        $this->_request->setParam('callback_class', $clazz);
                        $this->_request->setParam('callback_method', $method);

                        XenForo_DataWriter::create('XenForo_DataWriter_CodeEventListener');
                        DevHelper_XenForo_DataWriter_CodeEventListener::DevHelper_markAsGeneratedCallback($clazz, $method);
                    }
                }
            }
        }

        return parent::actionSave();
    }
}
