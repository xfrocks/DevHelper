<?php

class DevHelper_XenForo_ControllerAdmin_Tools extends XFCP_DevHelper_XenForo_ControllerAdmin_Tools
{
    public function actionDevHelperSync()
    {
        if (DevHelper_Installer::checkAddOnVersion()) {
            return $this->responseNoPermission();
        }

        /** @var XenForo_Model_AddOn $addOnModel */
        $addOnModel = $this->getModelFromCache('XenForo_Model_AddOn');

        $addOn = $addOnModel->getAddOnById('devHelper');
        $xmlPath = DevHelper_Generator_File::getAddOnXmlPath($addOn);

        $addOnModel->installAddOnXmlFromFile($xmlPath, $addOn['addon_id']);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('index')
        );
    }

    public function actionAddOnsServerFile()
    {
        $q = $this->_input->filterSingle('q', XenForo_Input::STRING);
        $paths = array();

        /** @var XenForo_Application $app */
        $app = XenForo_Application::getInstance();
        $rootDir = $app->getRootDir();

        if (strlen($q) > 0
            && strpos($q, '.') === false
        ) {
            if (strlen($q) < 7) {
                $q = 'library/';
                $paths[] = 'library';

                if (isset($_SERVER['DEVHELPER_ROUTER_PHP'])) {
                    $routerPhp = $_SERVER['DEVHELPER_ROUTER_PHP'];
                    $routerPhpDir = dirname($routerPhp);
                    $paths[] = sprintf('%s/addons', $routerPhpDir);
                }
            }

            $parts = preg_split('#/#', $q, -1, PREG_SPLIT_NO_EMPTY);

            $prefix = '';
            if (count($parts) > 0) {
                $prefix = array_pop($parts);
            }

            if (substr($q, 0, 1) === '/') {
                $path = '/' . implode('/', $parts);
            } else {
                $path = rtrim(sprintf('%s/%s', $rootDir, implode('/', $parts)), '/');
            }

            if (is_dir($path)) {
                $contents = scandir($path);

                foreach ($contents as $content) {
                    if (substr($content, 0, 1) === '.') {
                        continue;
                    }

                    if ($prefix !== ''
                        && substr($content, 0, strlen($prefix)) !== $prefix
                    ) {
                        continue;
                    }

                    $contentPath = sprintf('%s/%s', $path, $content);
                    if (is_dir($contentPath)) {
                        $paths[] = $contentPath;
                    } else {
                        $ext = XenForo_Helper_File::getFileExtension($contentPath);
                        if ($ext === 'xml') {
                            array_unshift($paths, $contentPath);
                        }
                    }
                }
            }
        }

        $results = array();
        foreach ($paths as $path) {
            $relativePath = preg_replace('#^' . preg_quote($app->getRootDir()) . '/#', '', $path);
            $results[$relativePath] = basename($path);
        }

        $view = $this->responseView();
        $view->jsonParams = array(
            'results' => $results
        );
        return $view;
    }

    public function actionCodeEventListenersHint()
    {
        $q = $this->_input->filterSingle('q', XenForo_Input::STRING);
        $classes = array();

        /** @var XenForo_Application $app */
        $app = XenForo_Application::getInstance();
        $libraryPath = sprintf('%s/library/', $app->getRootDir());

        if (strlen($q) > 0
            && preg_match('/[A-Z]/', $q)
        ) {
            $dirPath = '';
            $pattern = '';

            $classPath = DevHelper_Generator_File::getClassPath($q);
            if (is_file($classPath)) {
                $classes[] = $q;
            }

            $_dirPath = preg_replace('/\.php$/', '', $classPath);
            if (is_dir($_dirPath)) {
                $dirPath = $_dirPath;
            } else {
                $_parentDirPath = dirname($_dirPath);
                if (is_dir($_parentDirPath)) {
                    $dirPath = $_parentDirPath;
                    $pattern = basename($_dirPath);
                }
            }

            if ($dirPath !== '') {
                $files = scandir($dirPath);

                foreach ($files as $file) {
                    if (substr($file, 0, 1) === '.') {
                        continue;
                    }

                    if ($pattern !== ''
                        && strpos($file, $pattern) !== 0
                    ) {
                        continue;
                    }

                    $filePath = sprintf('%s/%s', $dirPath, $file);

                    if (is_file($filePath)) {
                        $contents = file_get_contents($filePath);
                        if (preg_match('/class\s(?<class>.+?)(\sextends|{)/', $contents, $matches)) {
                            $classes[] = $matches['class'];
                        }
                    } elseif (is_dir($filePath)) {
                        $classes[] = str_replace('/', '_', str_replace($libraryPath, '', $filePath));
                    }
                }
            }
        }

        $results = array();
        foreach ($classes as $class) {
            $results[$class] = $class;
        }

        $view = $this->responseView();
        $view->jsonParams = array(
            'results' => $results
        );
        return $view;
    }
}
