<?php

class DevHelper_XenForo_ControllerAdmin_RoutePrefix extends XFCP_DevHelper_XenForo_ControllerAdmin_RoutePrefix
{
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $publicPrefixes = &$response->params['publicPrefixes'];
            $adminPrefixes = &$response->params['adminPrefixes'];
            $totalPrefixes = &$response->params['totalPrefixes'];

            $removedCount = 0;

            /** @var DevHelper_ControllerHelper_AddOn $helper */
            $helper = $this->getHelper('DevHelper_ControllerHelper_AddOn');
            $removedCount += $helper->filterKeepActiveAddOnsDirect($publicPrefixes);
            $removedCount += $helper->filterKeepActiveAddOnsDirect($adminPrefixes);

            $totalPrefixes -= $removedCount;
        }

        return $response;
    }
}
