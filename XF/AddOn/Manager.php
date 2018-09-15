<?php

namespace DevHelper\XF\AddOn;

class Manager extends XFCP_Manager
{
    const CONFIG_ADDON_IDS_AUTO_ENABLE = 'addon_ids_auto_enable';

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

    /**
     * @param \XF\AddOn\AddOn $addOn
     * @return array
     */
    public function getDevHelperConfig($addOn)
    {
        $path = $this->getDevHelperConfigJsonPath($addOn);
        if (!file_exists($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }

        $config = json_decode($json, true);
        if (!is_array($config)) {
            return [];
        }

        return $config;
    }

    /**
     * @param \XF\AddOn\AddOn $addOn
     * @return string
     */
    public function getDevHelperConfigJsonPath($addOn)
    {
        return $addOn->getFilesDirectory() . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'config.json';
    }
}

// phpcs:disable
if (false) {
    class XFCP_Manager extends \XF\AddOn\Manager
    {
    }
}
