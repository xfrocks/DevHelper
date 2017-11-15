<?php

namespace DevHelper\XF;

use DevHelper\StreamWrapper;

class Extension extends \XF\_Extension
{
    /**
     * Extension constructor.
     * @param array $listeners
     * @param array $classExtensions
     *
     * @see \DevHelper\Listener::httpClientConfig()
     * @see \DevHelper\XF\AddOn\AddOn
     * @see \DevHelper\XF\AddOn\Manager
     * @see \DevHelper\XF\Service\AddOn\ReleaseBuilder
     */
    public function __construct(array $listeners = [], array $classExtensions = [])
    {
        parent::__construct($listeners, $classExtensions);

        $this->initDevHelper();
    }

    private function initDevHelper()
    {
        static $initOk = false;
        if ($initOk) {
            return;
        }
        $initOk = true;

        require(dirname(__DIR__) . '/StreamWrapper.php');
        stream_wrapper_register(StreamWrapper::PROTOCOL, StreamWrapper::class);
        XF::setSourceDirectory(StreamWrapper::PROTOCOL . '://src');

        $this->addListener('http_client_config', ['DevHelper\\Listener', 'httpClientConfig']);

        $classes = [
            'XF\AddOn\AddOn',
            'XF\AddOn\Manager',
            'XF\Service\AddOn\ReleaseBuilder',
        ];
        foreach ($classes as $class) {
            $this->addClassExtension($class, 'DevHelper\\' . $class);
        }
    }
}
