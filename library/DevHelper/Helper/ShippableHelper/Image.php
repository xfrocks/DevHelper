<?php

/**
 * Class DevHelper_Helper_ShippableHelper_Image
 * @version 9
 */
class DevHelper_Helper_ShippableHelper_Image
{
    public static function getThumbnailUrl($fullPath, $width, $height = 0, $dir = null)
    {
        if (defined('BDIMAGE_IS_WORKING')) {
            $size = $width;
            $mode = bdImage_Integration::MODE_CROP_EQUAL;
            if ($width > 0 && $height > 0) {
                $size = $width;
                $mode = $height;
            } elseif ($width > 0) {
                $size = $width;
                $mode = bdImage_Integration::MODE_STRETCH_HEIGHT;
            } elseif ($height > 0) {
                $size = $height;
                $mode = bdImage_Integration::MODE_STRETCH_WIDTH;
            }

            return bdImage_Integration::buildThumbnailLink($fullPath, $size, $mode);
        }

        $thumbnailPath = self::getThumbnailPath($fullPath, $width, $height, $dir);
        $thumbnailUrl = XenForo_Application::$externalDataUrl
            . self::_getThumbnailRelativePath($fullPath, $width, $height, $dir);
        if (file_exists($thumbnailPath) && filesize($thumbnailPath) > 0) {
            return $thumbnailUrl;
        }

        $tempPath = self::_getTempPath($fullPath);
        $imageInfo = self::_getImageInfo($tempPath);

        $image = null;
        if (!empty($imageInfo['typeInt'])) {
            $image = XenForo_Image_Abstract::createFromFile($tempPath, $imageInfo['typeInt']);
        }
        if (empty($image)) {
            // could not open the image, create a new image
            $image = XenForo_Image_Abstract::createImage(1, 1);
            $imageInfo['typeInt'] = IMAGETYPE_PNG;
        }

        DevHelper_Helper_ShippableHelper_ImageCore::thumbnail($image, $imageInfo, $width, $height);

        XenForo_Helper_File::createDirectory(dirname($thumbnailPath), true);
        $outputPath = tempnam(XenForo_Helper_File::getTempDir(), __CLASS__);

        /** @noinspection PhpParamsInspection */
        $image->output($imageInfo['typeInt'], $outputPath);
        XenForo_Helper_File::safeRename($outputPath, $thumbnailPath);

        return $thumbnailUrl;
    }

    public static function getThumbnailPath($fullPath, $width, $height = 0, $dir = null)
    {
        $thumbnailPath = XenForo_Helper_File::getExternalDataPath()
            . self::_getThumbnailRelativePath($fullPath, $width, $height, $dir);

        return $thumbnailPath;
    }

    protected static function _getTempPath($path)
    {
        if (!!parse_url($path, PHP_URL_HOST)) {
            $tempPath = DevHelper_Helper_ShippableHelper_TempFile::download($path);
        } else {
            $tempPath = $path;
        }

        return $tempPath;
    }

    protected static function _getThumbnailRelativePath($fullPath, $width, $height, $dir)
    {
        $fileName = preg_replace('#[^a-zA-Z0-9]#', '', basename($fullPath)) . md5(serialize(array(
                'fullPath' => $fullPath,
                'width' => $width,
                'height' => $height,
            )));
        $ext = XenForo_Helper_File::getFileExtension($fullPath);
        $divider = substr(md5($fileName), 0, 2);

        if (empty($dir)) {
            $dir = trim(str_replace('_', '/', substr(__CLASS__, 0, strpos(__CLASS__, '_ShippableHelper_Image'))), '/');
        }

        return sprintf('/%s/%sx%s/%s/%s.%s', $dir, $width, $height, $divider, $fileName, $ext);
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