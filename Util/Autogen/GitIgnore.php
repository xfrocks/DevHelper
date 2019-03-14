<?php

namespace DevHelper\Util\Autogen;

class GitIgnore
{
    /**
     * @param array $lines
     * @return bool
     */
    public static function sort(array &$lines)
    {
        return usort($lines, function ($line1, $line2) {
            $line1 = preg_replace('#[!\*]#', '', $line1);
            $line2 = preg_replace('#[!\*]#', '', $line2);

            return strcmp($line1, $line2);
        });
    }
}
