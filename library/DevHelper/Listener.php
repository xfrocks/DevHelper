<?php
class DevHelper_Listener {
	public static function load_class($class, array &$extend) {
		static $classes = array(
			'XenForo_ControllerAdmin_AddOn',
			'XenForo_ControllerAdmin_CodeEventListener',
		
			'XenForo_Model_AddOn',
		);
		
		if (in_array($class, $classes)) {
			$extend[] = 'DevHelper_' . $class;
		}
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template) {
		switch ($templateName) {
			case 'addon_edit':
			case 'template_edit':
			case 'admin_template_edit':
				$template->preloadTemplate('devhelper_' . $templateName);
				break;
		}
	}
	
	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template) {
		switch ($templateName) {
			case 'addon_edit':
			case 'template_edit':
			case 'admin_template_edit':
				$ourTemplate = $template->create('devhelper_' . $templateName, $template->getParams());
				$rendered = $ourTemplate->render();
				self::_injectHtml($content, $rendered);
				break;
			case 'PAGE_CONTAINER':
				DevHelper_Generator_File::minifyJs($template);
				
				$params = $template->getParams();
				if (!empty($params['DevHelper_requiresCodeMirrorCSS'])) {
					$search = '</head>';
					$insert = '<link rel="stylesheet" href="js/DevHelper/CodeMirror/lib/codemirror.css" />';
					$content = str_replace($search, $insert . $search, $content);
				}
				
				break;
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
		switch ($hookName) {
			case 'devhelper_search_and_replace':
				$markup = '<!-- search and replace -->';
				$contents = $markup . $contents . $markup;
				break;
		}
	}
	
	protected static function _injectHtml(&$target, $html, $offsetInTarget = 0,
		$mark = '<!-- search and replace -->', $revertMark = '<!-- revert all the thing! -->'
	) {
		if ($offsetInTarget === false) return; // do nothing if invalid offset is given
		if (empty($html)) return; // the html is empty
		
		$injected = false;
		$isRevert = (strpos($html, $revertMark) !== false);
		
		$startPos = strpos($html, $mark);	
		if ($startPos !== false) {
			$endPos = strpos($html, $mark, $startPos + 1);
			if ($endPos !== false) {
				// found the two marks
				$markLen = strlen($mark);
				$marked = trim(substr($html, $startPos + $markLen, $endPos - $startPos - $markLen));
				
				if (!$isRevert) {
					// normal mode, look for the first occurence
					$markedPos = strpos($target, $marked, $offsetInTarget);
				} else {
					// revert mode, look for the last occurence
					$markedPos = strrpos($target, $marked, $offsetInTarget);
				}

				if ($markedPos !== false) {
					// the marked text has been found
					// start injecting our html in place
					$html = str_replace($mark, '', $html);
					$html = str_replace($revertMark, '', $html);
					
					$target = substr_replace($target, $html, $markedPos, strlen($marked));
				}
				
				$injected = true; // assume that it was injected
			}
		}
		
		if (!$injected) {
			$html = str_replace($mark, '', $html);
			$html = str_replace($revertMark, '', $html);
			
			if (!$isRevert) {
				//  normal mode, append the html
				$target .= $html;
			} else {
				// revert mode, insert instead of append
				$target = $html . $target;
			}
		}
	}
}