#!/usr/bin/php
<?php

if (empty($_SERVER) OR empty($_SERVER['PWD']) OR empty($argv))
{
	echo "Could not get environment variables\n";
	exit(-1);
}

$PWD = $_SERVER['PWD'];
$FILE = $argv[0];
$DIR = dirname($FILE);
$HELPER_DIR = $DIR . '/helpers';
$HELPERS = array();

// load helper scripts
if (is_dir($HELPER_DIR))
{
	$dh = opendir($HELPER_DIR);
	while ($file = readdir($dh)) {
		if ($file == '.') continue;
		if ($file == '..') continue;
		
		$filePath = $HELPER_DIR . '/' . $file;
		if (file_exists($filePath) AND strtolower(substr($file, -4)) == '.php') {
			$HELPERS[substr($file, 0, -4)] = $filePath;
		}
	}
	closedir($dh);
}

// load XenForo
@include($PWD . '/library/XenForo/Autoloader.php');
if (!class_exists('XenForo_Autoloader'))
{
	echo "Could not detect XenForo\n";
	exit(-1);
}
XenForo_Autoloader::getInstance()->setupAutoloader($PWD . '/library');
XenForo_Application::initialize($PWD . '/library', $PWD);

// load our functions
@include($DIR . '/helpers_includes/common.php');
if (!class_exists('Helper_Common'))
{
	echo "Corrupted script\n";
	exit(-1);
}

function readline_completion_impl($string, $index)
{
	$readline_info = readline_info();
	$line = substr($readline_info['line_buffer'], 0, $readline_info['end']);
	$parts = Helper_Common::parseCommand($line);
	$candidates = array();
	
	if (empty($parts))
	{
		// no input yet, just return list of helper functions
		$candidates += array_keys($GLOBALS['HELPERS']);
	}
	else
	{
		if (isset($GLOBALS['HELPERS'][$parts[0]]))
		{
			// we actually got the helper function correctly
			$PARAMS = array_slice($parts, 1);
			$IS_COMPLETION = true;
			require($GLOBALS['HELPERS'][$parts[0]]);
		}
		else
		{
			// incomplete helper function...
			$candidates += array_keys($GLOBALS['HELPERS']);
		}
	}
	
	return $candidates;
}
readline_completion_function('readline_completion_impl');

while (true)
{
	$command = readline('> ');
	readline_add_history($command);
	$parts = Helper_Common::parseCommand($command);
	
	if (empty($parts[0]) OR !isset($HELPERS[$parts[0]]))
	{
		$parts[0] = 'help';
	}
	
	$PARAMS = array_slice($parts, 1);
	require($HELPERS[$parts[0]]);
}