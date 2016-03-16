<?php

/**
 * Class DevHelper_Helper_ShippableHelper_String
 * @version 1
 */
class DevHelper_Helper_ShippableHelper_String
{
    public static function formatBytes($bytes)
    {
        static $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $unitKey = 0;

        while ($bytes > 1024 && $unitKey < count($units) - 1) {
            $bytes /= 1024;
            $unitKey++;
        }

        // get float with one decimal place
        // truncate if it's insignificant though
        $value = sprintf('%.1f', $bytes);
        $value = preg_replace('#\.0$#', '', $value);

        return sprintf('%s %s', $value, $units[$unitKey]);
    }
}