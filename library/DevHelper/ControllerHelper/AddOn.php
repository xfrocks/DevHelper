<?php

class DevHelper_ControllerHelper_AddOn extends XenForo_ControllerHelper_Abstract
{
    public function filterKeepActiveAddOns(array &$dataGrouped, array $addOns = null)
    {
        $removedCount = 0;

        if ($addOns === null) {
            /** @var XenForo_Model_AddOn $addOnModel */
            $addOnModel = $this->_controller->getModelFromCache('XenForo_Model_AddOn');
            $addOns = $addOnModel->getAllAddOns();
        }

        foreach ($addOns as $addOn) {
            if (empty($addOn['active'])) {
                // remove template modifications from inactive add-ons
                if (!empty($dataGrouped[$addOn['addon_id']])) {
                    $removedCount += count($dataGrouped[$addOn['addon_id']]);
                    unset($dataGrouped[$addOn['addon_id']]);
                }
            }
        }

        return $removedCount;
    }

    public function filterKeepActiveAddOnsDirect(array &$data, array $addOns = null)
    {
        $removedCount = 0;

        if ($addOns === null) {
            /** @var XenForo_Model_AddOn $addOnModel */
            $addOnModel = $this->_controller->getModelFromCache('XenForo_Model_AddOn');
            $addOns = $addOnModel->getAllAddOns();
        }

        foreach (array_keys($data) as $dataId) {
            $singleRef = &$data[$dataId];

            if (empty($addOns[$singleRef['addon_id']])) {
                continue;
            }
            $addOnRef = &$addOns[$singleRef['addon_id']];

            if (empty($addOnRef['active'])) {
                $removedCount++;
                unset($data[$dataId]);
            }
        }

        return $removedCount;
    }

}
