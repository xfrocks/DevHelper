<?php

class DevHelper_Listener
{
    const XENFORO_CONTROLLERADMIN_ADDON_SAVE = 'DevHelper_XenForo_ControllerAdmin_AddOn';

    public static function load_class($class, array &$extend)
    {
        static $classes = array(
            'XenForo_ControllerAdmin_Home',
            'XenForo_ControllerAdmin_AddOn',
            'XenForo_ControllerAdmin_AdminTemplateModification',
            'XenForo_ControllerAdmin_CodeEventListener',
            'XenForo_ControllerAdmin_Permission',
            'XenForo_ControllerAdmin_RoutePrefix',
            'XenForo_ControllerAdmin_TemplateModification',
            'XenForo_ControllerAdmin_Tools',

            'XenForo_ControllerHelper_Editor',

            'XenForo_DataWriter_AddOn',
            'XenForo_DataWriter_Template',

            'XenForo_Model_AddOn',

            'XenForo_ViewAdmin_AddOn_Upgrade',
        );

        if (in_array($class, $classes)) {
            $extend[] = 'DevHelper_' . $class;
        }
    }

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        DevHelper_Autoloader::throwIfNotSetup();
    }

    public static function front_controller_pre_view(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
    {
        if (DevHelper_Helper_Template::autoExportImport() AND XenForo_Application::isRegistered('styles')) {
            $styles = XenForo_Application::get('styles');
            $style = reset($styles);
            $styleDate = $style['last_modified_date'];

            $templateFiles = glob(DevHelper_Helper_Template::getTemplateDirPath() . '/*');
            $templatesUpdated = array();
            $maxTemplateDate = 0;

            /** @var XenForo_Model_StyleProperty $propertyModel */
            $propertyModel = XenForo_Model::create('XenForo_Model_StyleProperty');

            foreach ($templateFiles as $templateFile) {
                $templateDate = filemtime($templateFile);

                if ($templateDate - 3 > $styleDate) {
                    // consider this is a change, start updating the template
                    $maxTemplateDate = max($maxTemplateDate, $templateDate);

                    $templateId = DevHelper_Helper_Template::getTemplateIdFromFilePath($templateFile);
                    if (empty($templateId)) {
                        continue;
                    }

                    $templateText = file_get_contents($templateFile);

                    $properties = $propertyModel->keyPropertiesByName($propertyModel->getEffectiveStylePropertiesInStyle(0));
                    $propertyChanges = $propertyModel->translateEditorPropertiesToArray($templateText, $templateText, $properties);

                    /** @var XenForo_DataWriter_Template $dw */
                    $dw = XenForo_DataWriter::create('XenForo_DataWriter_Template');
                    $dw->setExistingData($templateId);
                    $dw->set('template', $templateText);

                    if ($dw->hasErrors()) {
                        throw new XenForo_Exception(implode(', ', $dw->getErrors()));
                    }

                    if ($dw->hasChanges()) {
                        $dw->reparseTemplate();

                        $dw->save();

                        $propertyModel->saveStylePropertiesInStyleFromTemplate(0, $propertyChanges, $properties);

                        $templatesUpdated[] = $dw->get('title');
                    }
                }
            }

            if (!empty($maxTemplateDate)) {
                /** @var XenForo_Model_Style $styleModel */
                $styleModel = XenForo_Model::create('XenForo_Model_Style');
                $styleModel->updateAllStylesLastModifiedDate($maxTemplateDate);
            }
        }
    }

    public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
    {
        switch ($templateName) {
            case 'PAGE_CONTAINER':
                DevHelper_Generator_File::minifyJs($template);
                break;
        }
    }

    public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {
        switch ($hookName) {
            case 'devhelper_devhelper_helper_addon_unit':
                self::_filterDisabledAddOnOptions($contents);
                break;
        }
    }

    protected static function _filterDisabledAddOnOptions(&$html)
    {
        $offset = 0;

        $addOns = XenForo_Application::get('addOns');

        while (true) {
            if (preg_match('/<option value="([^"]+)">[^<]+<\/option>/i', $html, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $offset = $matches[0][1] + 1;
                $length = strlen($matches[0][0]);
                $addOnId = $matches[1][0];

                if (!isset($addOns[$addOnId])) {
                    $html = substr_replace($html, '', $offset - 1, $length);
                    $offset--;
                }
            } else {
                break;
            }
        }

        $groupOffset = 0;
        while (true) {
            if (preg_match('/<optgroup label=".+">\s+<\/optgroup>/i', $html, $matches, PREG_OFFSET_CAPTURE, $groupOffset)) {
                $groupOffset = $matches[0][1] + 1;
                $length = strlen($matches[0][0]);

                $html = substr_replace($html, '', $groupOffset - 1, $length);
                $groupOffset--;
            } else {
                break;
            }
        }
    }

}
