<?php

class DevHelper_Generator_Code_Common
{
	private $_className = false;
	private $_baseClass = false;
	private $_interfaces = array();
	private $_contants = array();
	private $_properties = array();
	private $_methods = array();
	private $_customizableMethods = array();

	protected function _generate()
	{
		if ($this->_className === false)
		{
			throw new Exception('_setClassName() must be called before calling _generate()');
		}

		$output = '';

		$output .= "<?php\n";
		$output .= "\n";
		$output .= "class {$this->_className}";
		if ($this->_baseClass !== false)
		{
			$output .= " extends {$this->_baseClass}";
		}
		if (!empty($this->_interfaces))
		{
			$isFirstInterface = true;
			$output .= " implements";
			foreach ($this->_interfaces as $interface)
			{
				if ($isFirstInterface)
				{
					$isFirstInterface = false;
				}
				else
				{
					$output .= ",";
				}
				$output .= " {$interface}";
			}
		}
		$output .= "\n{\n";

		$output .= "\n" . DevHelper_Generator_File::COMMENT_AUTO_GENERATED_START . "\n";

		if (!empty($this->_contants))
		{
			$output .= "\n";
			foreach ($this->_contants as $constantName => $constantValue)
			{
				$output .= "\tconst {$constantName} = {$constantValue};\n";
			}
		}

		if (!empty($this->_properties))
		{
			$output .= "\n";
			foreach ($this->_properties as $propertyName => $propertyDeclare)
			{
				if ($propertyDeclare != null)
				{
					$output .= "\t{$propertyDeclare};\n";
				}
				else
				{
					$output .= "\tprotected {$propertyName};\n";
				}
			}
		}

		if (!empty($this->_methods))
		{
			foreach ($this->_methods as $method)
			{
				$output .= "\n";
				$output .= $this->_generateMethod($method);
			}
		}

		$output .= "\n" . DevHelper_Generator_File::COMMENT_AUTO_GENERATED_END . "\n";

		if (!empty($this->_customizableMethods))
		{
			foreach ($this->_customizableMethods as $method)
			{
				$output .= "\n";
				$output .= $this->_generateMethod($method);
			}
		}

		$output .= "\n";
		$output .= "}"; // class ClassName {
		return $output;
	}

	protected function _generateMethod($method, $level = 1)
	{
		$output = "";
		$indentation = str_repeat("\t", $level);

		$output .= $indentation;
		if ($method['visibility'] != '')
		{
			$output .= "{$method['visibility']} ";
		}
		$output .= "function {$method['name']}(";

		$isFirstParam = true;
		foreach ($method['params'] as $paramName => $paramDeclare)
		{
			if ($isFirstParam)
			{
				$isFirstParam = false;
			}
			else
			{
				$output .= ", ";
			}

			if ($paramDeclare != null)
			{
				$output .= "{$paramDeclare}";
			}
			else
			{
				$output .= "{$paramName}";
			}
		}

		$output .= ")\n\t{\n";

		$codeBlocks = $method['code'];
		ksort($codeBlocks);
		$isFirstCodeBlock = true;
		foreach ($codeBlocks as $codeBlock)
		{
			$lines = explode("\n", $codeBlock);
			$codeBlockOutput = '';
			$foundNonEmptyLine = false;

			foreach ($lines as $line)
			{
				$trimmed = trim($line);

				if (strlen($trimmed) == 0)
				{
					// this is an empty line, only output if we have found some non-empty line before
					if ($foundNonEmptyLine)
					{
						$codeBlockOutput .= "\n";
					}
				}
				else
				{
					$foundNonEmptyLine = true;
					$codeBlockOutput .= "{$indentation}\t{$line}\n";
				}
			}

			$codeBlockOutput = rtrim($codeBlockOutput); // remove the last empty lines
			if ($isFirstCodeBlock)
			{
				$isFirstCodeBlock = false;
			}
			else
			{
				$output .= "\n";
			}
			$output .= $codeBlockOutput . "\n";
		}

		$output .= "{$indentation}}\n";

		return $output;
	}

	protected function _setClassName($className)
	{
		$this->_className = $className;
	}

	protected function _setBaseClass($className)
	{
		$this->_baseClass = $className;
	}

	protected function _addInterface($interface)
	{
		$this->_interfaces[] = $interface;
	}

	protected function _addConstant($name, $value)
	{
		$this->_contants[$name] = $value;
	}

	protected function _addProperty($name, $declare = null)
	{
		$this->_properties[$name] = $declare;
	}

	protected function _addMethod($name, $visibility, array $params, $code, $codeId = null)
	{
		$this->_addMethodCommon($this->_methods, $name, $visibility, $params);

		// switch code with existing code (detected by codeId) if needed
		if (!empty($code))
		{
			if ($codeId !== null)
			{
				$this->_methods[$name]['code'][$codeId] = $code;
			}
			else
			{
				$this->_methods[$name]['code'][] = $code;
			}
		}
	}

	protected function _addCustomizableMethod($name, $visibility, array $params)
	{
		$this->_addMethodCommon($this->_customizableMethods, $name, $visibility, $params);

		$this->_customizableMethods[$name]['code'] = array(
				'// customized code goes here',
		);
	}

	protected function _addMethodCommon(array &$methods, $name, $visibility, $params)
	{
		if (!isset($methods[$name]))
		{
			$methods[$name] = array(
					'name' => $name,
					'visibility' => '',
					'params' => array(),
					'code' => array(),
			);
		}

		// we have to use the broader visibility between
		static $visibilities = array('', 'private', 'protected', 'public', 'protected static', 'public static');
		$oldVisibilityLevel = array_search($methods[$name]['visibility'], $visibilities);
		$newVisibilityLevel = array_search($visibility, $visibilities);
		$max = max(array($oldVisibilityLevel, $newVisibilityLevel));
		$methods[$name]['visibility'] = $visibilities[$max];
			
		foreach ($params as $paramName => $paramDeclare)
		{
			if (is_numeric($paramName))
			{
				$paramName = $paramDeclare;
				$paramDeclare = null;
			}
			$methods[$name]['params'][$paramName] = $paramDeclare;
		}
	}
}