<?php

namespace DevHelper\XF;

class DevelopmentOutput extends XFCP_DevelopmentOutput
{
    private $returnEnabledInsteadOfAvailableAddOnIds = 0;

    public function getAvailableAddOnIds()
    {
        if ($this->returnEnabledInsteadOfAvailableAddOnIds > 0) {
            $addOns = \XF::app()->container('addon.cache');
            return array_keys($addOns);
        }

        return parent::getAvailableAddOnIds();
    }

    protected function loadTypeMetadata($typeDir)
    {
        $this->returnEnabledInsteadOfAvailableAddOnIds++;

        parent::loadTypeMetadata($typeDir);

        $this->returnEnabledInsteadOfAvailableAddOnIds--;
    }
}

// phpcs:disable
if (false) {
    class XFCP_DevelopmentOutput extends \XF\DevelopmentOutput
    {
    }
}
