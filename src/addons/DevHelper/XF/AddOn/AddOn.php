<?php

namespace DevHelper\XF\AddOn;

class AddOn extends XFCP_AddOn
{
    public function __construct($addOnOrId)
    {
        parent::__construct($addOnOrId);

        $addOnId = $this->prepareAddOnIdForFilename();
        $versionString = $this->prepareVersionForFilename();
        /** @noinspection PhpUndefinedFieldInspection */
        $this->buildDir = '/var/www/html/xenforo/internal_data/builds/' . $addOnId . '/' . $versionString;
        /** @noinspection PhpUndefinedFieldInspection */
        $this->releasesDir = '/var/www/html/xenforo/internal_data/releases/' . $addOnId;
    }
}

if (false) {
    class XFCP_AddOn extends \XF\AddOn\AddOn
    {
    }
}
