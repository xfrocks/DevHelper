<?php

class DevHelper_XenForo_ControllerAdmin_Permission extends XFCP_DevHelper_XenForo_ControllerAdmin_Permission
{
	public function actionDefinitions()
	{
		$response = parent::actionDefinitions();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$permissionGroups = &$response->params['permissionGroups'];
			$permissionsGrouped = &$response->params['permissionsGrouped'];
			$permissionsUngrouped = &$response->params['permissionsUngrouped'];
			$interfaceGroups = &$response->params['interfaceGroups'];
			$totalPermissions = &$response->params['totalPermissions'];

			$addOns = $this->getModelFromCache('XenForo_Model_AddOn')->getAllAddOns();
			$removedCount = 0;

			$this->getHelper('DevHelper_ControllerHelper_AddOn')->filterKeepActiveAddOnsDirect($permissionGroups, $addOns);

			$this->getHelper('DevHelper_ControllerHelper_AddOn')->filterKeepActiveAddOnsDirect($interfaceGroups, $addOns);

			foreach (array_keys($permissionsGrouped) as $interfaceGroupId)
			{
				if (empty($interfaceGroups[$interfaceGroupId]))
				{
					$removedCount += count($permissionsGrouped[$interfaceGroupId]);
					unset($permissionsGrouped[$interfaceGroupId]);
				}
			}

			foreach ($permissionsGrouped as &$groupPermissions)
			{
				$removedCount += $this->getHelper('DevHelper_ControllerHelper_AddOn')->filterKeepActiveAddOnsDirect($groupPermissions, $addOns);
			}

			$removedCount += $this->getHelper('DevHelper_ControllerHelper_AddOn')->filterKeepActiveAddOnsDirect($permissionsUngrouped, $addOns);

			$totalPermissions -= $removedCount;
		}

		return $response;
	}

}
