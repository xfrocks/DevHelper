<?php

namespace DevHelper\XF;

use DevHelper\StreamWrapper;

class Extension extends \XF\_Extension
{
    public function __construct(array $listeners = [], array $classExtensions = [])
    {
        parent::__construct($listeners, $classExtensions);

        stream_wrapper_register(StreamWrapper::PROTOCOL, StreamWrapper::class);
        XF::setSourceDirectory(StreamWrapper::PROTOCOL . '://src');

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
