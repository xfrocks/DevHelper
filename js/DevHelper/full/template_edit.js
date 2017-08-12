/** @param {jQuery} $ jQuery Object */
!function ($) {

    var defaultExtraKeys = {};
    defaultExtraKeys['Tab'] = 'indentMore';
    defaultExtraKeys['Shift-Tab'] = 'indentLess';

    var defaultConfig = {
        mode: 'htmlmixed',
        lineNumbers: 1,
        indentWithTabs: 1,
        smartIndent: 1,
        tabSize: 4,
        indentUnit: 4,
        extraKeys: defaultExtraKeys
    };

    if (XenForo.TemplateEditor) {
        var targetPrototype = XenForo.TemplateEditor.prototype;

        targetPrototype.DevHelper_extraKeysSave = function () {
            $('#saveReloadButton').trigger('click');
        };

        targetPrototype.DevHelper_setupCM = function (editor) {
            var $textarea = editor.$textarea;
            var $textareaWrapper = this.getTextareaWrapper();

            // default to use the mixed HTML mode
            var cmMode = 'htmlmixed';
            if (editor.$title.val().indexOf('.css') !== -1) {
                // we are editing a CSS template
                // switch to CSS mode
                cmMode = 'css';
            }

            var config = defaultConfig;
            config['value'] = $textarea.val();
            config['mode'] = cmMode;

            config.extraKeys['Cmd-S'] = $.context(this, 'DevHelper_extraKeysSave');
            config.extraKeys['Ctrl-S'] = $.context(this, 'DevHelper_extraKeysSave');

            var theCM = CodeMirror(function () {
            }, config);
            theCM.on('change', function (cm) {
                $textarea.val(cm.getValue());
            });

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
        targetPrototype.initializePrimaryEditor = function () {
            originalInitializePrimaryEditor.call(this);

            var templateTitle = this.$titleOriginal.strval();
            var editor = this.editors[templateTitle];

            this.DevHelper_setupCM(editor);
        };

        var originalCreateEditor = targetPrototype.createEditor;
        targetPrototype.createEditor = function (templateTitle, $prevTab) {
            var editor = originalCreateEditor.call(this, templateTitle, $prevTab);

            this.DevHelper_setupCM(editor);

            // immediately hide the editor
            editor.$theCMWrapper.xfHide();

            return editor;
        };

        targetPrototype.switchEditor = function (e) {
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

    XenForo.DevHelper_CodeMirror_TextArea = function ($textarea) {
        $textarea.each(function () {
            var config = defaultConfig;

            //config['viewportMargin'] = 'Infinity';

            var functionSave = function () {
                $textarea.parents('form').find('input.button.primary').trigger('click');
            };
            config.extraKeys['Cmd-S'] = functionSave;
            config.extraKeys['Ctrl-S'] = functionSave;

            var theCM = CodeMirror.fromTextArea(this, defaultConfig);

            theCM.on('change', function (cm) {
                $textarea.val(cm.getValue());
            });
            $(theCM.getWrapperElement()).addClass('DevHelper_CodeMirror_TextArea');
        });
    };

    // support template modification `find` textarea
    XenForo.register('textarea#ctrl_find.code', 'XenForo.DevHelper_CodeMirror_TextArea');
    XenForo.register('textarea#ctrl_replace.code', 'XenForo.DevHelper_CodeMirror_TextArea');
}(jQuery);