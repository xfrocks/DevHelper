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

    public static function extractMethods($php)
    {
        $methods = array();

        $offset = 0;
        while (true) {
            if (preg_match(
                '#(public|protected|private|static|\s)*'
                . 'function\s+(?<method>[a-zA-Z0-9_]+)\s*\([^\)]*\)\s*{#',
                $php,
                $matches,
                PREG_OFFSET_CAPTURE,
                $offset
            )) {
                $methods[] = $matches['method'][0];
                $offset = $matches[0][1] + strlen($matches[0][0]);
            } else {
                break;
            }
        }

        return $methods;
    }

    public static function appendMethod($php, $methodCode)
    {
        $lines = explode("\n", $php);
        $backUp = array();

        while (true) {
            $lastLine = array_pop($lines);
            $pos = strrpos($lastLine, '}');
            if ($pos === false) {
                $backUp[] = $lastLine;
                continue;
            }

            $prev = substr($lastLine, 0, $pos);
            $lines[] = $prev;

            $indent = '    ';
            if (preg_match('#^(?<indent>\s+)([^\s]|$)#', $prev, $matches)) {
                $indent .= $matches['indent'];
            }

            $methodCodeLines = explode("\n", $methodCode);
            foreach ($methodCodeLines as $methodCodeLine) {
                $lines[] = $indent . $methodCodeLine;
            }

            $lines[] = substr($lastLine, $pos);
            break;
        }

        $contents = implode("\n", $lines);
        if (!empty($backUp)) {
            $contents .= "\n" . implode("\n", array_reverse($backUp));
        }
        return $contents;
    }
}
