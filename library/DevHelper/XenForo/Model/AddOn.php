<?php

class DevHelper_XenForo_Model_AddOn extends XFCP_DevHelper_XenForo_Model_AddOn
{

	public function getAddOnOptionsList($includeCustomOption = true, $includeXenForoOption = true)
	{
		$options = parent::getAddOnOptionsList($includeCustomOption, $includeXenForoOption);

		if ($includeCustomOption)
		{
			$groups = array();
			
			foreach (array_keys($options) as $key)
			{
				if (preg_match('/^(\[[^\]]+\])(.+)$/', $options[$key], $matches))
				{
					$addOnGroupId = $matches[1];
					$addOnName = $matches[2];
					
					$groups[$addOnGroupId][$key] = trim($addOnName);
					unset($options[$key]);
				}
			}
			
			foreach ($groups as $groupId => $groupAddOnNames)
			{
				$options[$groupId] = array();
				
				foreach ($groupAddOnNames as $key => $addOnName)
				{
					$options[$groupId][$key] = $addOnName; 
				}
			}
		}

		return $options;
	}

}
