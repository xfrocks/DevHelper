<?php

/** @var XenForo_Application $application */
$application = XenForo_Application::getInstance();
$originalContents = file_get_contents($application->getRootDir() . '/library/XenForo/ViewRenderer/Json.php');
$contents = substr($originalContents, strpos($originalContents, '<?php') + 5);
$contents = str_replace(
    'class XenForo_ViewRenderer_Json extends',
    'class _XenForo_ViewRenderer_Json extends',
    $contents
);
$contents = str_replace('self::', 'static::', $contents);
eval($contents);

class DevHelper_XenForo_ViewRenderer_Json extends _XenForo_ViewRenderer_Json
{
    protected static function _addDefaultParams(array &$params = array())
    {
        $params = parent::_addDefaultParams($params);

        if (XenForo_Application::isRegistered('page_start_time')) {
            $params['_pageTime'] = microtime(true) - XenForo_Application::get('page_start_time');
        }

        $params['_memoryUsage'] = memory_get_usage();
        $params['_memoryUsagePeak'] = memory_get_peak_usage();

        if (XenForo_Application::isRegistered('db')) {
            $db = XenForo_Application::getDb();

            /* @var $profiler Zend_Db_Profiler */
            $profiler = $db->getProfiler();
            $params['_queryCount'] = $profiler->getTotalNumQueries();
            $params['_totalQueryRunTime'] = 0;

            if ($params['_queryCount']) {
                $params['_queries'] = array();

                $queries = $profiler->getQueryProfiles();

                /** @var Zend_Db_Profiler_Query $query */
                foreach ($queries AS $query) {
                    $queryText = $query->getQuery();
                    $queryText = preg_replace('#\s+#', ' ', $queryText);
                    $queryText = trim($queryText);

                    foreach ($query->getQueryParams() AS $param) {
                        $param = sprintf('{%s}', htmlentities($param));
                        $pos = strpos($queryText, '?');
                        if ($pos !== false) {
                            $queryText = substr_replace($queryText, $param, $pos, 1);
                        }
                    }

                    $params['_queries'][] = htmlentities($queryText);
                    $params['_totalQueryRunTime'] += $query->getElapsedSecs();
                }
            }
        }

        return $params;
    }
}

eval('class XenForo_ViewRenderer_Json extends DevHelper_XenForo_ViewRenderer_Json {}');
