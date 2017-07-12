<?php

class DevHelper_XenForo_ControllerAdmin_Permission extends XFCP_DevHelper_XenForo_ControllerAdmin_Permission
{
    public function actionDefinitions()
    {
        $response = parent::actionDefinitions();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $permissionGroups = &$response->params['permissionGroups'];
            $permissionsGrouped = &$response->params['permissionsGrouped'];
            $permissionsUngrouped = &$response->params['permissionsUngrouped'];
            $interfaceGroups = &$response->params['interfaceGroups'];
            $totalPermissions = &$response->params['totalPermissions'];

            $removedCount = 0;

            /** @var DevHelper_ControllerHelper_AddOn $helper */
            $helper = $this->getHelper('DevHelper_ControllerHelper_AddOn');
            $helper->filterKeepActiveAddOnsDirect($permissionGroups);
            $helper->filterKeepActiveAddOnsDirect($interfaceGroups);

            foreach (array_keys($permissionsGrouped) as $interfaceGroupId) {
                if (empty($interfaceGroups[$interfaceGroupId])) {
                    $removedCount += count($permissionsGrouped[$interfaceGroupId]);
                    unset($permissionsGrouped[$interfaceGroupId]);
                }
            }

            foreach ($permissionsGrouped as &$groupPermissions) {
                $removedCount += $helper->filterKeepActiveAddOnsDirect($groupPermissions);
            }

            $removedCount += $helper->filterKeepActiveAddOnsDirect($permissionsUngrouped);

            $totalPermissions -= $removedCount;
        }

        return $response;
    }
}
