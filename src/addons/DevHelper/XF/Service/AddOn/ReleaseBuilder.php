<?php

namespace DevHelper\XF\Service\AddOn;

use DevHelper\StreamWrapper;
use XF\Util\File;

class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    public function finalizeRelease()
    {
        $oopsPath = $this->buildRoot . '/oops';
        File::createDirectory($oopsPath, false);
        $this->buildRoot = $oopsPath;

        parent::finalizeRelease();
    }

    protected function getExcludedDirectories()
    {
        $dirs = parent::getExcludedDirectories();

        $dirs[] = '.git';

        return $dirs;
    }
}

if (false) {
    class XFCP_ReleaseBuilder extends \XF\Service\AddOn\ReleaseBuilder
    {
    }
}