<?php

/**
 * Class DevHelper_Helper_ShippableHelper_S3
 * @version 2
 */
class DevHelper_Helper_ShippableHelper_S3 extends Zend_Service_Amazon_S3
{
    protected $_region = 'us-east-1';

    public function __construct($accessKey = null, $secretKey = null, $region = null)
    {
        parent::__construct($accessKey, $secretKey, $region);

        $this->_region = $region;

        switch ($region) {
            case 'us-east-1':
                // default endpoint is correct for this region
                break;
            default:
                $this->setEndpoint(sprintf('http://s3-%s.amazonaws.com', $region));
        }
    }

    public function _makeRequest($method, $path = '', $params = null, $headers = array(), $data = null)
    {
        if (empty($params)) {
            $params = array();
        }

        if (!is_array($headers)) {
            $headers = array($headers);
        }

        if (isset($headers['Content-MD5'])) {
            unset($headers['Content-MD5']);
        }

        if (is_resource($data)) {
            throw new Zend_Service_Amazon_S3_Exception("No support for stream data");
        }
        $data = strval($data);
        $headers['x-amz-content-sha256'] = Zend_Crypt::hash('sha256', $data);
        $headers['x-amz-date'] = sprintf(
            '%sT%sZ',
            gmdate('Ymd', XenForo_Application::$time),
            gmdate('His', XenForo_Application::$time)
        );
        $headers['Host'] = parse_url($this->_endpoint, PHP_URL_HOST);

        $retryCount = 0;

        // build the end point (with path)
        $endpoint = clone($this->_endpoint);
        $path = '/' . $path;
        $endpoint->setPath($path);

        $this->addSignatureAws4($method, $path, $params, $headers);

        $client = self::getHttpClient();

        $client->resetParameters();
        $client->setUri($endpoint);
        $client->setAuth(false);
        $client->setHeaders($headers);

        if (is_array($params)) {
            foreach ($params as $name => $value) {
                $client->setParameterGet($name, $value);
            }
        }

        if (($method == 'PUT') && ($data !== null)) {
            if (!isset($headers['Content-type'])) {
                $headers['Content-type'] = self::getMimeType($path);
            }
            $client->setRawData($data, $headers['Content-type']);
        }

        do {
            $retry = false;

            $response = $client->request($method);
            $responseCode = $response->getStatus();

            if (XenForo_Application::debugMode() || $responseCode != 200) {
                XenForo_Helper_File::log(__METHOD__, sprintf(
                    "%s %s %s -> %d %s\n\n",
                    $method,
                    $path,
                    var_export($headers, true),
                    $responseCode,
                    $responseCode != 200
                        ? $response->getBody()
                        : sprintf('Body(length=%d)', strlen($response->getBody()))
                ));
            }

            // some 5xx errors are expected, so retry automatically
            if ($responseCode >= 500 && $responseCode < 600 && $retryCount <= 5) {
                $retry = true;
                $retryCount++;
                sleep($retryCount / 4 * $retryCount);
            } elseif ($responseCode == 307) {
                // need to redirect, new S3 endpoint given
                // this should never happen as Zend_Http_Client will redirect automatically
            } elseif ($responseCode == 100) {
                // 'OK to Continue';
            }
        } while ($retry);

        return $response;
    }

    protected function addSignatureAws4($method, $path, array $params, array &$headers)
    {
        // http://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-header-based-auth.html
        // task 1: create a canonical request
        $canonicalQueryArray = array();
        if (!empty($params)) {
            ksort($params);
            foreach ($params as $paramKey => $paramValue) {
                $canonicalQueryArray[] = sprintf('%s=%s', urlencode($paramKey), urlencode($paramValue));
            }
        }
        $canonicalQueryString = implode('&', $canonicalQueryArray);

        $canonicalHeaders = '';
        $signedHeadersArray = array();
        $hashedPayload = '';
        $timestamp = '';
        $headerKeys = array_combine(array_map('strtolower', array_keys($headers)), array_keys($headers));
        ksort($headerKeys);
        foreach ($headerKeys as $headerKeyLower => $headerKey) {
            $canonicalHeaders .= sprintf("%s:%s\n", $headerKeyLower, $headers[$headerKey]);
            $signedHeadersArray[] = $headerKeyLower;

            switch ($headerKey) {
                case 'x-amz-content-sha256':
                    $hashedPayload = $headers[$headerKey];
                    break;
                case 'x-amz-date':
                    $timestamp = $headers[$headerKey];
                    break;
            }
        }
        $signedHeadersString = implode(';', $signedHeadersArray);

        $canonicalRequest = sprintf(
            "%s\n%s\n%s\n%s\n%s\n%s",
            $method,
            $path,
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeadersString,
            $hashedPayload
        );

        // task 2: create a string to sign
        $date = substr($timestamp, 0, strpos($timestamp, 'T'));
        $scope = sprintf(
            '%s/%s/s3/aws4_request',
            $date,
            $this->_region
        );
        $stringToSign = sprintf(
            "AWS4-HMAC-SHA256\n%s\n%s\n%s",
            $timestamp,
            $scope,
            Zend_Crypt::hash('sha256', $canonicalRequest)
        );

        // task 3: calculate signature
        $dateKey = Zend_Crypt_Hmac::compute('AWS4' . $this->_getSecretKey(), 'sha256',
            $date, Zend_Crypt_Hmac::BINARY);
        $dateRegionKey = Zend_Crypt_Hmac::compute($dateKey,
            'sha256', $this->_region, Zend_Crypt_Hmac::BINARY);
        $dateRegionServiceKey = Zend_Crypt_Hmac::compute($dateRegionKey,
            'sha256', 's3', Zend_Crypt_Hmac::BINARY);
        $signingKey = Zend_Crypt_Hmac::compute($dateRegionServiceKey,
            'sha256', 'aws4_request', Zend_Crypt_Hmac::BINARY);

        $signature = Zend_Crypt_Hmac::compute($signingKey, 'sha256', $stringToSign);
        $headers['Authorization'] = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s,SignedHeaders=%s,Signature=%s',
            $this->_getAccessKey(),
            $scope,
            $signedHeadersString,
            $signature
        );

        return $signature;
    }

}