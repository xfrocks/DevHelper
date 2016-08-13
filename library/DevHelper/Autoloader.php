<?php

class DevHelper_Autoloader extends XenForo_Autoloader
{
    protected static $_DevHelper_isSetup = null;

    public static function throwIfNotSetup()
    {
        if (empty($_SERVER['SCRIPT_FILENAME'])) {
            throw new XenForo_Exception('Cannot get value for $_SERVER[\'SCRIPT_FILENAME\']');
        }

        /** @var XenForo_Application $app */
        $app = XenForo_Application::getInstance();
        $root = $app->getRootDir();
        $candidates = array(
            file_get_contents($root . '/index.php'),
            file_get_contents($root . '/admin.php'),
        );

        if (empty($_SERVER['DEVHELPER_ROUTER_PHP'])
            && in_array(file_get_contents($_SERVER['SCRIPT_FILENAME']), $candidates, true)
            && !self::$_DevHelper_isSetup
        ) {
            throw new XenForo_Exception('DevHelper_Autoloader must be used instead of XenForo_Autoloader');
        }
    }

    public static final function getDevHelperInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function autoloaderClassToFile($class)
    {
        static $classes = array(
            'XenForo_CodeEvent',
            'XenForo_Template_Abstract',
            'XenForo_ViewRenderer_Json',
        );
        if (in_array($class, $classes, true)) {
            $class = 'DevHelper_' . $class;
        }

        $classFile = parent::autoloaderClassToFile($class);
        $classFile = $this->_locateClassFile($classFile);

        $strPos = 0;
        if (substr($class, 0, 9) !== 'DevHelper') {
            $strPos = strpos($class, 'ShippableHelper_');
        }
        if ($strPos > 0) {
            // a helper class is being called, check its version vs. ours
            $classVersionId = 0;
            if (file_exists($classFile)) {
                $classContents = file_get_contents($classFile);

                $classVersionId = DevHelper_Helper_ShippableHelper::getVersionId($class, $classFile, $classContents);
                if ($classVersionId === false) {
                    die('Add-on class version could not be detected: ' . $classFile);
                }
            }

            $oursClass = 'DevHelper_Helper_' . substr($class, $strPos);
            $oursFile = parent::autoloaderClassToFile($oursClass);
            $oursFile = $this->_locateClassFile($oursFile);
            if (file_exists($oursFile)) {
                $oursContents = file_get_contents($oursFile);

                $oursVersionId = DevHelper_Helper_ShippableHelper::getVersionId($oursClass, $oursFile, $oursContents);
                if ($oursVersionId === false) {
                    die('DevHelper class version could not be detected: ' . $oursFile);
                }
            } else {
                die('DevHelper file could not be found: ' . $oursFile);
            }

            if ($classVersionId < $oursVersionId) {
                if (!DevHelper_Helper_ShippableHelper::update($class, $classFile, $oursClass, $oursContents)) {
                    die('Add-on file could not be updated: ' . $classFile);
                }

                // die('Add-on file has been updated: ' . $classFile);
            }
        }

        return $classFile;
    }

    protected function _locateClassFile($classFile)
    {
        if (!empty($_SERVER['DEVHELPER_ROUTER_PHP'])) {
            return DevHelper_Router::locate('library', $classFile);
        }

        return $classFile;
    }

    protected function _setupAutoloader()
    {
        parent::_setupAutoloader();

        self::$_DevHelper_isSetup = true;
    }
}