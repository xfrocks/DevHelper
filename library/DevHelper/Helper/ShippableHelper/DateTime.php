<?php

/**
 * Class DevHelper_Helper_ShippableHelper_DateTime
 * @version 1
 */
class DevHelper_Helper_ShippableHelper_DateTime
{
    public static function tzOffsetToName($offset, $isDst = null)
    {
        if ($isDst === null) {
            $isDst = date('I');
        }

        $zone = timezone_name_from_abbr('', $offset, $isDst);

        if ($zone === false) {
            foreach (timezone_abbreviations_list() as $abbr) {
                foreach ($abbr as $city) {
                    if ((bool)$city['dst'] === (bool)$isDst &&
                        strlen($city['timezone_id']) > 0 &&
                        $city['offset'] == $offset
                    ) {
                        $zone = $city['timezone_id'];
                        break;
                    }
                }

                if ($zone !== false) {
                    break;
                }
            }
        }

        return $zone;
    }
}