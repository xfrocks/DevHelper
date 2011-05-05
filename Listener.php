<?php
class DevHelper_Listener {
	public static function load_class($class, array &$extend) {
		static $classes = array(
			'XenForo_ControllerAdmin_AddOn',
		);
		
		if (in_array($class, $classes)) {
			$extend[] = str_replace('XenForo_', 'DevHelper_Extend_', $class);
		}
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template) {
		switch ($templateName) {
			case 'addon_edit':
				$template->preloadTemplate('devhelper_' . $templateName);
				break;
		}
	}
	
	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template) {
		switch ($templateName) {
			case 'addon_edit':
				$ourTemplate = $template->create('devhelper_' . $templateName, $template->getParams());
				$rendered = $ourTemplate->render();
				self::_injectHtml($content, $rendered);
				break;
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
		switch ($hookName) {
			case 'devhelper_search_and_replace':
				$markup = self::_getSearchAndReplaceMarkup();
				$contents = $markup . $contents . $markup;
				break;
		}
	}
	
	protected static function _injectHtml(&$content, $html) {
		if (empty($html)) return;
		
		$markup = self::_getSearchAndReplaceMarkup();
		$pos1 = strpos($html, $markup);
		if ($pos1 !== false) {
			$pos2 = strpos($html, $markup, $pos1 + 1);
			if ($pos2 !== false) {
				// found the 2 markup positions
				$search = substr($html, $pos1 + strlen($markup), $pos2 - $pos1 - strlen($markup));
				$content = str_replace($search, $html, $content);
			}
		}
	}
	
	protected static function _getSearchAndReplaceMarkup() {
		return '<!-- search and replace -->';
	}
}