<?php
if (empty($IS_COMPLETION))
{
	if (empty($PARAMS[0]))
	{
		echo "Available functions:\n";
		foreach($GLOBALS['HELPERS'] as $helperFunction => $filePath)
		{
			echo "	$helperFunction\n";
		}
	}
	else
	{
		echo "Requesting " . $PARAMS[0] . "\n";
	}
}
else
{
	if (count($PARAMS) <= 1)
	{
		$candidates += array_keys($GLOBALS['HELPERS']);
	}
}