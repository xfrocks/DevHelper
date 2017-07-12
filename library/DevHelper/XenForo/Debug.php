<?php

/** @var XenForo_Application $application */
$application = XenForo_Application::getInstance();
$originalContents = file_get_contents($application->getRootDir() . '/library/XenForo/Debug.php');
$contents = substr($originalContents, strpos($originalContents, '<?php') + 5);
$contents = str_replace(
    'class XenForo_Debug',
    'class _XenForo_Debug',
    $contents
);
$contents = str_replace('self::', 'static::', $contents);
eval($contents);

abstract class DevHelper_XenForo_Debug extends _XenForo_Debug
{
    public static function getDebugHtml()
    {
        $html = parent::getDebugHtml();

        $eventsHtml = static::_DevHelper_formatMeasuredTime(DevHelper_XenForo_CodeEvent::$measuredTime['events']);
        $callbacksHtml = static::_DevHelper_formatMeasuredTime(DevHelper_XenForo_CodeEvent::$measuredTime['callbacks']);

        $html = sprintf('%s%s%s', $eventsHtml, $callbacksHtml, $html);

        return $html;
    }

    protected static function _DevHelper_formatMeasuredTime(array $data)
    {
        $rows = array();

        foreach ($data as $label => $oneData) {
            $rows[] = sprintf(
                '<tr><td>%s</td><td>%d</td><td>%.6f</td></tr>',
                $label,
                $oneData['count'],
                $oneData['elapsed']
            );
        }

        return sprintf('<table><tbody>%s</tbody></table>', implode('', $rows));
    }
}

eval('abstract class XenForo_Debug extends DevHelper_XenForo_Debug {}');

if (false) {
    class _XenForo_Debug extends XenForo_Debug
    {
    }
}
