<?php

/**
 * Class DevHelper_Helper_ShippableHelper_Html
 * @version 4
 */
class DevHelper_Helper_ShippableHelper_Html
{
    /**
     * @param string $string
     * @param int $maxLength
     * @param array $options
     *
     * @return string
     */
    public static function snippet($string, $maxLength = 0, array $options = array())
    {
        if ($maxLength == 0 || $maxLength > utf8_strlen($string)) {
            return $string;
        }

        $snippet = XenForo_Template_Helper_Core::callHelper('snippet', array($string, $maxLength, $options));

        // TODO: find better way to avoid having to call this
        $snippet = htmlspecialchars_decode($snippet);

        $offset = 0;
        $stack = array();
        while (true) {
            $startPos = utf8_strpos($snippet, '<', $offset);
            if ($startPos !== false) {
                $endPos = utf8_strpos($snippet, '>', $startPos);
                if ($endPos === false) {
                    // we found a partial open tag, best to delete the whole thing
                    $snippet = utf8_substr($snippet, 0, $startPos) . '…';
                    break;
                }

                $foundLength = $endPos - $startPos - 1;
                $found = utf8_substr($snippet, $startPos + 1, $foundLength);
                $offset = $endPos;

                if (preg_match('#^(?<closing>/?)(?<tag>\w+)#', $found, $matches)) {
                    $tag = $matches['tag'];
                    $isClosing = !empty($matches['closing']);
                    $isSelfClosing = (!$isClosing && (utf8_substr($found, $foundLength - 1, 1) === '/'));

                    if ($isClosing) {
                        $lastInStack = null;
                        if (count($stack) > 0) {
                            $lastInStack = array_pop($stack);
                        }

                        if ($lastInStack !== $tag) {
                            // found tag does not match the one in stack
                            $replacement = '';

                            // first we have to close the one in stack
                            if ($lastInStack !== null) {
                                $replacement .= sprintf('</%s>', $tag);
                            }

                            // then we have to self close the found tag
                            $replacement .= utf8_substr($snippet, $startPos, $endPos - $startPos - 1);
                            $replacement .= '/>';

                            // do the replacement
                            $snippet = utf8_substr_replace($snippet, $replacement, $startPos, $endPos - $startPos);
                            $offset = $startPos + utf8_strlen($snippet);
                        }
                    } elseif ($isSelfClosing) {
                        // do nothing
                    } else {
                        // is opening tag
                        $stack[] = $tag;
                    }
                }
            } else {
                break;
            }
        }

        while (!empty($stack)) {
            $snippet .= sprintf('</%s>', array_pop($stack));
        }

        $snippet = utf8_trim($snippet);
        if ($snippet === '') {
            // this is bad...
            // happens if the $maxLength is too low and for some reason the very first tag cannot finish
            $snippet = utf8_trim(strip_tags($string));
            if ($snippet !== '') {
                $snippet = XenForo_Template_Helper_Core::callHelper('snippet', array($snippet, $maxLength, $options));
            } else {
                // this is super bad...
                // the string is one big html tag and it is too damn long
                $snippet = '…';
            }
        }

        return $snippet;
    }

    public static function stripFont($html)
    {
        $html = preg_replace('#(<[^>]+)( style="[^"]+")([^>]*>)#', '$1$3', $html);
        $html = preg_replace('#<\/?(b|i)>#', '', $html);

        return $html;
    }

    public static function getMetaTags($html)
    {
        $tags = array();

        $headPos = strpos($html, '</head>');
        if ($headPos === false) {
            return $tags;
        }

        $head = substr($html, 0, $headPos);

        $offset = 0;
        while (true) {
            if (preg_match('#<meta[^>]+>#i', $head, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $tag = $matches[0][0];
                $offset = $matches[0][1] + strlen($tag);
                $name = null;
                $value = null;

                if (preg_match('#name="(?<name>[^"]+)"#i', $tag, $matches)) {
                    $name = $matches['name'];
                } elseif (preg_match('#property="(?<name>[^"]+)"#i', $tag, $matches)) {
                    $name = $matches['name'];
                } else {
                    continue;
                }

                if (preg_match('#content="(?<value>[^"]+)"#', $tag, $matches)) {
                    $value = self::entityDecode($matches['value']);
                } else {
                    continue;
                }

                $tags[] = array(
                    'name' => $name,
                    'value' => $value,
                );
            } else {
                break;
            }
        }

        return $tags;
    }

    public static function getTitleTag($html)
    {
        if (preg_match('#<title>(?<title>[^<]+)</title>#i', $html, $matches)) {
            return self::entityDecode($matches['title']);
        }

        return '';
    }

    public static function entityDecode($html)
    {
        $decoded = $html;

        // required to deal with &quot; etc.
        $decoded = html_entity_decode($decoded, ENT_COMPAT, 'UTF-8');

        // required to deal with &#1234; etc.
        $convmap = array(0x0, 0x2FFFF, 0, 0xFFFF);
        $decoded = mb_decode_numericentity($decoded, $convmap, 'UTF-8');

        return $decoded;
    }
}