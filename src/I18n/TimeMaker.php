<?php

namespace arajcany\ToolBox\I18n;

use Cake\I18n\FrozenTime;
use Cake\I18n\Time;
use Exception;
use TypeError;

class TimeMaker
{

    /**
     * Wrapper function
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @return bool|FrozenTime|Time
     */
    public static function makeTimeFromUnknown($unknown, $inputTimezone = 'utc', $outputTimezone = 'utc')
    {
        return self::makeFromUnknown($unknown, $inputTimezone, $outputTimezone, $mode = 'mutable');
    }

    /**
     * Wrapper function
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @return bool|FrozenTime|Time
     */
    public static function makeFrozenTimeFromUnknown($unknown, $inputTimezone = 'utc', $outputTimezone = 'utc')
    {
        return self::makeFromUnknown($unknown, $inputTimezone, $outputTimezone, $mode = 'immutable');
    }

    /**
     * Make a Time/FrozenTime object from an unknown input type.
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @param bool|string $mode 'mutable' or 'immutable'
     * @param bool|null $failType Return this on failure to make a DateTime object
     * @return bool|null|FrozenTime|Time
     *
     * $unknown examples
     * String - "first day of january 2006"
     * Array - ['year' => '2018','month' => '7','day' => '25','hour' => '15','minute' => '6','second' => '30']
     *
     */
    private static function makeFromUnknown($unknown, $inputTimezone = 'utc', $outputTimezone = 'utc', $mode = false, $failType = null)
    {

        if ($mode == false) {
            return $failType;
        }

        if ($unknown === true) {
            return $failType;
        }

        if ($unknown === false) {
            return $failType;
        }

        if ($unknown === null) {
            return $failType;
        }


        //see if the basics will work
        try {

            if ($mode == 'mutable') {
                $timeObj = new Time($unknown, $inputTimezone);
                return $timeObj->setTimezone($outputTimezone);
            }

            if ($mode == 'immutable') {
                $timeObj = new FrozenTime($unknown, $inputTimezone);
                return $timeObj->setTimezone($outputTimezone);
            }

        } catch (TypeError $e) {

        } catch (Exception $e) {

        }

        //see if its a $this->request style array
        if (isset($unknown['year']) && isset($unknown['month']) && isset($unknown['day'])) {
            $defaults = [
                'hour' => '',
                'minute' => '',
                'second' => '',
            ];

            $unknownStructured = array_merge($defaults, $unknown);
            $unknownStructuredString =
                $unknownStructured['year'] . "-" .
                $unknownStructured['month'] . "-" .
                $unknownStructured['day'] . " " .
                $unknownStructured['hour'] . ":" .
                $unknownStructured['minute'] . ":" .
                $unknownStructured['second'];
            $unknownStructuredString = trim($unknownStructuredString, "-: \t\n\r");

            if ($unknownStructuredString == '') {
                return $failType;
            }

            try {

                if ($mode == 'mutable') {
                    $timeObj = new Time($unknownStructuredString, $inputTimezone);
                    return $timeObj->setTimezone($outputTimezone);
                }

                if ($mode == 'immutable') {
                    $timeObj = new FrozenTime($unknownStructuredString, $inputTimezone);
                    return $timeObj->setTimezone($outputTimezone);
                }

            } catch (TypeError $e) {

            } catch (Exception $e) {

            }
        }

        return $failType;
    }

}