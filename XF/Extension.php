<?php

namespace DevHelper\XF;

class Extension extends DevHelperCP_Extension
{
    private $enableSelf = false;

    public function __construct(array $listeners = [], array $classExtensions = [])
    {
        parent::__construct($listeners, $classExtensions);

        $xfDevOutput = 'XF\DevelopmentOutput';
        if (empty($classExtensions[$xfDevOutput]) ||
            !in_array('DevHelper\\' . $xfDevOutput, $classExtensions[$xfDevOutput], true)
        ) {
            $this->enableSelf = true;
        }
    }

    public function extendClass($class, $fakeBaseClass = null)
    {
        if ($this->enableSelf) {
            $this->enableSelf = false;

            $addOnManager = \XF::app()->addOnManager();
            $addOn = $addOnManager->getById('DevHelper');
            if ($addOn->isInstalled()) {
                $installed = $addOn->getInstalledAddOn();
                $installed->active = true;
                $installed->save();
                die('Enabled DevHelper automatically');
            }
        }

        return parent::extendClass($class, $fakeBaseClass);
    }
}

// phpcs:disable
if (false) {
    class DevHelperCP_Extension extends \XF\Extension
    {
    }
}
