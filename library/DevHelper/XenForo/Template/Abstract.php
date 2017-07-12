<?php

/** @var XenForo_Application $application */
$application = XenForo_Application::getInstance();
$originalContents = file_get_contents($application->getRootDir() . '/library/XenForo/Template/Abstract.php');
$contents = substr($originalContents, strpos($originalContents, '<?php') + 5);
$contents = str_replace(
    'class XenForo_Template_Abstract',
    'class _XenForo_Template_Abstract',
    $contents
);
$contents = str_replace('self::', 'static::', $contents);
eval($contents);

abstract class DevHelper_XenForo_Template_Abstract extends _XenForo_Template_Abstract
{
    protected function _processJsUrls(array $jsFiles)
    {
        DevHelper_Helper_Js::processJsFiles($jsFiles);

        return parent::_processJsUrls($jsFiles);
    }
}

eval('abstract class XenForo_Template_Abstract extends DevHelper_XenForo_Template_Abstract {}');

if (false) {
    abstract class _XenForo_Template_Abstract extends XenForo_Template_Abstract
    {
    }
}
