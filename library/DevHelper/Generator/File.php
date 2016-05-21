<?php

class DevHelper_Generator_File
{
    const COMMENT_AUTO_GENERATED_START = '/* Start auto-generated lines of code. Change made will be overwriten... */';
    const COMMENT_AUTO_GENERATED_END = '/* End auto-generated lines of code. Feel free to make changes below */';

    public static function minifyJs(array $jsFiles)
    {
        if (true) {
            foreach ($jsFiles as $path) {
                $pathInfo = pathinfo($path);

                if (strpos($path, 'js/xenforo/') !== false) {
                    // ignore xenforo files
                    continue;
                }

                $dirName = $pathInfo['dirname'];
                $realDirName = realpath($dirName);
                $fullDirName = realpath($realDirName . '/full');
                $baseName = $pathInfo['basename'];

                $minPath = $realDirName . '/' . $baseName;
                $fullPath = $fullDirName . '/' . $baseName;

                if (file_exists($fullPath) AND (!file_exists($minPath) OR (filemtime($fullPath) > filemtime($minPath)))) {
                    $fullContents = file_get_contents($fullPath);

                    if (strpos($fullContents, '/* no minify */') === false) {
                        require_once(dirname(__FILE__) . '/../Lib/jsmin-php/jsmin.php');
                        $minified = JSMin::minify($fullContents);
                    } else {
                        // the file requested not to be minify... (debugging?)
                        $minified = $fullContents;
                    }

                    self::writeFile($minPath, $minified, false, false);
                }
            }
        }
    }

    public static function calcHash($path)
    {
        if (file_exists($path)) {
            $contents = file_get_contents($path);
            return md5($contents);
        } else {
            return false;
        }
    }

    public static function getCamelCase($name)
    {
        return preg_replace('/[^a-zA-Z]/', '', ucwords(str_replace('_', ' ', $name)));
    }

    public static function getClassName($addOnId, $subClassName = false, DevHelper_Config_Base $config = null)
    {
        static $classNames = array();

        $hash = $addOnId . $subClassName;

        if (empty($classNames[$hash])) {
            if ($subClassName === 'DevHelper_Config') {
                $tmp = $addOnId;
                $tmp = preg_replace('/[^a-zA-Z_0-9]/', '', $tmp);

                // read root directory (./library), trying to pickup matched directory name
                $tmp = self::getClassNameInDirectory(XenForo_Autoloader::getInstance()->getRootDir(), $tmp);

                $className = $tmp . '_DevHelper_Config';
            } else {
                if ($config === null) {
                    throw new XenForo_Exception(sprintf('%s requires $config when $subClassName=%s', __METHOD__,
                        $subClassName));
                }

                $className = rtrim(sprintf('%s_%s', $config->getClassPrefix(), $subClassName), '_');
            }

            $classNames[$hash] = $className;
        }

        return $classNames[$hash];
    }

    public static function getClassNameInDirectory($dir, $className, $getPath = false)
    {
        $found = false;
        $foundPath = false;

        $classNameLower = strtolower($className);
        $classNameLower2 = str_replace('_', '', $classNameLower);
        // also support id likes add_on to map to folder AddOn
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            $fileLower = strtolower($file);
            if ($fileLower == $classNameLower OR $fileLower == $classNameLower2) {
                // we found it!
                $found = $file;
                $foundPath = sprintf('%s/%s', $dir, $file);
                break;
            }
        }
        closedir($dh);

        if (empty($found)) {
            // try harder, support addon_id to be mapped to Addon/Id (case insensitive)
            if (strpos($className, '_') !== false) {
                $parts = explode('_', $className);
                $partsCount = count($parts);
                $partsFound = array();
                while (!empty($parts)) {
                    $part = array_shift($parts);
                    $partDir = $dir . '/' . implode('/', $partsFound);
                    $partFound = self::getClassNameInDirectory($partDir, $part);
                    if ($partFound === false) {
                        // failed...
                        break;
                    }

                    $partsFound[$partDir] = $partFound;
                }

                if (count($partsFound) === $partsCount) {
                    $found = implode('_', $partsFound);

                    $partsFoundCloned = $partsFound;
                    while (count($partsFoundCloned) > 1) {
                        array_shift($partsFoundCloned);
                    }
                    $partDirs = array_keys($partsFoundCloned);
                    $lastPartDir = reset($partDirs);
                    $lastPartFound = reset($partsFoundCloned);
                    $foundPath = sprintf('%s/%s', $lastPartDir, $lastPartFound);
                }
            }
        }

        if ($getPath) {
            return $foundPath;
        } else {
            return $found;
        }
    }

    public static function getClassPath($className)
    {
        return XenForo_Autoloader::getInstance()->autoloaderClassToFile($className);
    }

    public static function getAddOnXmlPath(
        array $addOn,
        array $exportAddOn = null,
        DevHelper_Config_Base $config = null
    ) {
        if ($config === null) {
            /** @var DevHelper_Model_Config $configModel */
            $configModel = XenForo_Model::create('DevHelper_Model_Config');
            $config = $configModel->loadAddOnConfig($addOn);
        }

        $libraryPath = self::getClassNameInDirectory(
            XenForo_Autoloader::getInstance()->getRootDir(),
            self::getClassName($addOn['addon_id'], false, $config),
            true
        );

        $addOnId = (!empty($exportAddOn) ? $exportAddOn['addon_id'] : $addOn['addon_id']);

        return $libraryPath . '/addon-' . $addOnId . '.xml';
    }

    public static function getStyleXmlPath(array $addOn, array $style, DevHelper_Config_Base $config)
    {
        $libraryPath = self::getClassNameInDirectory(
            XenForo_Autoloader::getInstance()->getRootDir(),
            self::getClassName($addOn['addon_id'], false, $config),
            true
        );

        return $libraryPath . '/style-' . $style['title'] . '.xml';
    }

    public static function writeFile($path, $contents, $backUp, $isAutoGenerated)
    {
        $skip = false;

        if (file_exists($path)) {
            // existed file
            $oldContents = self::fileGetContents($path);

            if ($oldContents == $contents) {
                // same content
                $skip = true;
            } else {
                if ($backUp) {
                    if (strpos($path, 'FileSums.php') !== false) {
                        // writing FileSums.php
                        // this file is generated so many times that it's annoying
                        // so we will skip saving a copy of it...
                    } else {
                        copy($path, $path . '.' . XenForo_Application::$time . '.devhelper');
                    }
                }

                if ($isAutoGenerated AND XenForo_Helper_File::getFileExtension($path) === 'php') {
                    // different php content
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
        }

        if (!$skip) {
            return self::filePutContents($path, $contents);
        } else {
            return 'skip';
        }
    }

    public static function writeClass($className, $contents)
    {
        $path = self::getClassPath($className);

        self::writeFile($path, $contents, true, true);

        if (strpos($className, 'DevHelper_Generated') === false) {
            $backupClassName = self::_getBackupClassName($className);
            $backupPath = self::getClassPath($backupClassName);
            self::writeFile($backupPath . '.devhelper', $contents, false, false);
        }

        return $path;
    }

    protected static function _getBackupClassName($className)
    {
        $parts = explode('_', $className);
        $prefix = array_shift($parts);
        $suffix = implode('_', $parts);
        return $prefix . '_DevHelper_Generated_' . $suffix;
    }

    public static function fileGetContents($path)
    {
        if (is_readable($path)) {
            $contents = file_get_contents($path);

            return $contents;
        } else {
            return false;
        }
    }

    public static function filePutContents($path, $contents)
    {
        $dir = dirname($path);
        XenForo_Helper_File::createDirectory($dir);
        if (!is_dir($dir) OR !is_writable($dir)) {
            return false;
        }

        if (file_put_contents($path, $contents) > 0) {
            XenForo_Helper_File::makeWritableByFtpUser($path);
            return true;
        }

        return false;
    }

    public static function generateHashesFile(array $addOn, DevHelper_Config_Base $config, array $directories)
    {
        $hashes = array();

        /** @var XenForo_Application $application */
        $application = XenForo_Application::getInstance();
        $root = rtrim(realpath($application->getRootDir()), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $excludes = $config->getExportExcludes();

        foreach ($directories as $key => $directory) {
            $directoryHashes = XenForo_Helper_Hash::hashDirectory($directory, array(
                '.php',
                '.js'
            ));

            foreach ($directoryHashes as $filePath => $hash) {
                if (strpos($filePath, 'DevHelper') === false AND strpos($filePath, 'FileSums') === false) {
                    $relative = str_replace($root, '', $filePath);

                    $excluded = false;
                    foreach ($excludes as $exclude) {
                        if (strpos($relative, $exclude) === 0) {
                            $excluded = true;
                            break;
                        }
                    }
                    if ($excluded) {
                        continue;
                    }

                    $hashes[$relative] = $hash;
                }
            }
        }

        $fileSumsClassName = self::getClassName($addOn['addon_id'], 'FileSums', $config);
        $fileSumsContents = XenForo_Helper_Hash::getHashClassCode($fileSumsClassName, $hashes);
        self::writeClass($fileSumsClassName, $fileSumsContents);
    }

    public static function fileExport(array $addOn, DevHelper_Config_Base $config, $exportPath)
    {
        $list = array();

        $classNameByAddOnId = self::getClassName($addOn['addon_id'], false, $config);

        $list['library'] = self::getClassNameInDirectory(XenForo_Autoloader::getInstance()->getRootDir(),
            $classNameByAddOnId, true);
        if (empty($list['library'])) {
            throw new XenForo_Exception(sprintf('`library` not found for %s', $addOn['addon_id']));
        }

        $jsPath = self::getClassNameInDirectory(realpath(XenForo_Autoloader::getInstance()->getRootDir() . '/../js'),
            $classNameByAddOnId, true);
        if (!empty($jsPath)) {
            if (is_dir($jsPath)) {
                $list['js'] = $jsPath;
            }
        }

        $stylesDefaultPath = self::getClassNameInDirectory(realpath(XenForo_Autoloader::getInstance()->getRootDir() . '/../styles/default'),
            $classNameByAddOnId, true);
        if (!empty($stylesDefaultPath)) {
            if (is_dir($stylesDefaultPath)) {
                $list['styles_default'] = $stylesDefaultPath;
            }
        }

        $exportIncludes = $config->getExportIncludes();
        foreach ($exportIncludes as $exportInclude) {
            $exportIncludePath = XenForo_Autoloader::getInstance()->getRootDir() . '/../' . $exportInclude;

            if (file_exists($exportIncludePath)) {
                $list[$exportInclude] = $exportIncludePath;
            }
        }

        // save add-on XML
        $xmlPath = self::getAddOnXmlPath($addOn, null, $config);

        /** @var XenForo_Model_AddOn $addOnModel */
        $addOnModel = XenForo_Model::create('XenForo_Model_AddOn');
        $addOnModel->getAddOnXml($addOn)->save($xmlPath);
        echo "Exported       $xmlPath ($addOn[version_string]/$addOn[version_id])\n";
        DevHelper_Helper_Phrase::parseXmlForPhraseTracking($xmlPath);

        $exportAddOns = $config->getExportAddOns();
        foreach ($exportAddOns as $exportAddOnId) {
            $exportAddOn = $addOnModel->getAddOnById($exportAddOnId);
            if (empty($exportAddOn)) {
                die(sprintf("Could not find add-on %s\n", $exportAddOnId));
            }

            $exportAddOnPath = self::getAddOnXmlPath($addOn, $exportAddOn, $config);
            $addOnModel->getAddOnXml($exportAddOn)->save($exportAddOnPath);
            echo "Exported       $exportAddOnPath ($exportAddOn[version_string]/$exportAddOn[version_id])\n";
        }

        $exportStyles = $config->getExportStyles();

        if (!empty($exportStyles)) {
            /** @var XenForo_Model_Style $styleModel */
            $styleModel = $addOnModel->getModelFromCache('XenForo_Model_Style');
            $styles = $styleModel->getAllStyles();
            $exportedStyleCount = 0;

            foreach ($styles as $style) {
                if (in_array($style['title'], $exportStyles, true)) {
                    $stylePath = self::getStyleXmlPath($addOn, $style, $config);
                    $styleModel->getStyleXml($style)->save($stylePath);
                    echo "Exported       $stylePath\n";
                    $exportedStyleCount++;
                }
            }

            if ($exportedStyleCount < count($exportStyles)) {
                die("Not all export styles could be found...\n");
            }
        }

        // generate hashes
        self::generateHashesFile($addOn, $config, $list);

        // check for file_health_check event listener
        /** @var XenForo_Model_CodeEvent $codeEventModel */
        $codeEventModel = XenForo_Model::create('XenForo_Model_CodeEvent');
        $addOnEventListeners = $codeEventModel->getEventListenersByAddOn($addOn['addon_id']);
        $fileHealthCheckFound = false;
        foreach ($addOnEventListeners as $addOnEventListener) {
            if ($addOnEventListener['event_id'] === 'file_health_check') {
                $fileHealthCheckFound = true;
            }

            if (!is_callable(array($addOnEventListener['callback_class'], $addOnEventListener['callback_method']))) {
                die(sprintf("Callback is not callable %s::%s\n", $addOnEventListener['callback_class'],
                    $addOnEventListener['callback_method']));
            }
        }
        if (!$fileHealthCheckFound) {
            // try to generate the file health check event listener ourselves
            if (DevHelper_Generator_Code_Listener::generateFileHealthCheck($addOn, $config)) {
                $fileHealthCheckFound = true;
            }
        }
        if (!$fileHealthCheckFound) {
            die("No `file_health_check` event listener found.\n");
        }

        /** @var XenForo_Application $application */
        $application = XenForo_Application::getInstance();
        $rootPath = realpath($application->getRootDir());

        if (strpos($exportPath, 'upload') === false) {
            $exportPath .= '/upload';
        }
        XenForo_Helper_File::createDirectory($exportPath /*, true */);
        $exportPath = realpath($exportPath);
        $options = array(
            'extensions' => array(
                'php',
                'inc',
                'txt',
                'xml',
                'htm',
                'html',
                'js',
                'css',
                'jpg',
                'jpeg',
                'png',
                'gif',
                'swf',
                'crt',
                'pem',
                'eot',
                'svg',
                'ttf',
                'woff',
                'woff2',
                'otf',
                'md',
            ),
            'filenames_lowercase' => array(
                'license',
                'readme',
                'copyright',
                '.htaccess',
                'changelog',
                'composer.json',
                'readme.rdoc',
                'version',
            ),
            'force' => true, // always force add top level export entries
            'addon_id' => $addOn['addon_id'],

            'excludes' => array(),
            'excludeRegExs' => array(),
        );

        $excludes = $config->getExportExcludes();
        foreach ($excludes as $exclude) {
            if (preg_match('/^#.+#$/', $exclude)) {
                $options['excludeRegExs'][] = $exclude;
            } else {
                $options['excludes'][] = $exclude;
            }
        }

        foreach ($list as $type => $entry) {
            self::_fileExport($entry, $exportPath, $rootPath, $options);
        }

        // copy one xml copy to the export directory directory
        $xmlCopyPath = sprintf('%s/%s', dirname($exportPath), basename($xmlPath));
        if (@copy($xmlPath, $xmlCopyPath)) {
            echo "Copied         $xmlPath -> $xmlCopyPath\n";
        } else {
            echo "Can't cp       $xmlPath -> $xmlCopyPath\n";
        }
    }

    protected static function _fileExport($entry, &$exportPath, &$rootPath, $options)
    {
        if (empty($entry)) {
            return;
        }

        $relativePath = trim(str_replace($rootPath, '', $entry), '/');

        if (in_array($relativePath, $options['excludes'])) {
            echo "<span style='color: #ddd'>Excluded       $relativePath</span>\n";
            return;
        }

        foreach ($options['excludeRegExs'] as $excludeRegEx) {
            if (preg_match($excludeRegEx, $relativePath)) {
                echo "<span style='color: #ddd'>RegExcluded    $relativePath</span>\n";
                return;
            }
        }

        if (is_dir($entry)) {
            echo "<span style='color: #ddd'>Browsing       $relativePath</span>\n";

            $children = array();

            $dh = opendir($entry);
            while ($child = readdir($dh)) {
                if (substr($child, 0, 1) === '.') {
                    // ignore . (current directory)
                    // ignore .. (parent directory)
                    // ignore hidden files (dot files/directories)
                    continue;
                }

                $children[] = $child;
            }

            foreach ($children as $child) {
                if (!empty($options['force'])) {
                    $options['force'] = false;
                }
                // reset `force` option for children
                self::_fileExport($entry . '/' . $child, $exportPath, $rootPath, $options);
            }
        } elseif (is_file($entry)) {
            $ext = XenForo_Helper_File::getFileExtension($entry);
            if (!empty($options['force'])
                || (in_array($ext, $options['extensions'])
                    && strpos(basename($entry), '.') !== 0)
                || in_array(strtolower(basename($entry)), $options['filenames_lowercase'])
            ) {
                if ($options['addon_id'] == 'devHelper') {
                    $isDevHelper = (strpos($entry, 'DevHelper/DevHelper') !== false);
                } else {
                    $isDevHelper = (strpos($entry, 'DevHelper') !== false);
                }

                if (!$isDevHelper) {
                    $entryExportPath = $exportPath . '/' . $relativePath;

                    if ($ext === 'php') {
                        DevHelper_Helper_ShippableHelper::checkForUpdate($entry);
                    }

                    $contents = self::fileGetContents($entry);

                    if (!empty($contents)) {
                        if ($ext === 'php') {
                            DevHelper_Helper_Phrase::parsePhpForPhraseTracking($relativePath, $contents);
                            DevHelper_Helper_Xfcp::parsePhpForXfcpClass($relativePath, $contents);
                        }

                        $result = self::writeFile($entryExportPath, $contents, false, false);

                        if ($result === true) {
                            echo "Exporting      {$relativePath} OK\n";
                        } elseif ($result === 'skip') {
                            echo "<span style='color: #ddd'>Exporting      {$relativePath} SKIPPED</span>\n";
                        }
                    }
                }
            }
        }
    }

    public static function varExport($var, $level = 1, $linePrefix = "    ", $noKey = false)
    {
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

                if (!is_int($key)) {
                    $allKeysAreInt = false;
                }
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
                    $output .= "\n" . str_repeat($linePrefix, $level + 1);
                } else {
                    if (!$first) {
                        $output .= ', ';
                    }
                }

                if (empty($noKey)) {
                    $output .= var_export($key, true) . ' => ';
                }

                $output .= $str;

                if ($multiLine) {
                    $output .= ',';
                }

                $first = false;
            }

            if ($multiLine) {
                $output .= "\n" . str_repeat($linePrefix, $level);
            }

            $output .= ')';
        } elseif (is_object($var) && $var instanceof _DevHelper_Generator_File_Constant) {
            $output .= strval($var);
        } else {
            $tmp = var_export($var, true);
            if (strpos($tmp, "\n") !== false) {
                $tmp = str_replace("\n", "\n" . str_repeat($linePrefix, $level), $tmp);
            }

            $output .= $tmp;
        }

        return $output;
    }

    public static function varExportConstant($str)
    {
        return new _DevHelper_Generator_File_Constant($str);
    }

    public static function varExportConstantFromArray($value, array $constants)
    {
        foreach ($constants as $constant) {
            if ($value === constant($constant)) {
                return self::varExportConstant($constant);
            }
        }

        return $value;
    }

}

class _DevHelper_Generator_File_Constant
{
    protected $_str = '';

    function __construct($str)
    {
        $this->_str = $str;
    }

    function __toString()
    {
        return $this->_str;
    }

}