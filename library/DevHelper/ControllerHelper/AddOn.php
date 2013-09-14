<?php

class DevHelper_ControllerHelper_AddOn extends XenForo_ControllerHelper_Abstract
{
	public function filterKeepActiveAddOns(array &$dataGrouped, array $addOns = null)
	{
		if ($addOns === null)
		{
			$addOns = $this->_controller->getModelFromCache('XenForo_Model_AddOn')->getAllAddOns();
		}

		foreach ($addOns as $addOn)
		{
			if (empty($addOn['active']))
			{
				// remove template modifications from inactive add-ons
				if (!empty($dataGrouped[$addOn['addon_id']]))
				{
					unset($dataGrouped[$addOn['addon_id']]);
				}
			}
		}
	}

}
