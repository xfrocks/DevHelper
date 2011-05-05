<?php
class DevHelper_Generator_File {
	const COMMENT_AUTO_GENERATED_START = '/* Start auto-generated lines of code. Change made will be overwriten... */';
	const COMMENT_AUTO_GENERATED_END = '/* End auto-generated lines of code. Feel free to make changes below */';
	
	public static function calcHash($path) {
		if (file_exists($path)) {
			$contents = file_get_contents($path);
			return md5($contents);
		} else {
			return false;
		}
	}
	
	public static function getCamelCase($name) {
		return preg_replace('/[^a-zA-Z]/', '', ucwords(str_replace('_', ' ', $name)));
	}
	
	public static function getClassName($addOnId, $subClassName = false) {
		$className = $addOnId;
		$className = preg_replace('/[^a-zA-Z_]/', '', $className);
		if ($subClassName) {
			$className .= '_' . $subClassName;
		}
		
		return $className;
	}
	
	public static function getClassPath($className) {
		return XenForo_Autoloader::getInstance()->autoloaderClassToFile($className);
	}
	
	public static function write($className, $contents) {
		$path = self::getClassPath($className);
		
		if (file_exists($path)) {
			// existed file
			$oldContents = self::fileGetContents($path);
			copy($path, $path . '.' . XenForo_Application::$time);
			
			if ($oldContents == $contents) {
				// same content
				// do notning
			} else {
				// diffrent content
				// try to replace the auto generated code only
				$startPosOld = strpos($oldContents, self::COMMENT_AUTO_GENERATED_START);
				$endPosOld = strpos($oldContents, self::COMMENT_AUTO_GENERATED_END, $startPosOld);
				
				if ($startPosOld !== false AND $endPosOld !== false AND $endPosOld > $startPosOld) {
					// found our comments in old contents
					$startPos = strpos($contents, self::COMMENT_AUTO_GENERATED_START);
					$endPos = strpos($contents, self::COMMENT_AUTO_GENERATED_END, $startPos);
					if ($startPos !== false AND $endPos !== false AND $endPos > $startPos) {
						// found our comments in new contents
						
						$replacement = substr($contents, $startPos, $endPos - $startPos);
						$start = $startPosOld;
						$length = $endPosOld - $startPosOld;
						
						$contents = substr_replace($oldContents, $replacement, $start, $length);
					}
				}
			}
		}
		
		self::filePutContents($path, $contents);
		
		if (strpos($className, 'DevHelper_Generated') === false) {
			$backupClassName = self::_getBackupClassName($className);
			$backupPath = self::getClassPath($backupClassName);
			self::filePutContents($backupPath, $contents);
		}
		
		return $path;
	}
	
	protected static function _getBackupClassName($className) {
		$parts = explode('_', $className);
		$prefix = array_shift($parts);
		$suffix = implode('_', $parts);
		return $prefix . '_DevHelper_Generated_' . $suffix;
	}
	
	public static function fileGetContents($path) {
		$contents = file_get_contents($path);
		$contents = preg_replace("/(\r|\n)+/", "\n", $contents);
		return $contents;
	}
	
	public static function filePutContents($path, $contents) {
		$dir = dirname($path);
		XenForo_Helper_File::createDirectory($dir, true);
		
		$lines = explode("\n", $contents);
		$linesTrimmed = array();
		foreach ($lines as $line) {
			if (trim($line) == '') {
				$linesTrimmed[] = '';
			} else {
				$linesTrimmed[] = $line;
			}
		}
		file_put_contents($path, implode("\n", $linesTrimmed));
		
		XenForo_Helper_File::makeWritableByFtpUser($path);
	}
	
	public static function fileExport(array $addOn, DevHelper_Config_Base $config, $exportPath) {
		$list = array(
			// always export `library/addOnId` directory
			XenForo_Autoloader::getInstance()->getRootDir() . '/' . self::getClassName($addOn['addon_id']),
			
			// try to export `js/addOnId` too
			XenForo_Autoloader::getInstance()->getRootDir() . '/../js/' . self::getClassName($addOn['addon_id']),
		);
		
		$rootPath = realpath(XenForo_Application::getInstance()->getRootDir());
		if (strpos($exportPath, 'upload') === false) {
			$exportPath .= '/upload';
		}
		XenForo_Helper_File::createDirectory($exportPath /*, true */ );
		$exportPath = realpath($exportPath);
		$options = array(
			'extensions' => array('php', 'htm', 'html', 'js', 'css', 'jpg', 'jpeg', 'png', 'gif'),
		);
		
		foreach ($list as $entry) {
			self::_fileExport(realpath($entry), $exportPath, $rootPath, $options);
		}
		
		$xmlDirPath = dirname($exportPath);
		$xmlPath = $xmlDirPath . '/addon-' . $addOn['addon_id'] . '.xml';
		XenForo_Model::create('XenForo_Model_AddOn')->getAddOnXml($addOn)->save($xmlPath);
		echo "Exported       $xmlPath\n"; 
	}
	
	protected static function _fileExport($entry, &$exportPath, &$rootPath, &$options) {
		if (empty($entry)) return;
				
		$relativePath = str_replace($rootPath, '', $entry);
		
		if (is_dir($entry)) {
			echo "Browsing       $relativePath\n";
			
			$children = array();
			
			$dh = opendir($entry);
			while ($child = readdir($dh)) {
				if ($child != '.' AND $child != '..') {
					$children[] = $child;
				}
			}
			
			foreach ($children as $child) {
				self::_fileExport(realpath($entry . '/' . $child), $exportPath, $rootPath, $options);
			}
		} else {
			echo "Exporting      $relativePath ";
			
			$ext = XenForo_Helper_File::getFileExtension($entry);
			if (in_array($ext, $options['extensions'])) {
				if (strpos($entry, 'DevHelper') === false) {
					$entryExportPath = $exportPath . '/' . $relativePath;
					
					$entryExportDir = dirname($entryExportPath);
					XenForo_Helper_File::createDirectory($entryExportDir, true);
					
					if (@copy($entry, $entryExportPath)) {
						echo 'OK';
					}
				}				
			}
			
			echo "\n";
		}
	}
	
	public static function varExport($var, $level = 1, $linePrefix = "\t", $noKey = false) {
		$output = '';
		
		if (is_array($var)) {
			$arrayVars = array();
			$multiLine = false;
			$keyValueLength = 0;
			$allKeysAreInt = true;
			foreach ($var as $key => $value) {
				$arrayVars[$key] = self::varExport($value, $level + 1, $linePrefix);
				if (is_array($value) AND count($value) > 1) {
					$multiLine = true;
				}
				if (strpos($arrayVars[$key], "\n") !== false) {
					$multiLine = true;
				}
				
				$keyValueLength += strlen($key);
				if (is_array($value)) {
					$keyValueLength += strlen(var_export($value, true));
				} else {
					$keyValueLength += strlen($value);
				}
				
				if (!is_int($key)) $allKeysAreInt = false;
			}
			if ($keyValueLength > 100) {
				$multiLine = true;
			}
			if ($allKeysAreInt) {
				$noKey = true;
			}
			
			$output .= 'array(';
			$first = true;
			foreach ($arrayVars as $key => $str) {
				if ($multiLine) {
					if ($first) $first = false; else $output .= ',';
					$output .= "\n" . str_repeat($linePrefix, $level + 1);
				} else {
					if ($first) $first = false; else $output .= ', ';
				}
				if (empty($noKey)) $output .= var_export($key, true) . ' => '; 
				$output .= $str;
			}
			
			if ($multiLine) $output .= "\n" . str_repeat($linePrefix, $level);
			$output .= ')';
		} else {
			$tmp = var_export($var, true);
			if (strpos($tmp, "\n") !== false) {
				$tmp = str_replace("\n", "\n" . str_repeat($linePrefix, $level), $tmp);
			}
			
			$output .= $tmp;
		}
		
		return $output;
	}
}