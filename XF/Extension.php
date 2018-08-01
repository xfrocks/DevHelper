<?php

namespace DevHelper\XF;

class Extension extends DevHelperCP_Extension
{
    public function __construct(array $listeners = [], array $classExtensions = [])
    {
        foreach ([
                     'XF\AddOn\Manager',
                     'XF\DevelopmentOutput',
                     'XF\Entity\ClassExtension',
                 ] as $targetClass) {
            if (!isset($classExtensions[$targetClass])) {
                $classExtensions[$targetClass] = [];
            }

            $classExtensions[$targetClass][] = 'DevHelper\\' . $targetClass;
        }

        parent::__construct($listeners, $classExtensions);
    }

    public function getClassExtensionsForDevHelper()
    {
        return $this->classExtensions;
    }
}

// phpcs:disable
if (false) {
    class DevHelperCP_Extension extends \XF\Extension
    {
    }
}
