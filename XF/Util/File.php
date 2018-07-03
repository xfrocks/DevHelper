<?php

namespace DevHelper\XF\Util;

class File extends DevHelperCP_File
{
    public static function writeFile($file, $data, $createIndexHtml = true)
    {
        $patchDocCommentProperty = getenv('DEVHELPER_XF_UTIL_FILE_PATCH_DOC_COMMENT_PROPERTY');
        if (!empty($patchDocCommentProperty)) {
            $data = preg_replace('#(\s\*\s@property\s[^\s]+\s)([^\s]+\n)#', '$1$$2', $data);
        }

        return parent::writeFile($file, $data, $createIndexHtml);
    }
}

// phpcs:disable
if (false) {
    class DevHelperCP_File extends \XF\Util\File
    {
    }
}
