<?php
class DevHelper_Generator_Code_RoutePrefixAdmin {
	public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass, array $info) {
		$className = self::getClassName($addOn, $config, $dataClass);
		
		// create the route prefix first
		$routePrefixModel = XenForo_Model::create('XenForo_Model_RoutePrefix');
		$existed = $routePrefixModel->getPrefixesByRouteType('admin');
		foreach ($existed as $routePrefix) {
			if ($routePrefix['original_prefix'] == $info['routePrefix'] OR $routePrefix['route_class'] == $className) {
				// delete duplicated route prefix
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_RoutePrefix');
				$dw->setExistingData($routePrefix);
				$dw->delete();
			}
		}

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_RoutePrefix');
		$dw->bulkSet(array(
			'original_prefix' => $info['routePrefix'],
			'route_type' => 'admin',
			'route_class' => $className,
			'build_link' => 'data_only',
			'addon_id' => $addOn['addon_id'],
		));
		$dw->save();
		// finished creating our route prefix
		
		
		$contents = <<<EOF
<?php
class $className implements XenForo_Route_Interface {
	public function match(\$routePath, Zend_Controller_Request_Http \$request, XenForo_Router \$router) {
		if (in_array(\$routePath, array('add', 'save'))) {
			\$action = \$routePath;			
		} else {
			\$action = \$router->resolveActionWithIntegerParam(\$routePath, \$request, '{$dataClass['id_field']}');
		}
		return \$router->getRouteMatch('{$info['controller']}', \$action, '{$info['majorSection']}');
	}

	public function buildLink(\$originalPrefix, \$outputPrefix, \$action, \$extension, \$data, array &\$extraParams) {
		if (is_array(\$data)) {
			return XenForo_Link::buildBasicLinkWithIntegerParam(\$outputPrefix, \$action, \$extension, \$data, '{$dataClass['id_field']}');
		} else {
			return XenForo_Link::buildBasicLink(\$outputPrefix, \$action, \$extension);
		}
	}
}
EOF;

		return array($className, $contents);
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'Route_PrefixAdmin_' . $dataClass['camelCase']);
	}
	
	public static function getRoutePrefix(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		$className = self::getClassName($addOn, $config, $dataClass);
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