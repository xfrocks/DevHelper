<?php

namespace DevHelper;

class Autoloader
{
    protected static $_instance;

    protected $_setup = false;

    protected function __construct()
    {
    }

    public function setupAutoloader()
    {
        if ($this->_setup) {
            return;
        }

        /** @var \Composer\Autoload\ClassLoader $autoLoader */
        /** @noinspection PhpIncludeInspection */
        $autoLoader = require('/var/www/html/xenforo/src/vendor/autoload.php');

        list(, $addOnPaths) = Router::getLocatePaths();
        foreach ($addOnPaths as $addOnPathSuffix => $addOnPath) {
            $globPattern = $addOnPath . '/src/addons/*';
            $rootPaths = glob($globPattern);
            foreach ($rootPaths as $rootPath) {
                $rootNamespace = basename($rootPath);
                $autoLoader->setPsr4($rootNamespace . '\\', $rootPath);
            }

            if (count($rootPaths) === 0) {
                $rootNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $addOnPathSuffix);
                $autoLoader->setPsr4($rootNamespace . '\\', $addOnPath);
            }
        }

        spl_autoload_register(array($this, 'autoload'), true, true);

        $this->_setup = true;
    }

    public function autoload($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return true;
        }

        if ($class == 'utf8_entity_decoder') {
            return false;
        }

        if (substr($class, 0, 5) == 'XFCP_') {
            return false;
        }

        file_put_contents('/var/www/html/xenforo/internal_data/autoload.log', $class . "\n", FILE_APPEND);
        switch ($class) {
            case 'XF\Extension':
                $original = file_get_contents('/var/www/html/xenforo/src/XF/Extension.php');
                $renamed = str_replace('class Extension', 'class _Extension', $original);
                eval(substr($renamed, strlen('<?php')));

                require(__DIR__ . '/XF/Extension.php');

                class_alias('\DevHelper\XF\Extension', 'XF\\' . 'Extension');
                break;
        }

        return false;
    }

    final public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}