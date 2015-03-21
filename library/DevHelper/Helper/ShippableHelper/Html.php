<?php

/**
 * Class DevHelper_Helper_ShippableHelper_Html
 * @version 1
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
                    $snippet = utf8_substr($snippet, 0, $startPos);
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

        return $snippet;
    }
}