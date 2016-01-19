<?php

class DevHelper_XenForo_DataWriter_CodeEventListener extends XFCP_DevHelper_XenForo_DataWriter_CodeEventListener
{
    protected static $_DevHelper_generatedCallbacks = array();

    public function error($error, $errorKey = false, $specificError = true)
    {
        if ($errorKey === 'callback_method') {
            foreach (self::$_DevHelper_generatedCallbacks as $callback) {
                if ($callback[0] === $this->get('callback_class')
                    && $callback[1] === $this->get('callback_method')
                ) {
                    // triggering error for our generated method
                    // this may happen if we modified an existing listener which had already been loaded to memory
                    // ignore it...
                    return;
                }
            }
        }

        parent::error($error, $errorKey, $specificError);
    }

    public static function DevHelper_markAsGeneratedCallback($clazz, $method)
    {
        self::$_DevHelper_generatedCallbacks[] = array($clazz, $method);
    }

}