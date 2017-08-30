<?php

namespace DevHelper\XF\AddOn;

use DevHelper\Router;

class Manager extends XFCP_Manager
{
    protected function getAllJsonInfo()
    {
        Router::locateReset();
        return parent::getAllJsonInfo();
    }

    public function getAddOnPath($addOnId)
    {
        $addOnPath = parent::getAddOnPath($addOnId);
        return Router::locateCached($addOnPath);
    }

    protected function loadAddOnClass($addOnOrId, array $jsonInfo = null)
    {
        $addOn = parent::loadAddOnClass($addOnOrId, $jsonInfo);

        $class = \XF::app()->extendClass('XF\AddOn\AddOn');

        return new $class($addOnOrId ?: $addOn->getAddOnId());
    }
}

if (false) {
    class XFCP_Manager extends \XF\AddOn\Manager
    {
    }
}
