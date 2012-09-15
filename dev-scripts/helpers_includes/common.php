<?php

class Helper_Common
{
	public static function getAddOnIds()
	{
		$addOnModel = self::loadCachedModel('XenForo_Model_AddOn');
		
		return array_keys($addOnModel->getAddOnOptionsList(false, false));
	}

	public static function loadCachedModel($class)
	{
		static $models = array();

		if (!isset($models[$class]))
		{
			$models[$class] = XenForo_Model::create($class);
		}

		return $models[$class];
	}

	public static function parseCommand($command)
	{
		$parts = explode(' ', preg_replace('/\s+/', ' ', $command));

		foreach (array_keys($parts) as $i)
		{
			if ($parts[$i] === '')
			{
				unset($parts[$i]);
			}
		}

		return $parts;
	}
}