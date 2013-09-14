<?php

class DevHelper_XenForo_ControllerAdmin_RoutePrefix extends XFCP_DevHelper_XenForo_ControllerAdmin_RoutePrefix
{
	public function actionIndex()
	{
		$response = parent::actionIndex();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$publicPrefixes = &$response->params['publicPrefixes'];
			$adminPrefixes = &$response->params['adminPrefixes'];
			$totalPrefixes = &$response->params['totalPrefixes'];

			$addOns = $this->getModelFromCache('XenForo_Model_AddOn')->getAllAddOns();
			$removedCount = 0;

			$removedCount += $this->getHelper('DevHelper_ControllerHelper_AddOn')->filterKeepActiveAddOnsDirect($publicPrefixes, $addOns);

			$removedCount += $this->getHelper('DevHelper_ControllerHelper_AddOn')->filterKeepActiveAddOnsDirect($adminPrefixes, $addOns);

			$totalPrefixes -= $removedCount;
		}

		return $response;
	}

}
