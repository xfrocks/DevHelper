<?php

namespace DevHelper\XF;

class XF extends \XF
{
    public static function setSourceDirectory($sourceDirectory)
    {
        \XF::$sourceDirectory = $sourceDirectory;
    }
}