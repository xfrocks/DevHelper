<?php

/**
 * Class DevHelper_Helper_ShippableHelper_ImageSize
 * @version 3
 */
class DevHelper_Helper_ShippableHelper_ImageSize
{
    public static function calculate($uri, $ttl = 300)
    {
        $cacheId = __CLASS__ . md5($uri);
        $cache = ($ttl > 0 ? XenForo_Application::getCache() : null);

        if ($cache) {
            $cachedData = $cache->load($cacheId);
            if (is_string($cachedData)) {
                $cachedData = unserialize($cachedData);
            }
            if (!is_array($cachedData)) {
                $cachedData = array();
            }
            if (!empty($cachedData['uri'])
                && $cachedData['uri'] === $uri
                && XenForo_Application::$time - $cachedData['timestamp'] < $ttl
            ) {
                if (XenForo_Application::debugMode()) {
                    XenForo_Helper_File::log(__CLASS__, sprintf('$uri=%s; CACHE HIT', $uri));
                }
                return $cachedData;
            }
        }

        $data = self::_calculate($uri);
        if (empty($data['width'])
            || empty($data['height'])
        ) {
            $absoluteUri = XenForo_Link::convertUriToAbsoluteUri($uri, true);
            if ($absoluteUri != $uri) {
                $data = self::_calculate($absoluteUri);
                if (isset($data['uri'])) {
                    // use the original uri for cache tracking
                    $data['uri'] = $uri;
                }
            }
        }

        if ($cache) {
            $cache->save(serialize($data), $cacheId, array(), $ttl);
        }

        return $data;
    }

    protected static function _calculate($uri)
    {
        /** @var self $instance */
        static $instance = null;

        $startTime = microtime(true);

        if (preg_match('#^' . preg_quote(XenForo_Application::getOptions()->get('boardUrl'), '#')
            . '.+attachments/(.+\.)*(?<id>\d+)/$#', $uri, $matches)) {
            return self::_calculateForAttachment($uri, $matches['id']);
        }

        if ($instance === null) {
            $instance = new self($uri);
        } else {
            $instance->close();
            $instance->load($uri);
        }

        $size = $instance->getSize();

        if (XenForo_Application::debugMode()) {
            $elapsedTime = microtime(true) - $startTime;
            XenForo_Helper_File::log(__CLASS__, sprintf('$uri=%s; $elapsedTime=%.6f', $uri, $elapsedTime));
        }

        return array(
            'uri' => $uri,
            'width' => ($size ? $size[0] : ''),
            'height' => ($size ? $size[1] : ''),
            'timestamp' => time(),
        );
    }

    protected static function _calculateForAttachment($uri, $attachmentId)
    {
        $startTime = microtime(true);

        /** @var XenForo_Model_Attachment $attachmentModel */
        static $attachmentModel = null;
        static $attachments = array();

        if ($attachmentModel === null) {
            $attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');
        }

        if (!isset($attachments[$attachmentId])) {
            $attachments[$attachmentId] = $attachmentModel->getAttachmentById($attachmentId);
        }


        if (XenForo_Application::debugMode()) {
            $elapsedTime = microtime(true) - $startTime;
            XenForo_Helper_File::log(__CLASS__, sprintf('$attachmentId=%d; $elapsedTime=%.6f',
                $attachmentId, $elapsedTime));
        }

        return array(
            'uri' => $uri,
            'attachmentId' => $attachmentId,
            'width' => ($attachments[$attachmentId] ? $attachments[$attachmentId]['width'] : ''),
            'height' => ($attachments[$attachmentId] ? $attachments[$attachmentId]['height'] : ''),
            'timestamp' => time(),
        );
    }

    // https://github.com/tommoor/fastimage/commit/7bf53fcfebb5bc04b78a8cf23862778256de2241
    private $pos = 0;
    private $str;
    private $type;
    private $handle;

    private function __construct($uri = null)
    {
        if ($uri) {
            $this->load($uri);
        }
    }

    private function load($uri)
    {
        if ($this->handle) {
            $this->close();
        }

        $context = null;
        $host = parse_url($uri, PHP_URL_HOST);
        if ($host) {
            $httpContext = array('timeout' => 2.0);
            $httpContext['header'] = "Referer: http://$host\r\n";

            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                $httpContext['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            } else {
                $httpContext['user_agent'] = 'Mozilla/4.0 (MSIE 6.0; Windows NT 5.0)';
            }

            $context = stream_context_create(array('http' => $httpContext));
        }

        if ($context != null) {
            $this->handle = @fopen($uri, 'rb', null, $context);
        } else {
            $this->handle = @fopen($uri, 'rb');
        }
    }

    private function close()
    {
        if ($this->handle) {
            if (is_resource($this->handle)) {
                fclose($this->handle);
            }

            $this->handle = null;
            $this->type = null;
            $this->str = null;
        }
    }

    private function getSize()
    {
        $this->pos = 0;
        if ($this->getType()) {
            return array_values($this->parseSize());
        }

        return false;
    }

    private function getType()
    {
        $this->pos = 0;

        if (!$this->type) {
            switch ($this->getChars(2)) {
                case 'BM':
                    return $this->type = 'bmp';
                case 'GI':
                    return $this->type = 'gif';
                case chr(0xFF) . chr(0xd8):
                    return $this->type = 'jpeg';
                case chr(0x89) . 'P':
                    return $this->type = 'png';
                case 'II':
                case 'MM':
                    return $this->type = 'tiff';
                case 'RI':
                    return $this->type = 'webp';
                default:
                    return false;
            }
        }

        return $this->type;
    }

    private function parseSize()
    {
        $this->pos = 0;

        $method = 'parseSizeFor' . strtoupper($this->type);
        if (is_callable(array($this, $method))) {
            return call_user_func(array($this, $method));
        }

        return null;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function parseSizeForPNG()
    {
        $chars = $this->getChars(25);

        return unpack('N*', substr($chars, 16, 8));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function parseSizeForGIF()
    {
        $chars = $this->getChars(11);

        return unpack('S*', substr($chars, 6, 4));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function parseSizeForBMP()
    {
        $chars = $this->getChars(29);
        $chars = substr($chars, 14, 14);
        $type = unpack('C', $chars);

        return (reset($type) == 40) ? unpack('L*', substr($chars, 4)) : unpack('L*', substr($chars, 4, 8));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function parseSizeForJPEG()
    {
        $state = null;
        $skip = 0;

        while (true) {
            switch ($state) {
                default:
                    $this->getChars(2);
                    $state = 'started';
                    break;

                case 'started':
                    $b = $this->getByte();
                    if ($b === false) {
                        return false;
                    }

                    $state = $b == 0xFF ? 'sof' : 'started';
                    break;

                case 'sof':
                    $b = $this->getByte();
                    if (in_array($b, range(0xe0, 0xef))) {
                        $state = 'skipframe';
                    } elseif (in_array($b,
                        array_merge(range(0xC0, 0xC3), range(0xC5, 0xC7), range(0xC9, 0xCB), range(0xCD, 0xCF)))) {
                        $state = 'readsize';
                    } elseif ($b == 0xFF) {
                        $state = 'sof';
                    } else {
                        $state = 'skipframe';
                    }
                    break;

                case 'skipframe':
                    $skip = $this->readInt($this->getChars(2)) - 2;
                    $state = 'doskip';
                    break;

                case 'doskip':
                    $this->getChars($skip);
                    $state = 'started';
                    break;

                case 'readsize':
                    $c = $this->getChars(7);

                    return array($this->readInt(substr($c, 5, 2)), $this->readInt(substr($c, 3, 2)));
            }
        }

        return array(0, 0);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function parseSizeForWEBP()
    {
        $chars = $this->getChars(30);
        $result = unpack('C12/S9', $chars);

        return array($result['8'], $result['9']);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function parseSizeForTIFF()
    {
        $byteOrder = $this->getChars(2);
        switch ($byteOrder) {
            case 'II':
                $short = 'v';
                $long = 'V';
                break;
            case 'MM':
                $short = 'n';
                $long = 'N';
                break;
            default:
                return false;
                break;
        }

        $this->getChars(2);
        $offset = current(unpack($long, $this->getChars(4)));

        $this->getChars($offset - 8);
        $tagCount = current(unpack($short, $this->getChars(2)));

        for ($i = $tagCount; $i > 0; $i--) {
            $type = current(unpack($short, $this->getChars(2)));
            $this->getChars(6);
            $data = current(unpack($short, $this->getChars(2)));
            switch ($type) {
                case 0x0100:
                    $width = $data;
                    break;
                case 0x0101:
                    $height = $data;
                    break;
                case 0x0112:
                    $orientation = $data;
                    break;
            }
            if (isset($width) && isset($height) && isset($orientation)) {
                if ($orientation >= 5) {
                    return array($height, $width);
                }
                return array($width, $height);
            }
            $this->getChars(2);
        }

        return array(0, 0);
    }

    private function getChars($n)
    {
        if (!is_resource($this->handle)) {
            return false;
        }

        $response = null;

        // do we need more data?
        if ($this->pos + $n - 1 >= strlen($this->str)) {
            $end = ($this->pos + $n);

            while (strlen($this->str) < $end && $response !== false) {
                // read more from the file handle
                $need = $end - ftell($this->handle);

                if ($response = fread($this->handle, $need)) {
                    $this->str .= $response;
                } else {
                    return false;
                }
            }
        }

        $result = substr($this->str, $this->pos, $n);
        $this->pos += $n;

        if (function_exists('mb_convert_encoding')) {
            $result = mb_convert_encoding($result, '8BIT', '7BIT');
        }

        return $result;
    }

    private function getByte()
    {
        $c = $this->getChars(1);
        $b = unpack('C', $c);

        return reset($b);
    }

    private function readInt($str)
    {
        $size = unpack('C*', $str);

        return ($size[1] << 8) + $size[2];
    }

    public function __destruct()
    {
        $this->close();
    }
}