<?php

class DevHelper_Generator_Code_XenForoConfig
{
    public static function updateConfig($key, $value)
    {
        /** @var XenForo_Application $app */
        $app = XenForo_Application::getInstance();
        $path = $app->getRootDir() . '/library/config.php';
        $originalContents = file_get_contents($path);

        $keyParts = explode('.', $key);
        $varNamePattern = '#(\n|^)(\\$config';
        foreach ($keyParts as $i => $keyPart) {
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

        if (count($candidates) > 1) {
            throw new XenForo_Exception(sprintf('count($candidates) = %d', count($candidates)));
        }

        $phpStatement = sprintf(
            '$config["%s"] = %s;',
            implode('"]["', $keyParts),
            var_export($value, true)
        );
        if (count($candidates) === 1) {
            $matches = reset($candidates);

            $replacement = $matches[1][0] . $phpStatement . $matches[5][0];
            $contents = substr_replace($originalContents, $replacement, $matches[0][1], strlen($matches[0][0]));
        } else {
            $contents = $originalContents . "\n\n" . $phpStatement;
        }

        DevHelper_Generator_File::writeFile($path, $contents, true, false);
    }
}
