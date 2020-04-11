<?php

namespace DevHelper\XF\Service\AddOn;

use XF\Util\File;

class JsMinifier extends XFCP_JsMinifier
{
    protected function request($getErrors = false)
    {
        if (\is_executable('/usr/local/bin/uglifyjs')) {
            $jsContents = $this->options['js_code'];
            $tempFile = File::getTempFile();
            \file_put_contents($tempFile, $jsContents);
            $output = File::getTempFile();

            $cmd = '/usr/local/bin/uglifyjs '
                . escapeshellarg($tempFile)
                . ' -o ' . escapeshellarg($output)
                . ' -c -m';
            \exec($cmd);

            $minified = \trim(\file_get_contents($output));
            if (\strlen($minified) === 0) {
                \XF::logError('Failed to minify JS. CMD=' . $cmd);
            } else {
                return [
                    'compiledCode' => $minified
                ];
            }
        }

        return parent::request($getErrors);
    }
}
