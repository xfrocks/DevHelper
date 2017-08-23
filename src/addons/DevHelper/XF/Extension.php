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
            'XF\AddOn\Manager'
        ];
        foreach ($classes as $class) {
            $this->addClassExtension($class, 'DevHelper\\' . $class);
        }
    }

    public function extendClass($class, $fakeBaseClass = null)
    {
        return parent::extendClass($class, $fakeBaseClass);
    }
}
