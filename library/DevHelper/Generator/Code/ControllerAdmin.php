<?php
class DevHelper_Generator_Code_ControllerAdmin extends DevHelper_Generator_Code_Common {
	
	protected $_addOn = null;
	protected $_config = null;
	protected $_dataClass = null;
	protected $_info = null;
	
	protected function __construct(array $addOn, DevHelper_Config_Base $config, array $dataClass, array $info) {
		$this->_addOn = $addOn;
		$this->_config = $config;
		$this->_dataClass = $dataClass;
		$this->_info = $info;
	}
	
	protected function _generate() {
		$className = $this->_getClassName();
		$variableName = strtolower(substr($this->_dataClass['camelCase'], 0, 1)) . substr($this->_dataClass['camelCase'], 1);
		
		$variableNamePlural = (empty($this->_dataClass['camelCasePlural'])
			? ('All' . $this->_dataClass['camelCase'])
			: ($this->_dataClass['camelCasePlural']));
		$variableNamePlural = strtolower(substr($variableNamePlural, 0, 1)) . substr($variableNamePlural, 1);
		
		$modelClassName = DevHelper_Generator_Code_Model::getClassName($this->_addOn, $this->_config, $this->_dataClass);
		$modelGetFunctionName = DevHelper_Generator_Code_Model::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
		$modelCountFunctionName = DevHelper_Generator_Code_Model::generateCountDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
		
		$dataWriterClassName = DevHelper_Generator_Code_DataWriter::getClassName($this->_addOn, $this->_config, $this->_dataClass);

		$viewListClassName = $this->_getViewClassName('list');
		$viewEditClassName = $this->_getViewClassName('edit');
		$viewDeleteClassName = $this->_getViewClassName('delete');
		
		$imageField = DevHelper_Generator_Db::getImageField($this->_dataClass['fields']);
		list(
			$templateList,
			$templateEdit,
			$templateDelete
		) = $this->_generateTemplates($variableName, $variableNamePlural, $imageField);
		
		$this->_setClassName($this->_info['controller']);
		$this->_setBaseClass('XenForo_ControllerAdmin_Abstract');
		
		$this->_addMethod('actionIndex', 'public', array(), "

\${$variableName}Model = \$this->_get{$this->_dataClass['camelCase']}Model();
\${$variableNamePlural} = \${$variableName}Model->{$modelGetFunctionName}();

\$viewParams = array(
	'{$variableNamePlural}' => \${$variableNamePlural}
);

return \$this->responseView('$viewListClassName', '$templateList', \$viewParams);

		");
		
		$this->_addMethod('actionAdd', 'public', array(), "

\$viewParams = array(
	'$variableName' => array()
);

		", '000');
		
		$this->_addMethod('actionAdd', 'public', array(), "

return \$this->responseView('$viewEditClassName', '$templateEdit', \$viewParams);

		", '999');
		
		$this->_addMethod('actionEdit', 'public', array(), "

\$id = \$this->_input->filterSingle('{$this->_dataClass['id_field']}', XenForo_Input::UINT);
\${$variableName} = \$this->_get{$this->_dataClass['camelCase']}OrError(\$id);

\$viewParams = array(
	'$variableName' => \${$variableName},
);

		", '000');
		
		$this->_addMethod('actionEdit', 'public', array(), "

return \$this->responseView('$viewEditClassName', '$templateEdit', \$viewParams);

		", '999');
		
		$this->_addMethod('actionSave', 'public', array(), "

\$this->_assertPostOnly();

\$id = \$this->_input->filterSingle('{$this->_dataClass['id_field']}', XenForo_Input::UINT);
\$dw = \$this->_get{$this->_dataClass['camelCase']}DataWriter();
if (\$id) {
	\$dw->setExistingData(\$id);
}

		", '000');
		
		$this->_addMethod('actionSave', 'public', array(), "

\$this->_prepareDwBeforeSaving(\$dw);

\$dw->save();

return \$this->responseRedirect(
	XenForo_ControllerResponse_Redirect::SUCCESS,
	XenForo_Link::buildAdminLink('{$this->_info['routePrefix']}')
);

		", '999');
		
		$this->_addMethod('actionDelete', 'public', array(), "

\$id = \$this->_input->filterSingle('{$this->_dataClass['id_field']}', XenForo_Input::UINT);
\${$variableName} = \$this->_get{$this->_dataClass['camelCase']}OrError(\$id);

if (\$this->isConfirmedPost()) {
	\$dw = \$this->_get{$this->_dataClass['camelCase']}DataWriter();
	\$dw->setExistingData(\$id);
	\$dw->delete();

	return \$this->responseRedirect(
		XenForo_ControllerResponse_Redirect::SUCCESS,
		XenForo_Link::buildAdminLink('{$this->_info['routePrefix']}')
	);
} else {
	\$viewParams = array(
		'$variableName' => \${$variableName}
	);

	return \$this->responseView('$viewDeleteClassName', '$templateDelete', \$viewParams);
}

		");
		
		$phraseNotFound = $this->_getPhraseName('_not_found');
		$this->_addMethod("_get{$this->_dataClass['camelCase']}OrError", 'protected', array(
			'$id',
			'$fetchOptions' => 'array $fetchOptions = array()',
		), "

\${$variableName} = \$this->_get{$this->_dataClass['camelCase']}Model()->get{$this->_dataClass['camelCase']}ById(\$id, \$fetchOptions);
		
if (empty(\${$variableName})) {
	throw \$this->responseException(\$this->responseError(new XenForo_Phrase('$phraseNotFound'), 404));
}

return \${$variableName};

		");
		
		$this->_addMethod("_get{$this->_dataClass['camelCase']}Model", 'protected', array(), "

return \$this->getModelFromCache('$modelClassName');

		");
		
		$this->_addMethod(" _get{$this->_dataClass['camelCase']}DataWriter", 'protected', array(), "

return XenForo_DataWriter::create('$dataWriterClassName');

		");
		
		$this->_addCustomizableMethod('_prepareDwBeforeSaving', 'protected', array(
			'$dw' => "$dataWriterClassName \$dw",
		));

		return parent::_generate();
	}
	
	protected function _generateTemplates($variableName, $variableNamePlural, $imageField) {
		$dataTitle = "\${$variableName}" . (empty($this->_dataClass['title_field'])
			?(".{$this->_dataClass['id_field']}")
			:((is_array($this->_dataClass['title_field'])
			? (".{$this->_dataClass['title_field'][0]}.{$this->_dataClass['title_field'][1]}")
			: (".{$this->_dataClass['title_field']}"))));
			
		// create the phrases
		$phraseClassName = $this->_getPhraseName('');
		$phraseAdd = $this->_getPhraseName('_add');
		$phraseEdit = $this->_getPhraseName('_edit');
		$phraseDelete = $this->_getPhraseName('_delete');
		$phraseSave = $this->_getPhraseName('_save');
		$phraseConfirmDeletion = $this->_getPhraseName('_confirm_deletion');
		$phrasePleaseConfirm = $this->_getPhraseName('_please_confirm');
		$phraseNotFound = $this->_getPhraseName('_not_found');
		$phraseNoResults = $this->_getPhraseName('_no_results');
		
		$this->_generatePhrase($phraseClassName, $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phraseAdd, 'Add New ' . $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phraseEdit, 'Edit ' . $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phraseDelete, 'Delete ' . $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phraseSave, 'Save ' . $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phraseConfirmDeletion, 'Confirm Deletion of ' . $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phrasePleaseConfirm, 'Please confirm that you want to delete the following ' . $this->_dataClass['camelCaseWSpace']);
		$this->_generatePhrase($phraseNotFound, 'The requested ' . $this->_dataClass['camelCaseWSpace'] . ' could not be found');
		$this->_generatePhrase($phraseNoResults, 'No clues of ' . $this->_dataClass['camelCaseWSpace'] . ' at this moment...');
		// finished creating pharses
		
		$templateList = $this->_getTemplateTitle('_list');
		$templateEdit = $this->_getTemplateTitle('_edit');
		$templateDelete = $this->_getTemplateTitle('_delete');
		
		// create the templates
		$templateListTemplate = <<<EOF
<xen:title>{xen:phrase $phraseClassName}</xen:title>

<xen:topctrl>
	<a href="{xen:adminlink '{$this->_info['routePrefix']}/add'}" class="button" accesskey="a">+ {xen:phrase $phraseAdd}</a>
</xen:topctrl>

<xen:require css="filter_list.css" />
<xen:require js="js/xenforo/filter_list.js" />

<xen:form action="{xen:adminlink '{$this->_info['routePrefix']}'}" class="section">
	<xen:if is="{\${$variableNamePlural}}">
		<h2 class="subHeading">
			<xen:include template="filter_list_controls" />
			{xen:phrase $phraseClassName}
		</h2>
	
		<ol class="FilterList Scrollable">
			<xen:foreach loop="\${$variableNamePlural}" value="\${$variableName}">
				<xen:listitem
					id="{\${$variableName}.{$this->_dataClass['id_field']}}"
					href="{xen:adminlink '{$this->_info['routePrefix']}/edit', \${$variableName}}"
					label="{{$dataTitle}}"
					delete="{xen:adminlink '{$this->_info['routePrefix']}/delete', \${$variableName}}" />
			</xen:foreach>
		</ol>
	
		<p class="sectionFooter">{xen:phrase showing_x_of_y_items, 'count=<span class="FilterListCount">{xen:count \${$variableNamePlural}}</span>', 'total={xen:count \${$variableNamePlural}}'}</p>
	<xen:else />
		<div class="noResults">{xen:phrase $phraseNoResults}</div>
	</xen:if>
</xen:form>
EOF;
		$this->_generateAdminTemplate($templateList, $templateListTemplate);
		// finished template_list
		
		$templateEditFormExtra = 'class="AutoValidator" data-redirect="yes"';
		$templateEditFields = '';
		$filterParams = array();
		
		foreach ($this->_dataClass['fields'] as $field) {
			if ($field['name'] == $this->_dataClass['id_field']) continue;
			if (empty($field['required'])) continue; // ignore non-required fields 
			if ($field['name'] == $imageField) continue; // ignore image field
			
			// queue this field for validation
			$filterParams[$field['name']] = $field['type'];
			
			if ($field['name'] == $this->_dataClass['title_field']) {
				$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($this->_addOn, $this->_config, $this->_dataClass, $field['name']);				
				$templateEditFields .= <<<EOF

	<xen:textboxunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}" data-liveTitleTemplate="{xen:if {\${$variableName}.{$this->_dataClass['id_field']}},
		'{xen:phrase $phraseEdit}: <em>%s</em>',
		'{xen:phrase $phraseAdd}: <em>%s</em>'}" />
EOF;
				continue;
			}
			
			if (substr($field['name'], -3) == '_id') {
				// link to another dataClass?
				$other = substr($field['name'], 0, -3);
				if ($this->_config->checkDataClassExists($other)) {
					// yeah!
					$otherDataClass = $this->_config->getDataClass($other);
					$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($this->_addOn, $this->_config, $otherDataClass, $otherDataClass['name']);				
					$templateEditFields .= <<<EOF

	<xen:selectunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}">
		<xen:option value="">&nbsp;</xen:option>
		<xen:options source="\$all{$otherDataClass['camelCase']}" />
	</xen:selectunit>
EOF;
					$otherDataClassModelClassName = DevHelper_Generator_Code_Model::getClassName($addOn, $config, $otherDataClass);
					$otherDataClassStuff .= <<<EOF
'all{$otherDataClass['camelCase']}' => \$this->getModelFromCache('$otherDataClassModelClassName')->getList(),
EOF;
					continue;
				}
			}
			
			// special case with display_order
			if ($field['name'] == 'display_order') {
				$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($this->_addOn, $this->_config, $this->_dataClass, $field['name']);
				$templateEditFields .= <<<EOF

	<xen:spinboxunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}" />
EOF;
				continue;
			}
			
			$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($this->_addOn, $this->_config, $this->_dataClass, $field['name']);
			$extra = '';
			if ($field['type'] == XenForo_DataWriter::TYPE_STRING AND (empty($field['length']) OR $field['length'] > 255)) {
				$extra .= ' rows="5"';
			}
			$templateEditFields .= <<<EOF

	<xen:textboxunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}" $extra/>
EOF;
		}
		
		if ($imageField !== false) {
			$fieldPhraseImage = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($this->_addOn, $this->_config, $this->_dataClass, 'image');
			$templateEditFormExtra = 'enctype="multipart/form-data"';
			
			$templateEditFields .= <<<EOF
	<xen:uploadunit label="{xen:phrase $fieldPhraseImage}:" name="image" value="">
		<div id="imageHtml">
			<xen:if is="{\${$variableName}.images}">
				<xen:foreach loop="\${$variableName}.images" key="\$imageSizeCode" value="\$image">
					<img src="{\$image}" alt="{\$imageSizeCode}" title="{\$imageSizeCode}" />
				</xen:foreach>
			</xen:if>
		</div>
	</xen:uploadunit>
EOF;
		}
		
		$templateEditTemplate = <<<EOF
<xen:title>{xen:if '{\${$variableName}.{$this->_dataClass['id_field']}}', '{xen:phrase $phraseEdit}', '{xen:phrase $phraseAdd}'}</xen:title>

<xen:form action="{xen:adminlink '{$this->_info['routePrefix']}/save'}" $templateEditFormExtra>

	$templateEditFields

	<xen:submitunit save="{xen:phrase $phraseSave}">
		<input type="button" name="delete" value="{xen:phrase $phraseDelete}"
			accesskey="d" class="button OverlayTrigger"
			data-href="{xen:adminlink '{$this->_info['routePrefix']}/delete', \${$variableName}}"
			{xen:if '!{\${$variableName}.{$this->_dataClass['id_field']}}', 'style="display: none"'}
		/>
	</xen:submitunit>
	
	<input type="hidden" name="{$this->_dataClass['id_field']}" value="{\${$variableName}.{$this->_dataClass['id_field']}}" />
</xen:form>
EOF;

		$this->_generateAdminTemplate($templateEdit, $templateEditTemplate);
		
		// add input fields to action save
		$filterParamsExported = DevHelper_Generator_File::varExport($filterParams, 1);
		$this->_addMethod('actionSave', 'public', array(), "

\$dwInput = \$this->_input->filter($filterParamsExported);
\$dw->bulkSet(\$dwInput);

		", '500');
		
		if ($imageField !== false) {
			// add image to action save
			$this->_addMethod('actionSave', 'public', array(), "

\$image = XenForo_Upload::getUploadedFile('image');
if (!empty(\$image)) {
	\$dw->setImage(\$image);
}

			", '501');
		}
		
		// finished template_edit
		
		$templateDeleteTemplate = <<<EOF
<xen:title>{xen:phrase $phraseConfirmDeletion}: {{$dataTitle}}</xen:title>
<xen:h1>{xen:phrase $phraseConfirmDeletion}</xen:h1>

<xen:navigation>
	<xen:breadcrumb href="{xen:adminlink '{$this->_info['routePrefix']}/edit', \${$variableName}}">{{$dataTitle}}</xen:breadcrumb>
</xen:navigation>

<xen:require css="delete_confirmation.css" />

<xen:form action="{xen:adminlink '{$this->_info['routePrefix']}/delete', \${$variableName}}" class="deleteConfirmForm formOverlay">

	<p>{xen:phrase $phrasePleaseConfirm}:</p>
	<strong><a href="{xen:adminlink '{$this->_info['routePrefix']}/edit', \${$variableName}}">{{$dataTitle}}</a></strong>

	<xen:submitunit save="{xen:phrase $phraseDelete}" />
	
	<input type="hidden" name="_xfConfirm" value="1" />
</xen:form>
EOF;
		$this->_generateAdminTemplate($templateDelete, $templateDeleteTemplate);
		
		// finished creating our templates
		
		return array(
			$templateList,
			$templateEdit,
			$templateDelete,
		);
	}
	
	protected function _getPhraseName($suffix) {
		return DevHelper_Generator_Phrase::getPhraseName($this->_addOn, $this->_config, $this->_dataClass, $this->_dataClass['name'] . $suffix);
	}
	
	protected function _generatePhrase($phraseName, $phraseText) {
		DevHelper_Generator_Phrase::generatePhrase($this->_addOn, $phraseName, $phraseText);
	}
	
	protected function _getTemplateTitle($suffix) {
		return DevHelper_Generator_Template::getTemplateTitle($this->_addOn, $this->_config, $this->_dataClass, $this->_dataClass['name'] . $suffix);
	}
	
	protected function _generateAdminTemplate($templateTitle, $templateHtml) {
		DevHelper_Generator_Template::generateAdminTemplate($this->_addOn, $templateTitle, $templateHtml);
	}
	
	protected function _getClassName() {
		return self::getClassName($this->_addOn, $this->_config, $this->_dataClass);
	}
	
	protected function _getViewClassName($view) {
		return DevHelper_Generator_File::getClassName($this->_addOn['addon_id'], 'ViewAdmin_' . $this->_dataClass['camelCase'] . '_' . ucwords($view));
	}
	
	public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass, array $info) {
		$g = new self($addOn, $config, $dataClass, $info);
		
		return array($g->_getClassName(), $g->_generate());
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'ControllerAdmin_' . $dataClass['camelCase']);
	}
}