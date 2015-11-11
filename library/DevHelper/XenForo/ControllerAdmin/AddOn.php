<?php

class DevHelper_XenForo_ControllerAdmin_AddOn extends XFCP_DevHelper_XenForo_ControllerAdmin_AddOn
{
    protected $_DevHelper_actionSave_addOnIdChanged = false;

    public function actionIndex()
    {
        /** @var DevHelper_ControllerHelper_AddOn $helper */
        $helper = $this->getHelper('DevHelper_ControllerHelper_AddOn');
        $helper->selfCheck();

        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            uasort($response->params['addOns'], create_function('$a, $b', 'if ($a["active"] == $b["active"]) { return strcmp($a["title"], $b["title"]); } else { if ($a["active"]) return -1; else return 1; }'));
        }

        return $response;
    }

    public function actionSave()
    {
        $GLOBALS[DevHelper_Listener::XENFORO_CONTROLLERADMIN_ADDON_SAVE] = $this;

        $response = parent::actionSave();

        if ($response instanceof XenForo_ControllerResponse_Redirect) {
            if (!empty($this->_DevHelper_actionSave_addOnIdChanged)) {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('add-ons/file-export', $this->_DevHelper_actionSave_addOnIdChanged));
            }
        }

        return $response;
    }

    public function DevHelper_actionSave(XenForo_DataWriter_AddOn $addOnDw)
    {
        if ($addOnDw->isUpdate() AND $addOnDw->isChanged('version_id')) {
            $this->_DevHelper_actionSave_addOnIdChanged = $addOnDw->getMergedData();
        }
    }

    public function actionDataManager()
    {
        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
        $addOn = $this->_getAddOnOrError($addOnId);

        $config = $this->_getConfigModel()->loadAddOnConfig($addOn);

        $viewParams = array(
            'addOn' => $addOn,
            'config' => $config,
            'dataClasses' => $config->getDataClasses(),
        );

        $dataClassName = $this->_input->filterSingle('dataClass', XenForo_Input::STRING);
        if (!empty($dataClassName) AND $config->checkDataClassExists($dataClassName)) {
            $viewParams['focusedDataClass'] = $config->getDataClass($dataClassName);
        }

        return $this->responseView('DevHelper_ViewAdmin_AddOn_DataManager', 'devhelper_addon_data_manager', $viewParams);
    }

    public function actionGenerateFile()
    {
        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
        $addOn = $this->_getAddOnOrError($addOnId);

        $config = $this->_getConfigModel()->loadAddOnConfig($addOn);
        $dataClassName = $this->_input->filterSingle('dataClass', XenForo_Input::STRING);
        if (!empty($dataClassName) AND $config->checkDataClassExists($dataClassName)) {
            $dataClass = $config->getDataClass($dataClassName);
            $file = $this->_input->filterSingle('file', XenForo_Input::STRING);

            switch ($file) {
                case 'data_writer':
                case 'model':
                case 'route_prefix_admin':
                case 'controller_admin':
                case 'installer':
                    $suffix = str_replace(' ', '', ucwords(str_replace('_', ' ', $file)));
                    return call_user_func(array(
                        $this,
                        '_actionGenerate' . $suffix
                    ), $addOn, $config, $dataClass);
            }
        }

        return $this->responseNoPermission();
    }

    protected function _actionGenerateDataWriter(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        list($className, $contents) = DevHelper_Generator_Code_DataWriter::generate($addOn, $config, $dataClass);
        $path = DevHelper_Generator_File::writeClass($className, $contents);

        $config->updateDataClassFile($dataClass['name'], 'data_writer', $className, $path);
        $this->_getConfigModel()->saveAddOnConfig($addOn, $config);

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, XenForo_Link::buildAdminLink('add-ons/data-manager', $addOn, array('dataClass' => $dataClass['name'])));
    }

    protected function _actionGenerateModel(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        list($className, $contents) = DevHelper_Generator_Code_Model::generate($addOn, $config, $dataClass);
        $path = DevHelper_Generator_File::writeClass($className, $contents);

        $config->updateDataClassFile($dataClass['name'], 'model', $className, $path);
        $this->_getConfigModel()->saveAddOnConfig($addOn, $config);

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, XenForo_Link::buildAdminLink('add-ons/data-manager', $addOn, array('dataClass' => $dataClass['name'])));
    }

    protected function _actionGenerateRoutePrefixAdmin(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $routePrefix = $this->_input->filterSingle('route_prefix', XenForo_Input::STRING);
        $controller = $this->_input->filterSingle('controller', XenForo_Input::STRING);
        $majorSection = $this->_input->filterSingle('major_section', XenForo_Input::STRING);

        if (empty($routePrefix) OR empty($controller)) {
            $viewParams = array(
                'addOn' => $addOn,
                'dataClass' => $dataClass,
                'routePrefix' => $routePrefix ? $routePrefix : DevHelper_Generator_Code_RoutePrefixAdmin::getRoutePrefix($addOn, $config, $dataClass),
                'controller' => $controller ? $controller : DevHelper_Generator_Code_ControllerAdmin::getClassName($addOn, $config, $dataClass),
                'majorSection' => $majorSection,
            );

            return $this->responseView('DevHelper_ViewAdmin_AddOn_GenerateRoutePrefixAdmin', 'devhelper_addon_generate_route_prefix_admin', $viewParams);
        } else {
            list($className, $contents) = DevHelper_Generator_Code_RoutePrefixAdmin::generate($addOn, $config, $dataClass, array(
                'routePrefix' => $routePrefix,
                'controller' => $controller,
                'majorSection' => $majorSection,
            ));
            $path = DevHelper_Generator_File::writeClass($className, $contents);

            $config->updateDataClassFile($dataClass['name'], 'route_prefix_admin', $className, $path);
            $this->_getConfigModel()->saveAddOnConfig($addOn, $config);

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, XenForo_Link::buildAdminLink('add-ons/data-manager', $addOn, array('dataClass' => $dataClass['name'])));
        }
    }

    protected function _actionGenerateControllerAdmin(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $routePrefix = $this->_input->filterSingle('route_prefix', XenForo_Input::STRING);
        $controller = $this->_input->filterSingle('controller', XenForo_Input::STRING);

        if (empty($routePrefix) OR empty($controller)) {
            $viewParams = array(
                'addOn' => $addOn,
                'dataClass' => $dataClass,
                'routePrefix' => $routePrefix ? $routePrefix : DevHelper_Generator_Code_RoutePrefixAdmin::getRoutePrefix($addOn, $config, $dataClass),
                'controller' => $controller ? $controller : DevHelper_Generator_Code_ControllerAdmin::getClassName($addOn, $config, $dataClass),
            );

            return $this->responseView('DevHelper_ViewAdmin_AddOn_GenerateControllerAdmin', 'devhelper_addon_generate_controller_admin', $viewParams);
        } else {
            list($className, $contents) = DevHelper_Generator_Code_ControllerAdmin::generate($addOn, $config, $dataClass, array(
                'routePrefix' => $routePrefix,
                'controller' => $controller,
            ));
            $path = DevHelper_Generator_File::writeClass($className, $contents);

            $config->updateDataClassFile($dataClass['name'], 'controller_admin', $className, $path);
            $this->_getConfigModel()->saveAddOnConfig($addOn, $config);

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, XenForo_Link::buildAdminLink('add-ons/data-manager', $addOn, array('dataClass' => $dataClass['name'])));
        }
    }

    public function actionGenerateInstaller()
    {
        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
        $addOn = $this->_getAddOnOrError($addOnId);

        $config = $this->_getConfigModel()->loadAddOnConfig($addOn);

        list($className, $contents) = DevHelper_Generator_Code_Installer::generate($addOn, $config);
        DevHelper_Generator_File::writeClass($className, $contents);

        $dw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
        $dw->setExistingData($addOn);
        $dw->set('install_callback_class', $className);
        $dw->set('install_callback_method', 'install');
        $dw->set('uninstall_callback_class', $className);
        $dw->set('uninstall_callback_method', 'uninstall');
        $dw->save();

        $xmlPath = DevHelper_Generator_File::getAddOnXmlPath($addOn, null, $config);
        $this->_getAddOnModel()->getAddOnXml($addOn)->save($xmlPath);

        if ($this->_input->filterSingle('run', XenForo_Input::UINT)) {
            call_user_func(array(
                $className,
                'install'
            ), $dw->getMergedData(), $dw->getMergedData());
        }

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, XenForo_Link::buildAdminLink('add-ons/data-manager', $addOn));
    }

    public function actionFileExport()
    {
        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
        $addOn = $this->_getAddOnOrError($addOnId);

        $config = $this->_getConfigModel()->loadAddOnConfig($addOn);

        $exportPath = $config->getExportPath();

        if ($exportPath === false) {
            return $this->responseNoPermission();
        }

        DevHelper_Helper_Phrase::startLookingForPhraseTitles();
        DevHelper_Helper_Xfcp::startLookingForXfcpClasses();

        echo '<pre>';

        DevHelper_Generator_File::fileExport($addOn, $config, $exportPath);

        /** @var XenForo_Model_Template $templateModel */
        $templateModel = $this->getModelFromCache('XenForo_Model_Template');
        $templates = $templateModel->getMasterTemplatesInAddOn($addOn['addon_id']);
        foreach ($templates AS $template) {
            /** @var DevHelper_XenForo_DataWriter_Template $dw */
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Template');
            $dw->setExistingData($template, true);

            if ($dw->DevHelper_saveTemplate()) {
                echo "Saved template {$template['title']}\n";
            }
        }

        /** @var XenForo_Model_Phrase $phraseModel */
        $phraseModel = $this->getModelFromCache('XenForo_Model_Phrase');
        $phrases = $phraseModel->getMasterPhrasesInAddOn($addOnId);
        DevHelper_Helper_Phrase::finishLookingForPhraseTitles($phrases, $phraseModel);
        DevHelper_Helper_Xfcp::finishLookingForXfcpClasses($addOn, $config);

        echo '</pre>';

        DevHelper_Generator_Code_XenForoConfig::updateConfig('development.default_addon', $addOn['addon_id']);

        die('Done');
    }

    public function actionAllOff()
    {
        $addOns = $this->_getAddOnModel()->getAllAddOns();

        foreach ($addOns as $addOn) {
            if (empty($addOn['active'])) {
                continue;
            }

            if ($addOn['addon_id'] == 'devHelper') {
                continue;
            }

            $addOnDw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
            $addOnDw->setExistingData($addOn, true);
            $addOnDw->set('active', 0);
            $addOnDw->save();
        }

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('add-ons'));
    }

    /**
     * @return DevHelper_Model_Config
     */
    protected function _getConfigModel()
    {
        return $this->getModelFromCache('DevHelper_Model_Config');
    }

}
