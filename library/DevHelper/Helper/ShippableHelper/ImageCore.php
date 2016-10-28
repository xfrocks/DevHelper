<?php

/**
 * Class DevHelper_Helper_ShippableHelper_ImageCore
 * @version 1
 */
class DevHelper_Helper_ShippableHelper_ImageCore
{
    public static function thumbnail(XenForo_Image_Abstract $image, array $imageInfo, $width, $height = 0)
    {
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