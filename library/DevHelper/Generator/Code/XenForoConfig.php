<?php

class DevHelper_Generator_Code_XenForoConfig
{
    public static function updateConfig($key, $value)
    {
        /** @var XenForo_Application $app */
        $app = XenForo_Application::getInstance();
        $path = $app->getRootDir() . '/library/config.php';
        $originalContents = file_get_contents($path);

        $varNamePattern = '#(\n|^)(?<' . 'varName>\\$config';
        foreach (explode('.', $key) as $i => $keyPart) {
            // try to match the quote
            $varNamePattern .= '\\[([\'"]?)'
                // then the key
                . preg_quote($keyPart, '#')
                // then match the previously matched quote
                . '\\' . ($i + 3) . '\\]';
        }
        $varNamePattern .= ').+(\n|$)#';

        $candidates = array();
        $offset = 0;
        while (true) {
            if (!preg_match($varNamePattern, $originalContents, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                break;
            }

            $offset = $matches[0][1] + strlen($matches[0][0]);
            $candidates[] = $matches;
        }

        if (count($candidates) !== 1) {
            XenForo_Helper_File::log(__METHOD__, sprintf('count($candidates) = %d', count($candidates)));
            return;
        }

        $matches = reset($candidates);

        $replacement = $matches[1][0]
            . $matches['varName'][0]
            . ' = ' . var_export($value, true) . ';'
            . $matches[5][0];
        $contents = substr_replace($originalContents, $replacement, $matches[0][1], strlen($matches[0][0]));

        DevHelper_Generator_File::writeFile($path, $contents, true, false);
    }
}