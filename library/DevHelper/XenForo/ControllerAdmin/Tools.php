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
        $libraryPath = sprintf('%s/library', $app->getRootDir());

        $library = 'library';
        $libraryLength = min(strlen($library), strlen($q));

        if (strlen($q) > 0
            && strpos($q, '.') === false
            && substr($q, 0, $libraryLength) === substr($library, 0, $libraryLength)
        ) {
            if (strlen($q) < 7) {
                $q = 'library/';
                $paths[] = 'library';
            }

            $parts = explode('/', $q);
            array_shift($parts);

            $prefix = '';
            if (count($parts) > 0) {
                $prefix = array_pop($parts);
            }

            $path = rtrim(sprintf('%s/%s', $libraryPath, implode('/', $parts)), '/');

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
}
