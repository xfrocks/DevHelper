<?php

namespace DevHelper\XF\AddOn;

class Manager extends XFCP_Manager
{
    public function getAllAddOns()
    {
        $addOns = parent::getAllAddOns();

        $filtered = [];
        foreach ($addOns as $addOnId => $addOn) {
            if ($addOn->hasMissingFiles()) {
                continue;
            }

            $filtered[$addOnId] = $addOn;
        }

        return $filtered;
    }
}

// phpcs:disable
if (false) {
    class XFCP_Manager extends \XF\AddOn\Manager
    {
    }
}
