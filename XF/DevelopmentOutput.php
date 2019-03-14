<?php

namespace DevHelper\XF;

class DevelopmentOutput extends XFCP_DevelopmentOutput
{
    /**
     * @var int
     */
    private $returnEnabledInsteadOfAvailableAddOnIds = 0;

    /**
     * @return array
     */
    public function getAvailableAddOnIds()
    {
        if ($this->returnEnabledInsteadOfAvailableAddOnIds > 0) {
            $addOns = \XF::app()->container('addon.cache');
            return array_keys($addOns);
        }

        return parent::getAvailableAddOnIds();
    }

    /**
     * @param mixed $typeDir
     * @return void
     */
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
