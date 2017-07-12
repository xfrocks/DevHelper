<?php

class DevHelper_Generator_Template
{
    public static function getTemplateTitle(array $addOn, DevHelper_Config_Base $config, array $dataClass, $title)
    {
        return strtolower($addOn['addon_id'] . '_' . $title);
    }

    public static function generateAdminTemplate(array $addOn, $title, $template)
    {
        if (self::checkAdminTemplateExists($title)) {
            return false;
        }

        $propertyModel = self::_getStylePropertyModel();

        $properties = $propertyModel->keyPropertiesByName($propertyModel->getEffectiveStylePropertiesInStyle(-1));
        $propertyChanges = $propertyModel->translateEditorPropertiesToArray($template, $template, $properties);

        $writer = XenForo_DataWriter::create('XenForo_DataWriter_AdminTemplate');
        $writer->bulkSet(array(
            'title' => $title,
            'template' => $template,
            'addon_id' => $addOn['addon_id'],
        ));

        try {
            $writer->save();
        } catch (Exception $ex) {
            throw new XenForo_Exception("Exception creating template $title: " .
                $ex->getMessage() . '<br/><pre>' . htmlentities($template) . '</pre>');
        }

        $propertyModel->saveStylePropertiesInStyleFromTemplate(-1, $propertyChanges, $properties);

        return true;
    }

    public static function checkAdminTemplateExists($title)
    {
        $info = self::_getAdminTemplateModel()->getAdminTemplateByTitle($title);

        return !empty($info);
    }

    /**
     * @return XenForo_Model_AdminTemplate
     */
    protected static function _getAdminTemplateModel()
    {
        /** @var XenForo_Model_AdminTemplate $model */
        static $model = null;

        if ($model === null) {
            $model = XenForo_Model::create('XenForo_Model_AdminTemplate');
        }

        return $model;
    }

    /**
     * @return XenForo_Model_StyleProperty
     */
    protected static function _getStylePropertyModel()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return self::_getAdminTemplateModel()->getModelFromCache('XenForo_Model_StyleProperty');
    }
}
