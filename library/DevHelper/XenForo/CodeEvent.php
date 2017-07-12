<?php

/** @var XenForo_Application $application */
$application = XenForo_Application::getInstance();
$originalContents = file_get_contents($application->getRootDir() . '/library/XenForo/CodeEvent.php');
$contents = substr($originalContents, strpos($originalContents, '<?php') + 5);
$contents = str_replace(
    'class XenForo_CodeEvent',
    'class _XenForo_CodeEvent',
    $contents
);
$contents = str_replace(
    '$return = call_user_func_array($callback, $args);',
    '$return = static::DevHelper_measureCallbackTime($event, $callback, $args);',
    $contents
);
$contents = str_replace('self::', 'static::', $contents);
eval($contents);

abstract class DevHelper_XenForo_CodeEvent extends _XenForo_CodeEvent
{
    public static function fire($event, array $args = array(), $hint = null)
    {
        if ($event === 'front_controller_pre_route') {
            $addOns = XenForo_Application::get('addOns');
            if (!isset($addOns['devHelper'])) {
                // looks like our add-on hasn't been installed (or disabled)
                /** @var XenForo_Model_AddOn $addOnModel */
                $addOnModel = XenForo_Model::create('XenForo_Model_AddOn');

                $addOn = $addOnModel->getAddOnById('devHelper');
                if (!empty($addOn)) {
                    /** @var XenForo_DataWriter_AddOn $addOnDw */
                    $addOnDw = XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
                    $addOnDw->setExistingData($addOn);
                    $addOnDw->set('active', 1);
                    $addOnDw->save();

                    $message = 'Re-enabled DevHelper add-on.';
                } else {
                    $xmlPath = 'library/DevHelper/addon-devHelper.xml';
                    if (!file_exists($xmlPath)) {
                        die(sprintf('XML path (%s) does not exist', $xmlPath));
                    }
                    $addOnModel->installAddOnXmlFromFile($xmlPath);

                    $message = 'Installed DevHelper add-on.';
                }

                /** @var XenForo_FrontController $fc */
                $fc = $args[0];
                $redirect = $fc->getRequest()->getRequestUri();

                die(sprintf(
                    '<scr' . 'ipt>alert(%s);window.location = %s;</scr' . 'ipt>',
                    json_encode($message),
                    json_encode(XenForo_Link::buildAdminLink('full:tools/run-deferred', null, array(
                        'redirect' => $redirect,
                    )))
                ));
            }
        }

        return parent::fire($event, $args, $hint);
    }

    public static $measuredTime = array('events' => array(), 'callbacks' => array());

    public static function DevHelper_measureCallbackTime($event, $callback, $args)
    {
        $startTime = microtime(true);
        $result = call_user_func_array($callback, $args);
        $elapsed = microtime(true) - $startTime;

        if (!isset(self::$measuredTime['events'][$event])) {
            self::$measuredTime['events'][$event] = array(
                'count' => 0,
                'elapsed' => 0,
            );
        }
        self::$measuredTime['events'][$event]['count']++;
        self::$measuredTime['events'][$event]['elapsed'] += $elapsed;

        $callbackSafe = $callback;
        if (!is_string($callbackSafe)) {
            $callbackSafe = XenForo_Helper_Php::safeSerialize($callback);
        }
        if (!isset(self::$measuredTime['callbacks'][$callbackSafe])) {
            self::$measuredTime['callbacks'][$callbackSafe] = array(
                'count' => 0,
                'elapsed' => 0,
            );
        }
        self::$measuredTime['callbacks'][$callbackSafe]['count']++;
        self::$measuredTime['callbacks'][$callbackSafe]['elapsed'] += $elapsed;

        return $result;
    }
}

eval('abstract class XenForo_CodeEvent extends DevHelper_XenForo_CodeEvent {}');

if (false) {
    class _XenForo_CodeEvent extends XenForo_CodeEvent
    {
    }
}
