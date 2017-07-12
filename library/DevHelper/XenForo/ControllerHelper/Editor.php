<?php

class DevHelper_XenForo_ControllerHelper_Editor extends XFCP_DevHelper_XenForo_ControllerHelper_Editor
{
    public function getMessageText($inputName, XenForo_Input $input, $htmlCharacterLimit = -1)
    {
        $messageText = parent::getMessageText($inputName, $input, $htmlCharacterLimit);

        if (utf8_trim($messageText) === 'lipsum') {
            $lipsum = @file_get_contents('http://www.lipsum.com/feed/json');
            if (!empty($lipsum)) {
                $lipsum = @json_decode($lipsum, true);
                if (!empty($lipsum['feed']['lipsum'])) {
                    $messageText = sprintf(
                        "%s\n\nBy [URL=%s]%s[/URL]",
                        $lipsum['feed']['lipsum'],
                        $lipsum['feed']['creditlink'],
                        $lipsum['feed']['creditname']
                    );
                }
            }
        }

        return $messageText;
    }
}
