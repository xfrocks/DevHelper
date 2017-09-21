<?php

namespace DevHelper;

class Listener
{
    /**
     * @param \GuzzleHttp\Client $client
     * @see \XF\SubContainer\Http::applyDefaultClientConfig()
     */
    public static function httpClientConfig(&$client)
    {
        // this needs to be done because we have changed system $sourceDirectory to our stream wrapper
        $client->setDefaultOption('verify', '/var/www/html/xenforo/src/XF/Http/ca-bundle.crt');
    }
}