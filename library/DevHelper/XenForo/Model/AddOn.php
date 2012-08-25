<?php

class DevHelper_XenForo_Model_AddOn extends XFCP_DevHelper_XenForo_Model_AddOn {
	
	public function getAddOnOptionsList($includeCustomOption = true, $includeXenForoOption = true) {
		$options = parent::getAddOnOptionsList($includeCustomOption, $includeXenForoOption);
		
		if ($includeCustomOption) {
			// we have to filter out inactive add-ons
			$addOns = $this->getAllAddOns();
			foreach ($addOns AS $addOn) {
				if (isset($options[$addOn['addon_id']]) AND empty($addOn['active'])) {
					unset($options[$addOn['addon_id']]);
				}
			}
		}
		
		return $options;
	}
}