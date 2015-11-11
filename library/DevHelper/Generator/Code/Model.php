<?php

class DevHelper_Generator_Code_Model extends DevHelper_Generator_Code_Common
{
    protected $_addOn = null;
    protected $_config = null;
    protected $_dataClass = null;

    protected function __construct(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $this->_addOn = $addOn;
        $this->_config = $config;
        $this->_dataClass = $dataClass;
    }

    protected function _generate()
    {
        $className = $this->_getClassName();
        $tableName = DevHelper_Generator_Db::getTableName($this->_config, $this->_dataClass['name']);
        $idField = '';
        if (count($this->_dataClass['primaryKey']) == 1) {
            $idField = reset($this->_dataClass['primaryKey']);
        }
        $getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
        $countFunctionName = self::generateCountDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);

        $tableAlias = $this->_dataClass['name'];
        if (in_array($tableAlias, array(
            'group',
            'join',
            'order'
        ))) {
            $tableAlias = '_' . $tableAlias;
        }

        $variableName = self::getVariableName($this->_addOn, $this->_config, $this->_dataClass);
        $variableNamePlural = self::getVariableNamePlural($this->_addOn, $this->_config, $this->_dataClass);
        $conditionFields = DevHelper_Generator_Db::getConditionFields($this->_dataClass['fields']);

        $this->_setClassName($className);
        $this->_setBaseClass('XenForo_Model');

        $this->_addCustomizableMethod("_{$getFunctionName}Customized", 'protected', array(
            'array &$data',
            'array $fetchOptions',
        ));
        $this->_addCustomizableMethod("_prepare{$this->_dataClass['camelCase']}ConditionsCustomized", 'protected', array(
            'array &$sqlConditions',
            'array $conditions',
            'array $fetchOptions',
        ));
        $this->_addCustomizableMethod("_prepare{$this->_dataClass['camelCase']}FetchOptionsCustomized", 'protected', array(
            '&$selectFields',
            '&$joinTables',
            'array $fetchOptions',
        ));
        $this->_addCustomizableMethod("_prepare{$this->_dataClass['camelCase']}OrderOptionsCustomized", 'protected', array(
            'array &$choices',
            'array &$fetchOptions',
        ));

        if (!empty($idField)) {
            $this->_addMethod('getList', 'public', array(
                '$conditions' => 'array $conditions = array()',
                '$fetchOptions' => 'array $fetchOptions = array()',
            ), "

\${$variableNamePlural} = \$this->{$getFunctionName}(\$conditions, \$fetchOptions);
\$list = array();

foreach (\${$variableNamePlural} as \$id => \${$variableName}) {
    \$list[\$id] = \${$variableName}" . (empty($this->_dataClass['title_field']) ? ("['{$idField}']") : ((is_array($this->_dataClass['title_field']) ? ("['{$this->_dataClass['title_field'][0]}']['{$this->_dataClass['title_field'][1]}']") : ("['{$this->_dataClass['title_field']}']")))) . ";
}

return \$list;

            ");

            $this->_addMethod("get{$this->_dataClass['camelCase']}ById", 'public', array(
                '$id',
                '$fetchOptions' => 'array $fetchOptions = array()',
            ), "

\${$variableNamePlural} = \$this->{$getFunctionName}(array('{$idField}' => \$id), \$fetchOptions);

return reset(\${$variableNamePlural});

            ");

            $this->_addMethod("get{$this->_dataClass['camelCase']}IdsInRange", 'public', array(
                '$start',
                '$limit',
            ), "

\$db = \$this->_getDb();

return \$db->fetchCol(\$db->limit('
    SELECT {$idField}
    FROM {$tableName}
    WHERE {$idField} > ?
    ORDER BY {$idField}
', \$limit), \$start);

            ");
        }

        $this->_addMethod($getFunctionName, 'public', array(
            '$conditions' => 'array $conditions = array()',
            '$fetchOptions' => 'array $fetchOptions = array()',
        ), "

\$whereConditions = \$this->prepare{$this->_dataClass['camelCase']}Conditions(\$conditions, \$fetchOptions);

\$orderClause = \$this->prepare{$this->_dataClass['camelCase']}OrderOptions(\$fetchOptions);
\$joinOptions = \$this->prepare{$this->_dataClass['camelCase']}FetchOptions(\$fetchOptions);
\$limitOptions = \$this->prepareLimitFetchOptions(\$fetchOptions);

\${$variableNamePlural} = \$this->" . (!empty($idField) ? "fetchAllKeyed" : "_getDb()->fetchAll") . "(\$this->limitQueryResults(\"
    SELECT {$tableAlias}.*
        \$joinOptions[selectFields]
    FROM `{$tableName}` AS {$tableAlias}
        \$joinOptions[joinTables]
    WHERE \$whereConditions
        \$orderClause
    \", \$limitOptions['limit'], \$limitOptions['offset']
)" . (!empty($idField) ? ", '{$idField}'" : "") . ");

        ", '001');

        $this->_addMethod($getFunctionName, 'public', array(
            '$conditions' => 'array $conditions = array()',
            '$fetchOptions' => 'array $fetchOptions = array()',
        ), "

\$this->_{$getFunctionName}Customized(\${$variableNamePlural}, \$fetchOptions);

return \${$variableNamePlural};

        ", '999');

        $this->_addMethod($countFunctionName, 'public', array(
            '$conditions' => 'array $conditions = array()',
            '$fetchOptions' => 'array $fetchOptions = array()',
        ), "

\$whereConditions = \$this->prepare{$this->_dataClass['camelCase']}Conditions(\$conditions, \$fetchOptions);

\$orderClause = \$this->prepare{$this->_dataClass['camelCase']}OrderOptions(\$fetchOptions);
\$joinOptions = \$this->prepare{$this->_dataClass['camelCase']}FetchOptions(\$fetchOptions);
\$limitOptions = \$this->prepareLimitFetchOptions(\$fetchOptions);

return \$this->_getDb()->fetchOne(\"
    SELECT COUNT(*)
    FROM `{$tableName}` AS {$tableAlias}
        \$joinOptions[joinTables]
    WHERE \$whereConditions
\");

        ");

        $this->_addMethod("prepare{$this->_dataClass['camelCase']}Conditions", 'public', array(
            '$conditions' => 'array $conditions = array()',
            '$fetchOptions' => 'array $fetchOptions = array()',
        ), "

\$sqlConditions = array();
\$db = \$this->_getDb();

        ");

        foreach ($conditionFields as $conditionField) {
            $this->_addMethod("prepare{$this->_dataClass['camelCase']}Conditions", '', array(), "

if (isset(\$conditions['{$conditionField}'])) {
    if (is_array(\$conditions['{$conditionField}'])) {
        if (!empty(\$conditions['{$conditionField}'])) {
            // only use IN condition if the array is not empty (nasty!)
            \$sqlConditions[] = \"{$tableAlias}.{$conditionField} IN (\" . \$db->quote(\$conditions['{$conditionField}']) . \")\";
        }
    } else {
        \$sqlConditions[] = \"{$tableAlias}.{$conditionField} = \" . \$db->quote(\$conditions['{$conditionField}']);
    }
}

            ");
        }

        $this->_addMethod("prepare{$this->_dataClass['camelCase']}Conditions", '', array(), "

\$this->_prepare{$this->_dataClass['camelCase']}ConditionsCustomized(\$sqlConditions, \$conditions, \$fetchOptions);

return \$this->getConditionsForClause(\$sqlConditions);

        ");

        $this->_addMethod("prepare{$this->_dataClass['camelCase']}FetchOptions", 'public', array('$fetchOptions' => 'array $fetchOptions = array()'), "

\$selectFields = '';
\$joinTables = '';

\$this->_prepare{$this->_dataClass['camelCase']}FetchOptionsCustomized(\$selectFields, \$joinTables, \$fetchOptions);

return array(
    'selectFields' => \$selectFields,
    'joinTables' => \$joinTables
);

        ");

        $orderChoices = array();
        if (isset($this->_dataClass['fields']['display_order'])) {
            if (isset($this->_dataClass['fields']['lft'])) {
                $orderChoices['display_order'] = sprintf('%s.lft', $tableAlias);
            } else {
                $orderChoices['display_order'] = sprintf('%s.display_order', $tableAlias);
            }
        }
        $orderChoices = DevHelper_Generator_File::varExport($orderChoices);

        $this->_addMethod("prepare{$this->_dataClass['camelCase']}OrderOptions", 'public', array(
            '$fetchOptions' => 'array $fetchOptions = array()',
            '$defaultOrderSql' => '$defaultOrderSql = \'\'',
        ), "

\$choices = {$orderChoices};

\$this->_prepare{$this->_dataClass['camelCase']}OrderOptionsCustomized(\$choices, \$fetchOptions);

return \$this->getOrderByClause(\$choices, \$fetchOptions, \$defaultOrderSql);

        ");

        $this->_generateImageCode();
        $this->_generatePhrasesCode();
        $this->_generateOptionsCode();
        $this->_generateParentCode();

        return parent::_generate();
    }

    protected function _generateImageCode()
    {
        $imageField = DevHelper_Generator_Db::getImageField($this->_dataClass['fields']);
        if ($imageField === false) {
            // no image field...
            return '';
        }

        if (count($this->_dataClass['primaryKey']) > 1) {
            throw new XenForo_Exception(sprintf('Cannot generate image code for %s: too many fields in primary key', $this->_getClassName()));
        }
        $idField = reset($this->_dataClass['primaryKey']);

        $getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
        $variableName = self::getVariableName($this->_addOn, $this->_config, $this->_dataClass);
        $variableNamePlural = self::getVariableNamePlural($this->_addOn, $this->_config, $this->_dataClass);
        $dwClassName = DevHelper_Generator_Code_DataWriter::getClassName($this->_addOn, $this->_config, $this->_dataClass);
        $configPrefix = $this->_config->getPrefix();
        $imagePath = "{$configPrefix}/{$this->_dataClass['camelCase']}";
        $imagePath = strtolower($imagePath);

        $this->_addMethod($getFunctionName, '', array(), "

// build image urls and make them ready for all the records
\$imageSizes = XenForo_DataWriter::create('{$dwClassName}')->getImageSizes();
foreach (\${$variableNamePlural} as &\${$variableName}) {
    \${$variableName}['images'] = array();
    if (!empty(\${$variableName}['{$imageField}'])) {
        foreach (\$imageSizes as \$imageSizeCode => \$imageSize) {
            \${$variableName}['images'][\$imageSizeCode] = \$this->getImageUrl(\${$variableName}, \$imageSizeCode);
        }
    }
}

        ", '100');

        $this->_addMethod('getImageFilePath', 'public static', array(
            sprintf('$%s', $variableName) => sprintf('array $%s', $variableName),
            '$size' => '$size = \'l\'',
        ), "

\$internal = self::_getImageInternal(\${$variableName}, \$size);

if (!empty(\$internal)) {
    return XenForo_Helper_File::getExternalDataPath() . \$internal;
} else {
    return '';
}

        ");

        $this->_addMethod('getImageUrl', 'public static', array(
            sprintf('$%s', $variableName) => sprintf('array $%s', $variableName),
            '$size' => '$size = \'l\'',
        ), "

\$internal = self::_getImageInternal(\${$variableName}, \$size);

if (!empty(\$internal)) {
    return XenForo_Application::\$externalDataUrl . \$internal;
} else {
    return '';
}

        ");

        $this->_addMethod('_getImageInternal', 'protected static', array(
            sprintf('$%s', $variableName) => sprintf('array $%s', $variableName),
            '$size',
        ), "

if (empty(\${$variableName}['{$idField}']) OR empty(\${$variableName}['{$imageField}'])) {
    return '';
}

return '/{$imagePath}/' . \${$variableName}['{$idField}'] . '_' . \${$variableName}['{$imageField}'] . strtolower(\$size) . '.jpg';

        ");

        return true;
    }

    protected function _generatePhrasesCode()
    {
        $variableName = self::getVariableName($this->_addOn, $this->_config, $this->_dataClass);
        $variableNamePlural = self::getVariableNamePlural($this->_addOn, $this->_config, $this->_dataClass);

        if (!empty($this->_dataClass['phrases'])) {
            if (count($this->_dataClass['primaryKey']) > 1) {
                throw new XenForo_Exception(sprintf('Cannot generate phrases code for %s: too many fields in primary key', $this->_getClassName()));
            }
            $idField = reset($this->_dataClass['primaryKey']);

            $statements = '';

            foreach ($this->_dataClass['phrases'] as $phraseType) {
                $getPhraseTitleFunction = self::generateGetPhraseTitleFunctionName($this->_addOn, $this->_config, $this->_dataClass, $phraseType);
                $phraseTitlePrefix = DevHelper_Generator_Phrase::getPhraseName($this->_addOn, $this->_config, $this->_dataClass, $this->_dataClass['name']) . '_';

                $this->_addMethod($getPhraseTitleFunction, 'public static', array('$id'), "

return \"{$phraseTitlePrefix}{\$id}_{$phraseType}\";

                ");

                $statements .= "        '{$phraseType}' => new XenForo_Phrase(self::{$getPhraseTitleFunction}(\${$variableName}['{$idField}'])),\n";
            }

            $getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);

            $this->_addMethod($getFunctionName, '', array(), "

// prepare the phrases
foreach (\${$variableNamePlural} as &\${$variableName}) {
    \${$variableName}['phrases'] = array(
{$statements}    );
}

            ", '300');
        }
    }

    protected function _generateOptionsCode()
    {
        $variableName = self::getVariableName($this->_addOn, $this->_config, $this->_dataClass);
        $variableNamePlural = self::getVariableNamePlural($this->_addOn, $this->_config, $this->_dataClass);
        $optionsFields = DevHelper_Generator_Db::getOptionsFields($this->_dataClass['fields']);

        if (!empty($optionsFields)) {
            $statements = '';

            foreach ($optionsFields as $optionsField) {
                $statements .= "    \${$variableName}['{$optionsField}'] = @unserialize(\${$variableName}['{$optionsField}']);\n";
                $statements .= "    if (empty(\${$variableName}['{$optionsField}'])) \${$variableName}['{$optionsField}'] = array();\n";
            }

            $getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);

            $this->_addMethod($getFunctionName, '', array(), "

// parse all the options fields
foreach (\${$variableNamePlural} as &\${$variableName}) {
{$statements}}

            ", '400');
        }
    }

    protected function _generateParentCode()
    {
        $parentField = DevHelper_Generator_Db::getParentField($this->_dataClass['name'], $this->_dataClass['fields']);
        if ($parentField === false) {
            // no parent field...
            return;
        }

        if (count($this->_dataClass['primaryKey']) > 1) {
            throw new XenForo_Exception(sprintf('Cannot generate parent code for %s: too many fields in primary key', $this->_getClassName()));
        }
        $idField = reset($this->_dataClass['primaryKey']);

        $displayOrderField = false;
        $depthField = false;
        $lftField = false;
        $rgtField = false;
        $breadcrumbField = DevHelper_Generator_Db::getBreadcrumbField($this->_dataClass['name'], $this->_dataClass['fields']);;
        foreach ($this->_dataClass['fields'] as $field) {
            if ($field['name'] == 'display_order') {
                $displayOrderField = $field['name'];
            } elseif ($field['name'] == 'depth') {
                $depthField = $field['name'];
            } elseif ($field['name'] == 'lft') {
                $lftField = $field['name'];
            } elseif ($field['name'] == 'rgt') {
                $rgtField = $field['name'];
            }
        }
        if (empty($displayOrderField) OR empty($depthField) OR empty($lftField) OR empty($rgtField)) {
            // no hierarchy fields
            return;
        }

        $tableName = DevHelper_Generator_Db::getTableName($this->_config, $this->_dataClass['name']);
        $getFunctionName = self::generateGetDataFunctionName($this->_addOn, $this->_config, $this->_dataClass);
        $variableName = self::getVariableName($this->_addOn, $this->_config, $this->_dataClass);
        $variableNamePlural = self::getVariableNamePlural($this->_addOn, $this->_config, $this->_dataClass);

        $rebuildStructureFunctionName = self::generateRebuildStructureFunctionName($this->_addOn, $this->_config, $this->_dataClass);
        $getStructureChangesFunctionName = '_getStructureChanges';
        $groupByParentsFunctionName = self::generateGroupByParentsFunctionName($this->_addOn, $this->_config, $this->_dataClass);

        $this->_addMethod($rebuildStructureFunctionName, '', array(), "

\$grouped = \$this->{$groupByParentsFunctionName}(\$this->{$getFunctionName}(array(), array('order' => '{$displayOrderField}')));

\$db = \$this->_getDb();
XenForo_Db::beginTransaction(\$db);

\$changes = \$this->{$getStructureChangesFunctionName}(\$grouped);
foreach (\$changes AS \$id => \$oneChanges) {
    \$db->update('{$tableName}', \$oneChanges, '{$idField} = ' . \$db->quote(\$id));
}

XenForo_Db::commit(\$db);

return \$changes;

        ");

        $titleFieldBreadcrumb = '';
        if (!empty($this->_dataClass['title_field']) AND !is_array($this->_dataClass['title_field'])) {
            $titleFieldBreadcrumb = "\n                '{$this->_dataClass['title_field']}' => \${$variableName}['{$this->_dataClass['title_field']}'],";
        }

        $breadcrumbStatements = '';
        if (!empty($breadcrumbField)) {
            $breadcrumbStatements = "\n    if (\${$variableName}['category_breadcrumb'] != \$serializedBreadcrumb) {";
            $breadcrumbStatements .= "\n        \$thisChanges['category_breadcrumb'] = \$serializedBreadcrumb;";
            $breadcrumbStatements .= "\n    }";
        }

        $this->_addMethod($getStructureChangesFunctionName, 'protected', array(
            '$grouped' => 'array $grouped',
            '$parentId' => '$parentId = 0',
            '$depth' => '$depth = 0',
            '$startPosition' => '$startPosition = 1',
            'nextPosition' => '&$nextPosition = 0',
            '$breadcrumb' => 'array $breadcrumb = array()',
        ), "

\$nextPosition = \$startPosition;

if (!isset(\$grouped[\$parentId])) {
    return array();
}

\$changes = array();
\$serializedBreadcrumb = serialize(\$breadcrumb);

foreach (\$grouped[\$parentId] AS \$id => \${$variableName}) {
    \$left = \$nextPosition;
    \$nextPosition++;

    \$thisBreadcrumb = \$breadcrumb + array(
            \$id => array(
                '{$idField}' => \$id,{$titleFieldBreadcrumb}
                '{$parentField}' => \${$variableName}['{$parentField}'],
            )
        );

    \$changes += \$this->{$getStructureChangesFunctionName}(
        \$grouped,
        \$id,
        \$depth + 1,
        \$nextPosition,
        \$nextPosition,
        \$thisBreadcrumb
    );

    \$thisChanges = array();
    if (\${$variableName}['depth'] != \$depth) {
        \$thisChanges['depth'] = \$depth;
    }
    if (\${$variableName}['lft'] != \$left) {
        \$thisChanges['lft'] = \$left;
    }
    if (\${$variableName}['rgt'] != \$nextPosition) {
        \$thisChanges['rgt'] = \$nextPosition;
    }{$breadcrumbStatements}

    if (!empty(\$thisChanges)) {
        \$changes[\$id] = \$thisChanges;
    }

    \$nextPosition++;
}

return \$changes;

        ");

        $this->_addMethod($groupByParentsFunctionName, '', array(sprintf('$%s', $variableNamePlural) => sprintf('array $%s', $variableNamePlural)), "

\$grouped = array();
foreach (\${$variableNamePlural} AS \${$variableName}) {
    \$grouped[\${$variableName}['{$parentField}']][\${$variableName}['{$idField}']] = \${$variableName};
}

return \$grouped;

        ");
    }

    protected function _getClassName()
    {
        return self::getClassName($this->_addOn, $this->_config, $this->_dataClass);
    }

    public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $g = new self($addOn, $config, $dataClass);

        return array(
            $g->_getClassName(),
            $g->_generate()
        );
    }

    public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Model_' . $dataClass['camelCase'], $config);
    }

    public static function getVariableName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $variableName = strtolower(substr($dataClass['camelCase'], 0, 1)) . substr($dataClass['camelCase'], 1);
        $variableNamePlural = self::getVariableNamePlural($addOn, $config, $dataClass);

        if ($variableName === $variableNamePlural) {
            $variableName = '_' . $variableName;
        }

        return $variableName;
    }

    public static function getVariableNamePlural(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $variableNamePlural = (empty($dataClass['camelCasePlural']) ? ('All' . $dataClass['camelCase']) : ($dataClass['camelCasePlural']));

        return strtolower(substr($variableNamePlural, 0, 1)) . substr($variableNamePlural, 1);
    }

    public static function generateGetDataFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        return 'get' . (empty($dataClass['camelCasePlural']) ? ('All' . $dataClass['camelCase']) : $dataClass['camelCasePlural']);
    }

    public static function generateCountDataFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        return 'count' . (empty($dataClass['camelCasePlural']) ? ('All' . $dataClass['camelCase']) : $dataClass['camelCasePlural']);
    }

    public static function generateRebuildStructureFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        return 'rebuild' . $dataClass['camelCase'] . 'Structure';
    }

    public static function generateGroupByParentsFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        return 'group' . (empty($dataClass['camelCasePlural']) ? ('All' . $dataClass['camelCase']) : $dataClass['camelCasePlural']) . 'ByParents';
    }

    public static function generateGetPhraseTitleFunctionName(array $addOn, DevHelper_Config_Base $config, array $dataClass, $phraseType)
    {
        $camelCase = ucwords(str_replace('_', ' ', $phraseType));
        return 'getPhraseTitleFor' . $camelCase;
    }

}
