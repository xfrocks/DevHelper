<?php
class DevHelper_Generator_File {
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
		if (strpos($addOnId, '_') !== false) {
			$className = ucwords(str_replace('_', ' ', $addOnId));
		} else {
			$className = $addOnId;
		}
		$className = preg_replace('/[^a-zA-Z]/', '', $className);
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
				$oldBackupClassName = self::_getBackupClassName($className);
				$oldBackupPath = self::getClassPath($oldBackupClassName);
				if (file_exists($oldBackupPath) AND false) {
					// backup found
					// try to merge
					$oldBackupContents = self::fileGetContents($oldBackupPath);
					
					$lineSeparator = "\n";
					$oldBackupLines = explode($lineSeparator, $oldBackupContents);
					$oldLines = explode($lineSeparator, $oldContents);
					$lines = explode($lineSeparator, $contents);
					
					$diffFromOld = self::diff($oldBackupLines, $oldLines);
					$diffFromNow = self::diff($oldBackupLines, $lines);
					
					$merged = array();
					$keyDiffOldDelta = 0;
					$keyDiffNewDelta = 0;
					foreach ($oldBackupLines as $oldBackupKey => $line) {
						for ($keyDiffOld = $oldBackupKey + $keyDiffOldDelta; $keyDiffOld < count($diffFromOld); $keyDiffOld + 1) {
							if (is_string($diffFromOld[$keyDiffOld]) AND $diffFromOld[$keyDiffOld] == $line) {
								$keyDiffOldDelta = $keyDiffOld - $oldBackupKey;
								break;
							} elseif (is_array($diffFromOld[$keyDiffOld]) AND (in_array($line, $diffFromOld[$keyDiffOld]['d']) OR (in_array($line, $diffFromOld[$keyDiffOld]['i'])))) {
								$keyDiffOldDelta = $keyDiffOld - $oldBackupKey;
								break;
							}
						}
						for ($keyDiffOld = $oldBackupKey + $keyDiffOldDelta; $keyDiffOld < count($diffFromOld); $keyDiffOld + 1) {
							if (is_string($diffFromOld[$keyDiffOld]) AND $diffFromOld[$keyDiffOld] == $line) {
								$keyDiffOldDelta = $keyDiffOld - $oldBackupKey;
								break;
							} elseif (is_array($diffFromOld[$keyDiffOld]) AND (in_array($line, $diffFromOld[$keyDiffOld]['d']) OR (in_array($line, $diffFromOld[$keyDiffOld]['i'])))) {
								$keyDiffOldDelta = $keyDiffOld - $oldBackupKey;
								break;
							}
						}
						
						if (empty($changedFromOld[$key]) AND empty($changedFromNow[$key])) {
							$merged[$key] = $line;
						} else {
							if (empty($changedFromOld[$key]) OR empty($changedFromNow[$key])) {
								// no conflict
								$changed = !empty($changedFromOld[$key]) ? $changedFromOld[$key] : $changedFromNow[$key];
							} else {
								echo 'CONFLICT<br/>';
								echo 'LINE: ' . $key . '<br/>';
								echo 'OLD:<br/>';
								echo nl2br(var_export($changedFromOld[$key], true));
								echo 'NOW:<br/>';
								echo nl2br(var_export($changedFromNow[$key], true));
								die('CONFICT');
							}
							
							$merged[$key] = $changed['i'];
							if (!in_array($line, $changed['d'])) {
								$merged[$key][] = $line;
							}
						}
					}
								
					$mergedContents = array();
					foreach ($merged as $line) {
						if (is_array($line)) {
							$mergedContents[] = implode($lineSeparator, $line);
						} else {
							$mergedContents[] = $line;
						}
					}
					$mergedContents = implode($lineSeparator, $mergedContents);
					
					die($mergedContents);
					
					self::filePutContents($path, $mergedContents);
				} else {
					// no backup found
					self::filePutContents($path, $contents);
				}
			}
		} else {
			// no existed file 
			self::filePutContents($path, $contents);
		}
		
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
		file_put_contents($path, $contents);
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
	
	public static function diff($old, $new){
		$maxlen = 0;
		foreach($old as $oindex => $ovalue){
			$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
					$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				if($matrix[$oindex][$nindex] > $maxlen){
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}	
		}
		if($maxlen == 0) return array(array('d' => $old, 'i' => $new));
		return array_merge(
			self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
	}
}