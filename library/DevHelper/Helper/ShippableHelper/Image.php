<?php

/**
 * Class DevHelper_Helper_ShippableHelper_Image
 * @version 2
 */
class DevHelper_Helper_ShippableHelper_Image
{
    public static function getImageInfo($path)
    {
        $tempPath = self::_getTempPath($path);
        $imageInfo = array('path' => $path);

        $fileSize = @filesize($tempPath);
        if (!empty($fileSize)) {
            $imageInfo['fileSize'] = $fileSize;
        }

        if (!empty($imageInfo['fileSize'])
            && $info = getimagesize($tempPath)
        ) {
            $imageInfo['width'] = $info[0];
            $imageInfo['height'] = $info[1];

            $imageInfo['typeInt'] = $info[2];
            switch ($imageInfo['typeInt']) {
                case IMAGETYPE_GIF:
                    $imageInfo['type'] = 'gif';
                    break;
                case IMAGETYPE_JPEG:
                    $imageInfo['type'] = 'jpeg';
                    break;
                case IMAGETYPE_PNG:
                    $imageInfo['type'] = 'png';
                    break;
            }
        }

        return $imageInfo;
    }

    public static function getThumbnailUrl($fullPath, $width, $height = 0)
    {
        $thumbnailPath = self::getThumbnailPath($fullPath, $width, $height);
        $thumbnailUrl = XenForo_Application::$externalDataUrl . self::_getThumbnailRelativePath($fullPath, $width, $height);
        if (file_exists($thumbnailPath) && filesize($thumbnailPath) > 0) {
            return $thumbnailUrl;
        }

        $tempPath = self::_getTempPath($fullPath);
        $imageInfo = self::getImageInfo($tempPath);

        if (!empty($imageInfo['typeInt'])) {
            $image = XenForo_Image_Abstract::createFromFile($tempPath, $imageInfo['typeInt']);
        }
        if (empty($image)) {
            // could not open the image, create a new image
            $longer = max($width, $height);
            $image = XenForo_Image_Abstract::createImage($longer, $longer);
        }

        if ($width === '' && $height > 0) {
            // stretch width
            $targetHeight = $height;
            $targetWidth = $targetHeight / $image->getHeight() * $image->getWidth();
            $image->thumbnail($targetWidth, $targetHeight);
        } elseif ($height === '' && $width > 0) {
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

        // TODO: progressive jpeg

        XenForo_Helper_File::createDirectory(dirname($thumbnailPath), true);
        $outputPath = tempnam(XenForo_Helper_File::getTempDir(), __CLASS__);
        $image->output($imageInfo['typeInt'], $outputPath);
        XenForo_Helper_File::safeRename($outputPath, $thumbnailPath);

        return $thumbnailUrl;
    }

    public static function getThumbnailPath($fullPath, $width, $height = 0)
    {
        return XenForo_Helper_File::getExternalDataPath() . self::_getThumbnailRelativePath($fullPath, $width, $height);
    }

    protected static function _getTempPath($path)
    {
        if (Zend_Uri::check($path)) {
            $tempFileDownload = array();
            $tempFileDownload[] = substr(__CLASS__, 0, strpos(__CLASS__, 'ShippableHelper_Image')) . 'ShippableHelper_TempFile';
            $tempFileDownload[] = 'download';
            $tempPath = call_user_func($tempFileDownload, $path);
        } else {
            $tempPath = $path;
        }

        return $tempPath;
    }

    protected static function _getThumbnailRelativePath($fullPath, $width, $height = 0)
    {
        $fileName = preg_replace('#[^a-zA-Z0-9]#', '', basename($fullPath)) . md5(serialize(array(
                'fullPath' => $fullPath,
                'width' => $width,
                'height' => $height,
            )));

        if (XenForo_Helper_File::getFileExtension($fullPath) === 'png') {
            $ext = 'png';
        } else {
            $ext = 'jpg';
        }

        $divider = substr(md5($fileName), 0, 2);

        return sprintf(
            '/%s/%sx%s/%s/%s.%s',
            str_replace('_', '/', substr(__CLASS__, 0, strpos(__CLASS__, '_ShippableHelper_Image'))),
            $width, $height, $divider, $fileName, $ext
        );
    }
}