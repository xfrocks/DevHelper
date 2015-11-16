<?php

/**
 * Class DevHelper_Helper_ShippableHelper_DateTime
 * @version 2
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

    /**
     * Get Unix timestamp in GMT for inputted date values.
     *
     * @param int|array $year in user timezone
     * @param int $month in user timezone
     * @param int $day in user timezone
     * @param int $hour in user timezone
     * @param int $minute in user timezone
     * @param int $second in user timezone
     *
     * @return int timestamp in GMT
     */
    public static function gmmktime($year, $month = 0, $day = 0, $hour = 0, $minute = 0, $second = 0)
    {
        if (is_array($year)) {
            $args = func_get_args();
            if (count($args) !== 1) {
                return 0;
            }

            $values = $year;
            $year = 0;
            foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $key) {
                if (isset($values[$key])
                    && is_int($values[$key])
                ) {
                    $$key = intval($values[$key]);
                }
            }
        }

        $timestamp = gmmktime($hour, $minute, $second, $month, $day, $year);
        $offset = XenForo_Locale::getTimeZoneOffset();

        return $timestamp - $offset;
    }
}