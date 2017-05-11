<?php

/**
 * Class DevHelper_Helper_ShippableHelper_ImageCore
 * @version 3
 */
class DevHelper_Helper_ShippableHelper_ImageCore
{
    public static $cropTopLeft = false;

    /**
     * @param string $path
     * @return array
     */
    public static function open($path)
    {
        $accessiblePath = self::_getAccessiblePath($path);
        $imageInfo = self::_getImageInfo($accessiblePath);

        $image = null;
        if (!empty($imageInfo['typeInt'])
            && !empty($imageInfo['width'])
            && !empty($imageInfo['height'])
            && $imageInfo['width'] * $imageInfo['height'] < XenForo_Application::getConfig()->get('maxImageResizePixelCount')
        ) {
            $image = XenForo_Image_Abstract::createFromFile($accessiblePath, $imageInfo['typeInt']);
        }

        return array(
            'image' => $image,
            'imageInfo' => $imageInfo,
        );
    }

    /**
     * @param array $data
     * @param string $path
     * @return bool
     */
    public static function save(array $data, $path)
    {
        XenForo_Helper_File::createDirectory(dirname($path), true);
        $outputPath = tempnam(XenForo_Helper_File::getTempDir(), __CLASS__);

        /** @noinspection PhpUndefinedMethodInspection */
        $data['image']->output($data['imageInfo']['typeInt'], $outputPath);
        return XenForo_Helper_File::safeRename($outputPath, $path);
    }

    /**
     * @param array $data
     * @param int $width
     * @param int $height
     * @return array()
     */
    public static function thumbnail(array $data, $width, $height = 0)
    {
        $image = $data['image'];
        $imageInfo = $data['imageInfo'];

        if (empty($image)) {
            // no image input, create a new one
            $size = min(100, max($width, $height));
            $image = XenForo_Image_Abstract::createImage($size, $size);
            $imageInfo['typeInt'] = IMAGETYPE_PNG;
        }

        $isGd = $image instanceof XenForo_Image_Gd;
        $isImageMagick = $image instanceof XenForo_Image_ImageMagick_Pecl;
        if ($imageInfo['typeInt'] === IMAGETYPE_GIF && $isImageMagick) {
            /** @noinspection PhpParamsInspection */
            _DevHelper_Helper_ShippableHelper_ImageCore_ImageMagick::dropFrames($image);
        }

        if (in_array($width, array('', 0), true) && $height > 0) {
            self::_resizeStretchWidth($image, $height);
        } elseif (in_array($height, array('', 0), true) && $width > 0) {
            self::_resizeStretchHeight($image, $width);
        } elseif ($width > 0 && $height > 0) {
            self::_cropExact($image, $width, $height);
        } elseif ($width > 0) {
            self::_cropSquare($image, $width);
        }

        if ($imageInfo['typeInt'] === IMAGETYPE_JPEG) {
            if ($isGd) {
                /** @noinspection PhpParamsInspection */
                _DevHelper_Helper_ShippableHelper_ImageCore_Gd::progressiveJpeg($image);
            } elseif ($isImageMagick) {
                /** @noinspection PhpParamsInspection */
                _DevHelper_Helper_ShippableHelper_ImageCore_ImageMagick::progressiveJpeg($image);
            }
        }

        return array(
            'image' => $image,
            'imageInfo' => $imageInfo,
        );
    }

    /**
     * @param XenForo_Image_Abstract $imageObj
     * @param int $targetHeight
     */
    protected static function _resizeStretchWidth(XenForo_Image_Abstract $imageObj, $targetHeight)
    {
        $targetWidth = $targetHeight / $imageObj->getHeight() * $imageObj->getWidth();
        $imageObj->thumbnail($targetWidth, $targetHeight);
    }

    /**
     * @param XenForo_Image_Abstract $imageObj
     * @param int $targetWidth
     */
    protected static function _resizeStretchHeight(XenForo_Image_Abstract $imageObj, $targetWidth)
    {
        $targetHeight = $targetWidth / $imageObj->getWidth() * $imageObj->getHeight();
        $imageObj->thumbnail($targetWidth, $targetHeight);
    }

    /**
     * @param XenForo_Image_Abstract $imageObj
     * @param int $targetWidth
     * @param int $targetHeight
     */
    protected static function _cropExact(XenForo_Image_Abstract $imageObj, $targetWidth, $targetHeight)
    {
        $origRatio = $imageObj->getWidth() / $imageObj->getHeight();
        $cropRatio = $targetWidth / $targetHeight;
        if ($origRatio > $cropRatio) {
            $thumbnailHeight = $targetHeight;
            $thumbnailWidth = $thumbnailHeight * $origRatio;
        } else {
            $thumbnailWidth = $targetWidth;
            $thumbnailHeight = $thumbnailWidth / $origRatio;
        }

        if ($thumbnailWidth <= $imageObj->getWidth()
            && $thumbnailHeight <= $imageObj->getHeight()
        ) {
            $imageObj->thumbnail($thumbnailWidth, $thumbnailHeight);
            self::_cropCenter($imageObj, $targetWidth, $targetHeight);
        } else {
            if ($origRatio > $cropRatio) {
                self::_cropCenter($imageObj, $imageObj->getHeight() * $cropRatio, $imageObj->getHeight());
            } else {
                self::_cropCenter($imageObj, $imageObj->getWidth(), $imageObj->getWidth() / $cropRatio);
            }
        }
    }

    /**
     * @param XenForo_Image_Abstract $imageObj
     * @param int $target
     */
    protected static function _cropSquare(XenForo_Image_Abstract $imageObj, $target)
    {
        $imageObj->thumbnailFixedShorterSide($target);
        self::_cropCenter($imageObj, $target, $target);
    }

    /**
     * @param XenForo_Image_Abstract $imageObj
     * @param int $cropWidth
     * @param int $cropHeight
     */
    protected static function _cropCenter(XenForo_Image_Abstract $imageObj, $cropWidth, $cropHeight)
    {
        if (self::$cropTopLeft) {
            // revert to top left cropping (old version behavior)
            /** @noinspection PhpParamsInspection */
            $imageObj->crop(0, 0, $cropWidth, $cropHeight);
            return;
        }

        $width = $imageObj->getWidth();
        $height = $imageObj->getHeight();
        $x = floor(($width - $cropWidth) / 2);
        $y = floor(($height - $cropHeight) / 2);

        /** @noinspection PhpParamsInspection */
        $imageObj->crop($x, $y, $cropWidth, $cropHeight);
    }

    protected static function _getAccessiblePath($path)
    {
        if (!parse_url($path, PHP_URL_HOST)) {
            return $path;
        }

        $boardUrl = XenForo_Application::getOptions()->get('boardUrl');
        if (strpos($path, '..') === false
            && strpos($path, $boardUrl) === 0
        ) {
            $localFilePath = self::_getLocalFilePath(substr($path, strlen($boardUrl)));
            if (self::_imageExists($localFilePath)) {
                return $localFilePath;
            }
        }

        if (preg_match('#attachments/(.+\.)*(?<id>\d+)/#', $path, $matches)) {
            $fullIndex = XenForo_Link::buildPublicLink('full:index');
            $canonicalIndex = XenForo_Link::buildPublicLink('canonical:index');
            if (strpos($path, $fullIndex) === 0 || strpos($path, $canonicalIndex) === 0) {
                $attachmentDataFilePath = self::_getAttachmentDataFilePath($matches['id']);
                if (self::_imageExists($attachmentDataFilePath)) {
                    return $attachmentDataFilePath;
                }
            }
        }

        return DevHelper_Helper_ShippableHelper_TempFile::download($path);
    }

    protected static function _getLocalFilePath($path)
    {
        // remove query parameters
        $path = preg_replace('#(\?|\#).*$#', '', $path);
        if (strlen($path) === 0) {
            return $path;
        }

        $extension = XenForo_Helper_File::getFileExtension($path);
        if (!in_array($extension, array('gif', 'jpeg', 'jpg', 'png'), true)) {
            return '';
        }

        /** @var XenForo_Application $app */
        $app = XenForo_Application::getInstance();
        $path = sprintf('%s/%s', rtrim($app->getRootDir(), '/'), ltrim($path, '/'));

        return $path;
    }

    protected static function _getAttachmentDataFilePath($attachmentId)
    {
        /** @var XenForo_Model_Attachment $attachmentModel */
        static $attachmentModel = null;
        static $attachments = array();

        if ($attachmentModel === null) {
            $attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');
        }

        if (!isset($attachments[$attachmentId])) {
            $attachments[$attachmentId] = $attachmentModel->getAttachmentById($attachmentId);
        }

        if (empty($attachments[$attachmentId])) {
            return '';
        }

        return $attachmentModel->getAttachmentDataFilePath($attachments[$attachmentId]);
    }

    protected static function _imageExists($path)
    {
        if (!is_string($path) || strlen($path) === 0) {
            // invalid path
            return false;
        }

        $pathSize = @filesize($path);
        if (!is_int($pathSize)) {
            // file not exists
            return false;
        }

        // according to many sources, no valid image can be smaller than 14 bytes
        // http://garethrees.org/2007/11/14/pngcrush/
        // https://github.com/mathiasbynens/small/blob/master/gif.gif
        return $pathSize >= 14;
    }

    protected static function _getImageInfo($path)
    {
        $imageInfo = array();

        $fileSize = @filesize($path);
        if (!empty($fileSize)) {
            $imageInfo['fileSize'] = $fileSize;
        }

        if (!empty($imageInfo['fileSize'])
            && $info = @getimagesize($path)
        ) {
            $imageInfo['width'] = $info[0];
            $imageInfo['height'] = $info[1];
            $imageInfo['typeInt'] = $info[2];
        }

        return $imageInfo;
    }
}

class _DevHelper_Helper_ShippableHelper_ImageCore_Gd extends XenForo_Image_Gd
{
    public static function progressiveJpeg(XenForo_Image_Gd $obj)
    {
        imageinterlace($obj->_image, 1);
    }
}

class _DevHelper_Helper_ShippableHelper_ImageCore_ImageMagick extends XenForo_Image_ImageMagick_Pecl
{
    public static function dropFrames(XenForo_Image_ImageMagick_Pecl $obj)
    {
        $image = $obj->_image;

        if ($image->count() > 1) {
            $newImage = null;

            foreach ($image as $frame) {
                $newImage = new Imagick();
                $newImage->addImage($frame->getImage());
                break;
            }

            if ($newImage !== null) {
                $obj->_image = $newImage;
                $image->destroy();
            }
        }
    }

    public static function progressiveJpeg(XenForo_Image_ImageMagick_Pecl $obj)
    {
        $obj->_image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
    }
}