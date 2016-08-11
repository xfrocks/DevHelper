<?php

/**
 * Class DevHelper_Helper_ShippableHelper_Http
 * @version 2
 */
class DevHelper_Helper_ShippableHelper_Http
{
    public static function resolveRedirect($url, $limit = -1, array $options = array())
    {
        $options = array_merge(array(
            'cacheTtl' => 3600,
        ), $options);

        $cache = null;
        $cacheKey = '';
        if ($options['cacheTtl'] > 0) {
            $cache = XenForo_Application::getCache();
        }
        if ($cache) {
            $cacheKey = self::_resolveRedirect_getCacheKey($url);
            $resolved = $cache->load($cacheKey, false, true);
            if (!empty($resolved)) {
                if (XenForo_Application::debugMode()) {
                    XenForo_Helper_File::log(__METHOD__, sprintf('Resolved via cache. ($url=%s, $limit=%d) -> %s',
                        $url, $limit, $resolved));
                }

                return unserialize($resolved);
            }
        }

        $resolved = self::_resolveRedirect_curl($url, $limit);
        if (XenForo_Application::debugMode()) {
            XenForo_Helper_File::log(__METHOD__, sprintf('Resolved via curl. ($url=%s, $limit=%d) -> %s',
                $url, $limit, serialize($resolved)));
        }

        if (!empty($cache)
            && !empty($cacheKey)
        ) {
            $cache->save(serialize($resolved), $cacheKey, array(), $options['cacheTtl']);
        }

        return $resolved;
    }

    protected static function _resolveRedirect_curl($url, $limit, array $options = array())
    {
        if (!isset($options['originalUrl'])) {
            $options['originalUrl'] = $url;
        }
        if (!isset($options['originalLimit'])) {
            $options['originalLimit'] = $limit;
        }

        if (!parse_url($url, PHP_URL_HOST)) {
            if (XenForo_Application::debugMode()) {
                XenForo_Helper_File::log(__METHOD__, sprintf('Cannot resolve malformed url. ($url=%s, $limit=%d) -> %s',
                    $options['originalUrl'], $options['originalLimit'], $url));
            }

            return $url;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $redirectLocation = '';
        if ($httpCode >= 300
            && $httpCode < 400
            && preg_match('#Location: (?<location>http.+)\s#', $headers, $matches)
        ) {
            $redirectLocation = $matches['location'];
        }

        if ($redirectLocation === '') {
            if (XenForo_Application::debugMode()) {
                XenForo_Helper_File::log(__METHOD__, sprintf('Resolved. ($url=%s, $limit=%d) -> %s',
                    $options['originalUrl'], $options['originalLimit'], $url));
            }

            return $url;
        }

        if ($limit === -1) {
            $nextLimit = -1;
        } elseif ($limit > 0) {
            $nextLimit = $limit - 1;
        } else {
            if (XenForo_Application::debugMode()) {
                XenForo_Helper_File::log(__METHOD__, sprintf('Too many redirects! ($url=%s, $limit=%d) -> %s',
                    $options['originalUrl'], $options['originalLimit'], $url));
            }

            return $url;
        }

        return self::_resolveRedirect_curl($redirectLocation, $nextLimit, $options);
    }

    protected static function _resolveRedirect_getCacheKey($url)
    {
        return preg_replace('#[^A-Za-z0-9_]#', '', sprintf('%s_%s', $url, md5(sprintf('%s_%s', $url, __CLASS__))));
    }
}