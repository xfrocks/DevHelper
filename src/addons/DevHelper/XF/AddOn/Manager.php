<?php

namespace DevHelper\XF\AddOn;

use DevHelper\Router;

class Manager extends XFCP_Manager
{
    protected function getAllJsonInfo()
    {
        $skip = true;
        if (!is_array($this->jsonInfo)) {
            $skip = false;
        }

        $available = parent::getAllJsonInfo();

        if (!$skip) {
            $addOnIds = $this->DevHelper_getAvailableAddOnIds();
            foreach ($addOnIds as $addOnId) {
                $available[$addOnId] = $this->getAddOnJsonInfo($addOnId);
            }

            $this->jsonInfo = $available;
        }

        return $available;
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

    private function DevHelper_getAvailableAddOnIds()
    {
        $addOnIds = [];

        list(, $addOnPaths) = Router::getLocatePaths();

        foreach ($addOnPaths as $addOnPath) {
            foreach (new \DirectoryIterator($addOnPath . '/src/addons') as $entry) {
                if (!$this->isValidDir($entry)) {
                    continue;
                }

                if ($this->isDirAddOnRoot($entry)) {
                    $addOnIds[] = $entry->getBasename();
                } else {
                    $vendorPrefix = $entry->getBasename();
                    foreach (new \DirectoryIterator($entry->getPathname()) as $addOnDir) {
                        if (!$this->isValidDir($addOnDir)) {
                            continue;
                        }

                        if ($this->isDirAddOnRoot($addOnDir)) {
                            $addOnIds[] = "$vendorPrefix/{$addOnDir->getBasename()}";
                        }
                    }
                }
            }
        }

        return $addOnIds;
    }
}

if (false) {
    class XFCP_Manager extends \XF\AddOn\Manager
    {
    }
}
