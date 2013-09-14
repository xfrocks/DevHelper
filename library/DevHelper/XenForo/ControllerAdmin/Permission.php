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

			foreach (array_keys($permissionGroups) as $permissionGroupId)
			{
				$permissionGroupRef = &$permissionGroups[$permissionGroupId];

				if (empty($addOns[$permissionGroupRef['addon_id']]))
				{
					continue;
				}
				$addOnRef = &$addOns[$permissionGroupRef['addon_id']];

				if (empty($addOnRef['active']))
				{
					unset($permissionGroups[$permissionGroupId]);
				}
			}

			foreach (array_keys($interfaceGroups) as $interfaceGroupId)
			{
				$interfaceGroupRef = &$interfaceGroups[$interfaceGroupId];

				if (empty($addOns[$interfaceGroupRef['addon_id']]))
				{
					continue;
				}
				$addOnRef = &$addOns[$interfaceGroupRef['addon_id']];

				if (empty($addOnRef['active']))
				{
					unset($interfaceGroups[$interfaceGroupId]);

					if (!empty($permissionsGrouped[$interfaceGroupId]))
					{
						$removedCount += count($permissionsGrouped[$interfaceGroupId]);
						unset($permissionsGrouped[$interfaceGroupId]);
					}
				}
			}

			foreach ($permissionsGrouped as &$groupPermissions)
			{
				foreach (array_keys($groupPermissions) as $permissionId)
				{
					$permissionRef = &$groupPermissions[$permissionId];

					if (empty($addOns[$permissionRef['addon_id']]))
					{
						continue;
					}
					$addOnRef = &$addOns[$permissionRef['addon_id']];

					if (empty($addOnRef['active']))
					{
						$removedCount++;
						unset($groupPermissions[$permissionId]);
					}
				}
			}

			foreach (array_keys($permissionsUngrouped) as $permissionId)
			{
				$permissionRef = &$permissionsUngrouped[$permissionId];

				if (empty($addOns[$permissionRef['addon_id']]))
				{
					continue;
				}
				$addOnRef = &$addOns[$permissionRef['addon_id']];

				if (empty($addOnRef['active']))
				{
					$removedCount++;
					unset($permissionsUngrouped[$permissionId]);
				}
			}

			$totalPermissions -= $removedCount;
		}

		return $response;
	}

}
