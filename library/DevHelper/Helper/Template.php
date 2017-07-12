<?php

class DevHelper_Helper_Template
{
    public static function autoExportImport()
    {
        return false;
    }

    public static function getTemplateDirPath()
    {
        return call_user_func_array('sprintf', array(
            '%s/DevHelper/templates',
            XenForo_Helper_File::getInternalDataPath(),
        ));
    }

    public static function getTemplateFilePath(array $template)
    {
        $extension = 'html';
        if (strpos($template['title'], '.css') !== false) {
            $extension = 'css';
        }

        return call_user_func_array('sprintf', array(
            '%s/%s_%d.%s',
            self::getTemplateDirPath(),
            $template['title'],
            $template['template_id'],
            $extension
        ));
    }

    public static function getTemplateIdFromFilePath($filePath)
    {
        $basename = basename($filePath);

        $extension = XenForo_Helper_File::getFileExtension($basename);
        if (!in_array($extension, array(
            'html',
            'css'
        ))
        ) {
            return 0;
        }

        $sanExtension = substr($basename, 0, -1 * (strlen($extension) + 1));
        $parts = explode('_', $sanExtension);
        $lastPart = array_pop($parts);
        if (!is_numeric($lastPart)) {
            return 0;
        }

        return intval($lastPart);
    }
}
