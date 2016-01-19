<?php

abstract class DevHelper_Config_Base
{
    protected $_dataClasses = array();
    protected $_dataPatches = array();
    protected $_exportPath = false;
    protected $_exportIncludes = array();
    protected $_exportExcludes = array();
    protected $_exportAddOns = array();
    protected $_exportStyles = array();
    protected $_options = array();

    protected function _upgrade()
    {
        return true;
    }

    public function upgradeConfig()
    {
        $result = $this->_upgrade();

        return empty($result);
    }

    public function getDataClasses()
    {
        return $this->_dataClasses;
    }

    public function getDataClass($name)
    {
        $name = $this->_normalizeDbName($name);

        if (!$this->checkDataClassExists($name))
            return array();

        $dataClass = $this->_dataClasses[$name];

        foreach ($dataClass['files'] as &$file) {
            if (!empty($file)) {
                $path = DevHelper_Generator_File::getClassPath($file['className']);
                $hash = DevHelper_Generator_File::calcHash($path);
                if ($hash != $file['hash']) {
                    $file['changed'] = true;
                }
            }
        }

        return $dataClass;
    }

    public function addDataClass($name, $fields = array(), $primaryKey = false, $indeces = array(), $extraData = array())
    {
        $name = $this->_normalizeDbName($name);

        $this->_dataClasses[$name] = array(
            'name' => $name,
            'camelCase' => DevHelper_Generator_File::getCamelCase($name),
            'camelCasePlural' => false,
            'camelCaseWSpace' => ucwords(str_replace('_', ' ', $name)),
            'camelCasePluralWSpace' => false,
            'fields' => array(),
            'phrases' => array(),
            'title_field' => false,
            'primaryKey' => false,
            'indeces' => array(),

            'files' => array(
                'data_writer' => false,
                'model' => false,
                'route_prefix_admin' => false,
                'controller_admin' => false,
            ),
        );

        foreach ($extraData as $key => $value) {
            $this->_dataClasses[$name][$key] = $value;
        }

        foreach ($fields as $fieldName => $fieldInfo) {
            $fieldInfo = array_merge(array('name' => $fieldName), $fieldInfo);
            $this->addDataClassField($name, $fieldInfo);
        }
        $this->addDataClassPrimaryKey($name, $primaryKey);
        foreach ($indeces as $index) {
            $this->addDataClassIndex($name, $index);
        }

        return true;
    }

    public function addDataClassField($name, array $field)
    {
        $name = $this->_normalizeDbName($name);
        $field['name'] = $this->_normalizeDbName($field['name']);
        $field['type'] = strtolower($field['type']);
        if (!in_array($field['type'], DevHelper_Generator_Db::getDataTypes()))
            $field['type'] = XenForo_DataWriter::TYPE_SERIALIZED;

        if (empty($this->_dataClasses[$name]['title_field']) AND in_array($field['type'], array(XenForo_DataWriter::TYPE_STRING))) {
            $this->_dataClasses[$name]['title_field'] = $field['name'];
        }

        $this->_dataClasses[$name]['fields'][$field['name']] = $field;

        return true;
    }

    public function addDataClassPrimaryKey($name, $fields)
    {
        $name = $this->_normalizeDbName($name);

        if (!is_array($fields)) {
            $fields = array($fields);
        }

        $primaryKey = array();

        foreach ($fields as $field) {
            $field = $this->_normalizeDbName($field);
            if (!$this->checkDataClassFieldExists($name, $field)) {
                return false;
            }
            $primaryKey[] = $field;
        }

        if (!empty($primaryKey)) {
            $this->_dataClasses[$name]['primaryKey'] = $primaryKey;
            return true;
        }

        return false;
    }

    public function addDataClassIndex($name, array $index)
    {
        $name = $this->_normalizeDbName($name);
        $fields = array();

        if (!is_array($index['fields'])) {
            $index['fields'] = array($index['fields']);
        }

        foreach ($index['fields'] as $field) {
            $field = $this->_normalizeDbName($field);
            if ($this->checkDataClassFieldExists($name, $field)) {
                $fields[] = $field;
            } else {
                return false;
            }
        }
        if (empty($fields))
            return false;

        if (isset($index['name'])) {
            $indexName = $index['name'];
        } else {
            $indexName = implode('_', $fields);
        }

        $type = !empty($index['type']) ? $index['type'] : 'NORMAL';
        if (!in_array(strtoupper($type), DevHelper_Generator_Db::getIndexTypes())) {
            $type = 'NORMAL';
        }

        $this->_dataClasses[$name]['indeces'][$indexName] = array(
            'name' => $indexName,
            'fields' => $fields,
            'type' => strtoupper($type),
        );

        return true;
    }

    public function updateDataClassFile($name, $fileType, $className, $path)
    {
        $name = $this->_normalizeDbName($name);

        $this->_dataClasses[$name]['files'][$fileType] = array(
            'className' => $className,
            'hash' => DevHelper_Generator_File::calcHash($path),
        );
    }

    public function checkDataClassExists($name)
    {
        $name = $this->_normalizeDbName($name);

        return isset($this->_dataClasses[$name]);
    }

    public function checkDataClassFieldExists($name, $field)
    {
        $name = $this->_normalizeDbName($name);
        $field = $this->_normalizeDbName($field);

        return isset($this->_dataClasses[$name]['fields'][$field]);
    }

    public function getDataPatches()
    {
        return $this->_dataPatches;
    }

    public function addDataPatch($table, array $patch)
    {
        if (!empty($patch['index'])) {
            if (!isset($patch['name'])) {
                $patch['name'] = implode('_', $patch['fields']);
            }
            $patchKey = 'index::' . $patch['name'];

            if (isset($patch['type'])) {
                $patch['type'] = strtoupper($patch['type']);
            } else {
                $patch['type'] = '';
            }
            if (!in_array($patch['type'], DevHelper_Generator_Db::getIndexTypes())) {
                $patch['type'] = 'NORMAL';
            }

            if (!isset($patch['fields'])) {
                throw new XenForo_Exception('addDataPatch(index=true) requires `fields`');
            }
            if (!is_array($patch['fields'])) {
                $patch['fields'] = array(strval($patch['fields']));
            }
        } else {
            $patch['name'] = DevHelper_Generator_Db::getFieldName($this, $this->_normalizeDbName($patch['name']));
            $patchKey = $patch['name'];

            if (!isset($patch['type'])) {
                throw new XenForo_Exception('addDataPatch() requires `type`');
            }
            $patch['type'] = strtolower($patch['type']);
            if (!in_array($patch['type'], DevHelper_Generator_Db::getDataTypes())) {
                $patch['type'] = XenForo_DataWriter::TYPE_SERIALIZED;
            }
        }

        $this->_dataPatches[$table][$patchKey] = $patch;

        return true;
    }

    public function setExportPath($path)
    {
        if (is_dir($path) AND is_writable($path)) {
            $this->_exportPath = $path;
        } else {
            die('EXPORT PATH IS NOT WRITABLE');
        }
    }

    public function getExportPath()
    {
        $path = $this->_exportPath;

        if ($path === false) {
            return false;
        } elseif (is_dir($path) AND is_writable($path)) {
            return $path;
        } else {
            var_dump($path, is_dir($path), is_writable($path));
            die('EXPORT PATH IS NOT WRITABLE');
        }
    }

    public function getExportIncludes()
    {
        return $this->_exportIncludes;
    }

    public function getExportExcludes()
    {
        return $this->_exportExcludes;
    }

    public function getExportAddOns()
    {
        return $this->_exportAddOns;
    }

    public function getExportStyles()
    {
        return $this->_exportStyles;
    }

    public function getPrefix()
    {
        if (!empty($this->_options['prefix'])) {
            return $this->_options['prefix'];
        }

        $configClassName = get_class($this);
        $parts = explode('_', $configClassName);
        array_pop($parts);
        array_pop($parts);
        $prefix = implode('_', $parts);

        return $prefix;
    }

    public function getClassPrefix()
    {
        if (!empty($this->_options['classPrefix'])) {
            return $this->_options['classPrefix'];
        }

        return $this->getPrefix();
    }

    public function outputSelf()
    {
        $className = get_class($this);

        $dataClasses = DevHelper_Generator_File::varExport($this->_dataClasses);
        $dataPatches = DevHelper_Generator_File::varExport($this->_dataPatches);
        $exportPath = DevHelper_Generator_File::varExport($this->_exportPath);
        $exportIncludes = DevHelper_Generator_File::varExport($this->_exportIncludes);
        $exportExcludes = DevHelper_Generator_File::varExport($this->_exportExcludes);
        $exportAddOns = DevHelper_Generator_File::varExport($this->_exportAddOns);
        $exportStyles = DevHelper_Generator_File::varExport($this->_exportStyles);
        $options = DevHelper_Generator_File::varExport($this->_options);

        $contents = <<<EOF
<?php

class $className extends DevHelper_Config_Base
{
    protected \$_dataClasses = $dataClasses;
    protected \$_dataPatches = $dataPatches;
    protected \$_exportPath = $exportPath;
    protected \$_exportIncludes = $exportIncludes;
    protected \$_exportExcludes = $exportExcludes;
    protected \$_exportAddOns = $exportAddOns;
    protected \$_exportStyles = $exportStyles;
    protected \$_options = $options;

    /**
     * Return false to trigger the upgrade!
     **/
    protected function _upgrade()
    {
        return true; // remove this line to trigger update

        /*
        \$this->addDataClass(
            'name_here',
            array( // fields
                'field_here' => array(
                    'type' => 'type_here',
                    // 'length' => 'length_here',
                    // 'required' => true,
                    // 'allowedValues' => array('value_1', 'value_2'),
                    // 'default' => 0,
                    // 'autoIncrement' => true,
                ),
                // other fields go here
            ),
            array('primary_key_1', 'primary_key_2'), // or 'primary_key', both are okie
            array( // indeces
                array(
                    'fields' => array('field_1', 'field_2'),
                    'type' => 'NORMAL', // UNIQUE or FULLTEXT
                ),
            ),
        );
        */
    }
}
EOF;

        return $contents;
    }

    protected function _normalizeDbName($name)
    {
        return $this->_normalizeName($name);
    }

    protected function _normalizeName($name)
    {
        return preg_replace('/[^a-zA-Z_]/', '', $name);
    }

}
