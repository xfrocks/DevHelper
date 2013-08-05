#!/usr/bin/php
<?php

if ($argc < 3) 
{
    die(sprintf("USAGE: %s [path/to/add-on/repo] [path/to/xenforo/root]\n", basename($argv[0])));
}

$pathToAddOnRepo = realpath($argv[1]);
if (empty($pathToAddOnRepo))
{
    die(sprintf("Path to add-on repo not found: %s\n", $argv[1]));
}

$pathToXenForoRoot = realpath($argv[2]);
if (empty($pathToXenForoRoot))
{
    die(sprintf("Path to XenForo root not found: %s\n", $argv[2]));
}
require($pathToXenForoRoot . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($pathToXenForoRoot . '/library');
XenForo_Application::initialize($pathToXenForoRoot . '/library', $pathToXenForoRoot);

function checkFile($file)
{
    $contents = file_get_contents($file);
    $offset = 0;

    while(true)
    {
        if (preg_match('/extends[^X]+(XFCP_[A-Za-z_]+)/', $contents, $matches, PREG_OFFSET_CAPTURE, $offset))
        {
            $offset = $matches[0][1] + 1;

            $xfcpClassName = $matches[1][0];
            $parts = explode('_', $xfcpClassName);
            array_shift($parts); // remove XFCP_
            array_shift($parts); // remove add-on prefix (if add-on has multiple prefixes, this is good enough to prevent processing our own file)

            $processingParts = array();
            $processed = false;
            while (count($parts) > 0)
            {
                $part = array_pop($parts);
                array_unshift($processingParts, $part);
                $processingClassName = implode('_', $processingParts);

                if (class_exists($processingClassName))
                {
                    eval('class ' . $xfcpClassName . ' extends ' . $processingClassName . ' {}');

                    try
                    {
                        require($file);
                    }
                    catch (Exception $e)
                    {
                        echo sprintf("Exception: %s\n", $e->getMessage());
                    }

                    $processed = true;
                }
                elseif (strpos($processingClassName, 'XenForo_ViewPublic_') === 0)
                {
                    // for XenForo_ViewPublic_* classes, if the view class itself cannot be found
                    // the system will load XenForo_ViewPublic_Base, we will check againts that now
                    eval('class ' . $xfcpClassName . ' extends XenForo_ViewPublic_Base {}');

                    try
                    {
                        require($file);
                    }
                    catch (Exception $e)
                    {
                        echo sprintf("Exception: %s\n", $e->getMessage());
                    }

                    $processed = true;
                }

                if ($processed)
                {
                    break;
                }
            }
        }
        else
        {
            break;
        }
    }
}

function loop($dir)
{
    $nodes = glob(sprintf("%s/*", $dir));
    
    foreach($nodes as $node)
    {
        if (is_dir($node))
        {
            loop($node);
        }
        else
        {
            $ext = strtolower(pathinfo($node, PATHINFO_EXTENSION));
            if ($ext === 'php')
            {
                checkFile($node);
            }
        }
    }
}
loop($pathToAddOnRepo);