<?php

class DevHelper_Helper_Php
{
    public static function extractString($php, &$offset)
    {
        $operator = substr($php, $offset, 1);
        $phpLength = strlen($php);

        switch ($operator) {
            case '"':
            case "'":
                $innerOffset = $offset + 1;
                $string = '';
                while (true) {
                    $next = substr($php, $innerOffset, 1);
                    if ($next === '\\') {
                        // found escaped character
                        $innerOffset++;
                        if ($innerOffset >= $phpLength) {
                            // unclosed string
                            return false;
                        }
                        $next = substr($php, $innerOffset, 1);
                    } elseif ($next === $operator) {
                        // found another instance of the operator, end of string
                        $offset = $innerOffset;
                        return $string;
                    }

                    $string .= $next;
                    $innerOffset++;
                    if ($innerOffset >= $phpLength) {
                        // unclosed string
                        return false;
                    }
                }
                break;
        }

        return false;
    }

}
