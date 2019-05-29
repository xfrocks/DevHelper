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
            $addOnIds = array_keys($addOns);
            $addOnIds = array_filter($addOnIds, function ($id) {
                return $id !== 'XF';
            });
            return $addOnIds;
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
