<?php

namespace DevHelper\XF\Service\AddOn;

use XF\Util\File;

class JsMinifier extends XFCP_JsMinifier
{
    protected function request($getErrors = false)
    {
        if (\is_executable('/usr/local/bin/uglifyjs')) {
            $minifier = '/usr/local/bin/uglifyjs';
        } elseif (\is_executable('/usr/bin/uglifyjs')) {
            $minifier = '/usr/bin/uglifyjs';
        } else {
            $minifier = null;
        }

        if ($minifier !== null) {
            $jsContents = $this->options['js_code'];
            $tempFile = File::getTempFile();
            \file_put_contents($tempFile, $jsContents);
            $output = File::getTempFile();

            $cmd = $minifier . ' '
                . \escapeshellarg($tempFile)
                . ' -o ' . \escapeshellarg($output)
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
