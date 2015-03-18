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

}
