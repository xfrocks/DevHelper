<?php

class DevHelper_XenForo_Route_PrefixAdmin_Templates extends XFCP_DevHelper_XenForo_Route_PrefixAdmin_Templates
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $routeMatch = parent::match($routePath, $request, $router);

        $action = $routeMatch->getAction();
        if (strpos($action, '_') !== false && intval($request->getParam('template_id')) === 0) {
            /** @var XenForo_Model_Template $templateModel */
            $templateModel = XenForo_Model::create('XenForo_Model_Template');
            $templateIds = $templateModel->getTemplateIdInStylesByTitle($action);
            if (!empty($templateIds[0])) {
                $request->setParam('template_id', $templateIds[0]);
                $routeMatch->setAction('edit');
            }
        }

        return $routeMatch;
    }

}