/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	
	var targetPrototype = XenForo.TemplateEditor.prototype;
	
	targetPrototype.DevHelper_extraKeysSave = function(cm) {
		$('#saveReloadButton').trigger('click');
	};
	
	targetPrototype.DevHelper_setupCM = function(editor) {
		var $textarea = editor.$textarea;
		var $textareaWrapper = this.getTextareaWrapper();
		
		// default to use the mixed HTML mode
		var cmMode = 'htmlmixed';
		if (editor.$title.val().indexOf('.css') != -1) {
			// we are editing a CSS template
			// switch to CSS mode
			cmMode = 'css';
		}
		
		var config = {
			value: $textarea.val(),
			mode: cmMode,
			lineNumbers: 1,
			indentWithTabs: 1,
			smartIndent: 1,
			tabSize: 4,
			indentUnit: 4,
			onChange: function(cm, data) {
				$textarea.val(cm.getValue());
			},
			extraKeys: {}
		};
		
		config.extraKeys['Cmd-S'] = $.context(this, 'DevHelper_extraKeysSave');
		config.extraKeys['Ctrl-S'] = $.context(this, 'DevHelper_extraKeysSave');
		
		var theCM = CodeMirror(function() {}, config);
		var $wrapper = $(theCM.getWrapperElement());
		$wrapper.width($textareaWrapper.parent().width());
		
		// append the CodeMirror editor's wrapper to the page
		$textareaWrapper.append($wrapper);
		theCM.refresh();

		// hide the textarea
		$textarea.xfHide();
		
		// save it for later access
		editor.theCM = theCM;
		editor.$theCMWrapper = $wrapper;
	};
	
	var originalInitializePrimaryEditor = targetPrototype.initializePrimaryEditor;
	targetPrototype.initializePrimaryEditor = function() {
		originalInitializePrimaryEditor.call(this);
		
		var templateTitle = this.$titleOriginal.strval();
		var editor = this.editors[templateTitle];
		
		this.DevHelper_setupCM(editor);
	};
	
	var originalCreateEditor = targetPrototype.createEditor;
	targetPrototype.createEditor = function(templateTitle, $prevTab) {
		var editor = originalCreateEditor.call(this, templateTitle, $prevTab);
		
		this.DevHelper_setupCM(editor);
		
		// immediately hide the ace
		editor.$theCMWrapper.xfHide();
		
		return editor;
	};
	
	targetPrototype.switchEditor = function(e) {
		var $target = $(e.target).closest('a'),
			editor;

		// switch the active tab
		$target.closest('li')
			.addClass('active')
			.siblings().removeClass('active');

		// hide all CodeMirror instances
		$('.CodeMirror', this.getTextareaWrapper()).xfHide();

		editor = this.editors[$target.attr('templateTitle')];

		// display the only needed one
		editor.$theCMWrapper.xfShow();

		return false;
	};

}
(jQuery, this, document);