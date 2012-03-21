/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined) {
	
	var targetPrototype = XenForo.TemplateEditor.prototype;
	
	targetPrototype.DevHelper_setupAce = function(editor) {
		var $textarea = editor.$textarea;
		console.log($textarea);
		var uniqueId = XenForo.uniqueId();
		var $ace = $('<div />')
					.attr('id', uniqueId)
					.css('position', 'relative')
					.css('width', $textarea.width() + 'px')
					.css('height', $textarea.height() + 'px');
		this.getTextareaWrapper().append($ace);
		
		$ace.aceEditor = ace.edit(uniqueId);
		var session = $ace.aceEditor.getSession();
		session.setValue($textarea.val());
		session.on('change', function() {
			$textarea.val(session.getValue());
		});
		
		var HtmlMode = require('ace/mode/html').Mode;
		session.setMode(new HtmlMode());
		
		// hide the textarea
		$textarea.xfHide();
		
		// save it for later access
		editor.$ace = $ace;
	};
	
	var originalInitializePrimaryEditor = targetPrototype.initializePrimaryEditor;
	targetPrototype.initializePrimaryEditor = function() {
		originalInitializePrimaryEditor.call(this);
		
		var templateTitle = this.$titleOriginal.strval();
		var editor = this.editors[templateTitle];
		
		this.DevHelper_setupAce(editor);
	};
	
	var originalCreateEditor = targetPrototype.createEditor;
	targetPrototype.createEditor = function(templateTitle, $prevTab) {
		var editor = originalCreateEditor.call(this, templateTitle, $prevTab);
		
		this.DevHelper_setupAce(editor);
		
		// immediately hide the ace
		editor.$ace.xfHide();
		
		return editor;
	};
	
	targetPrototype.switchEditor = function(e) {
		var $target = $(e.target).closest('a'),
			editor;

		// switch the active tab
		$target.closest('li')
			.addClass('active')
			.siblings().removeClass('active');

		// switch the active editor
		$('.ace_editor', this.getTextareaWrapper())
			.xfHide();

		editor = this.editors[$target.attr('templateTitle')];

		editor.$ace
			.xfShow();
		editor.$ace.aceEditor.env.editor.focus();

		return false;
	};

}
(jQuery, this, document);