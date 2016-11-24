<?php

/**
 * Class DevHelper_Helper_ShippableHelper_ImageCore
 * @version 2
 */
class DevHelper_Helper_ShippableHelper_ImageCore
{
    /**
     * @param string $path
     * @return array
     */
    public static function open($path)
    {
        $accessiblePath = self::_getAccessiblePath($path);
        $imageInfo = self::_getImageInfo($accessiblePath);

        $image = null;
        if (!empty($imageInfo['typeInt'])) {
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
            // stretch width
            $targetHeight = $height;
            $targetWidth = $targetHeight / $image->getHeight() * $image->getWidth();
            $image->thumbnail($targetWidth, $targetHeight);
        } elseif (in_array($height, array('', 0), true) && $width > 0) {
            // stretch height
            $targetWidth = $width;
            $targetHeight = $targetWidth / $image->getWidth() * $image->getHeight();
            $image->thumbnail($targetWidth, $targetHeight);
        } elseif ($width > 0 && $height > 0) {
            // exact crop
            $origRatio = $image->getWidth() / $image->getHeight();
            $cropRatio = $width / $height;
            if ($origRatio > $cropRatio) {
                $thumbnailHeight = $height;
                $thumbnailWidth = $height * $origRatio;
            } else {
                $thumbnailWidth = $width;
                $thumbnailHeight = $width / $origRatio;
            }

            if ($thumbnailWidth <= $image->getWidth() && $thumbnailHeight <= $image->getHeight()) {
                $image->thumbnail($thumbnailWidth, $thumbnailHeight);
                $image->crop(0, 0, $width, $height);
            } else {
                // thumbnail requested is larger then the image size
                if ($origRatio > $cropRatio) {
                    $image->crop(0, 0, $image->getHeight() * $cropRatio, $image->getHeight());
                } else {
                    $image->crop(0, 0, $image->getWidth(), $image->getWidth() / $cropRatio);
                }
            }
        } elseif ($width > 0) {
            // square crop
            $image->thumbnailFixedShorterSide($width);
            $image->crop(0, 0, $width, $width);
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

    protected static function _getAccessiblePath($path)
    {
        if (!!parse_url($path, PHP_URL_HOST)) {
            $url = $path;

            $boardUrl = XenForo_Application::getOptions()->get('boardUrl');
            if (strpos($url, '..') === false
                && strpos($url, $boardUrl) === 0
            ) {
                $localFilePath = self::_getLocalFilePath(substr($url, strlen($boardUrl)));
                if (strlen($localFilePath) > 0
                    && bdImage_Helper_File::existsAndNotEmpty($localFilePath)
                ) {
                    return $localFilePath;
                }
            }

            if (preg_match('#attachments/(.+\.)*(?<id>\d+)/#', $url, $matches)) {
                $fullIndex = XenForo_Link::buildPublicLink('full:index');
                $canonicalIndex = XenForo_Link::buildPublicLink('canonical:index');
                if (strpos($url, $fullIndex) === 0 || strpos($url, $canonicalIndex) === 0) {
                    $attachmentDataFilePath = self::_getAttachmentDataFilePath($matches['id']);
                    if (bdImage_Helper_File::existsAndNotEmpty($attachmentDataFilePath)) {
                        return $attachmentDataFilePath;
                    }
                }
            }

            $tempPath = DevHelper_Helper_ShippableHelper_TempFile::download($url);
        } else {
            $tempPath = $path;
        }

        return $tempPath;
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