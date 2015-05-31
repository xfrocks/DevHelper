<?php

class DevHelper_Generator_Code_RoutePrefixAdmin extends DevHelper_Generator_Code_Common
{
    protected $_addOn = null;
    protected $_config = null;
    protected $_dataClass = null;
    protected $_info = null;

    protected function __construct(array $addOn, DevHelper_Config_Base $config, array $dataClass, array $info)
    {
        $this->_addOn = $addOn;
        $this->_config = $config;
        $this->_dataClass = $dataClass;
        $this->_info = $info;
    }

    protected function _generate()
    {
        $className = $this->_getClassName();

        if (count($this->_dataClass['primaryKey']) > 1) {
            throw new XenForo_Exception(sprintf('Cannot generate %s: too many fields in primary key', $className));
        }
        $idField = reset($this->_dataClass['primaryKey']);

        // create the route prefix first
        /** @var XenForo_Model_RoutePrefix $routePrefixModel */
        $routePrefixModel = XenForo_Model::create('XenForo_Model_RoutePrefix');
        $existed = $routePrefixModel->getPrefixesByRouteType('admin');
        foreach ($existed as $routePrefix) {
            if ($routePrefix['original_prefix'] == $this->_info['routePrefix'] OR $routePrefix['route_class'] == $className) {
                // delete duplicated route prefix
                $dw = XenForo_DataWriter::create('XenForo_DataWriter_RoutePrefix');
                $dw->setExistingData($routePrefix);
                $dw->delete();
            }
        }

        eval("class $className {}");
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_RoutePrefix');
        $dw->bulkSet(array(
            'original_prefix' => $this->_info['routePrefix'],
            'route_type' => 'admin',
            'route_class' => $className,
            'build_link' => 'data_only',
            'addon_id' => $this->_addOn['addon_id'],
        ));
        $dw->save();
        // finished creating our route prefix

        $this->_setClassName($className);
        $this->_addInterface('XenForo_Route_Interface');

        $this->_addMethod('match', 'public', array(
            '$routePath',
            '$request' => 'Zend_Controller_Request_Http $request',
            '$router' => 'XenForo_Router $router',
        ), "

if (in_array(\$routePath, array('add', 'save'))) {
    \$action = \$routePath;
} else {
    \$action = \$router->resolveActionWithIntegerParam(\$routePath, \$request, '{$idField}');
}
return \$router->getRouteMatch('{$this->_info['controller']}', \$action, '{$this->_info['majorSection']}');

        ");

        $this->_addMethod('buildLink', 'public', array(
            '$originalPrefix',
            '$outputPrefix',
            '$action',
            '$extension',
            '$data',
            '$extraParams' => 'array &$extraParams',
        ), "

if (is_array(\$data)) {
    return XenForo_Link::buildBasicLinkWithIntegerParam(\$outputPrefix, \$action, \$extension, \$data, '{$idField}');
} else {
    return XenForo_Link::buildBasicLink(\$outputPrefix, \$action, \$extension);
}

        ");

        return parent::_generate();
    }

    protected function _getClassName()
    {
        return self::getClassName($this->_addOn, $this->_config, $this->_dataClass);
    }

    public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass, array $info)
    {
        $g = new self($addOn, $config, $dataClass, $info);

        return array(
            $g->_getClassName(),
            $g->_generate()
        );
    }

    public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Route_PrefixAdmin_' . $dataClass['camelCase']);
    }

    public static function getRoutePrefix(array $addOn, DevHelper_Config_Base $config, array $dataClass)
    {
        $className = self::getClassName($addOn, $config, $dataClass);

        /** @var XenForo_Model_RoutePrefix $routePrefixModel */
        $routePrefixModel = XenForo_Model::create('XenForo_Model_RoutePrefix');
        $routePrefixes = $routePrefixModel->getPrefixesByAddOnGroupedByRouteType($addOn['addon_id']);
        if (!empty($routePrefixes['admin'])) {
            foreach ($routePrefixes['admin'] as $routePrefix) {
                if ($routePrefix['route_class'] == $className) {
                    return $routePrefix['original_prefix'];
                }
            }
        }

        return strtolower($addOn['addon_id'] . '-' . $dataClass['name']);
    }

}
