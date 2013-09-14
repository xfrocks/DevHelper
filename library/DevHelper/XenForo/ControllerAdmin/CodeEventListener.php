<?php

class DevHelper_XenForo_ControllerAdmin_CodeEventListener extends XFCP_DevHelper_XenForo_ControllerAdmin_CodeEventListener
{

	public function actionIndex()
	{
		$response = parent::actionIndex();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$addOns = &$response->params['addOns'];
			$listeners = &$response->params['listeners'];

			foreach ($addOns as $addOn)
			{
				if (empty($addOn['active']))
				{
					// remove listeners from inactive add-ons
					if (!empty($listeners[$addOn['addon_id']]))
						unset($listeners[$addOn['addon_id']]);
				}
			}
		}

		return $response;
	}

}
