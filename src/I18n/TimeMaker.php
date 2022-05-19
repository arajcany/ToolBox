<?php

namespace arajcany\ToolBox\I18n;

use Cake\I18n\FrozenTime;
use Throwable;

class TimeMaker
{

    /**
     * Wrapper function
     *
     * @deprecated use makeFrozenTimeFromUnknown() as mutable is no longer supported
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @return bool|FrozenTime
     */
    public static function makeTimeFromUnknown($unknown, string $inputTimezone = 'utc', string $outputTimezone = 'utc')
    {
        return self::makeFromUnknown($unknown, $inputTimezone, $outputTimezone);
    }

    /**
     * Wrapper function
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @return bool|FrozenTime
     */
    public static function makeFrozenTimeFromUnknown($unknown, string $inputTimezone = 'utc', string $outputTimezone = 'utc')
    {
        return self::makeFromUnknown($unknown, $inputTimezone, $outputTimezone);
    }

    /**
     * Make a Time/FrozenTime object from an unknown input type.
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @param bool|null $failType Return this on failure to make a DateTime object
     * @return bool|null|FrozenTime
     *
     * $unknown examples
     * String - "first day of january 2006"
     * Array - ['year' => '2018','month' => '7','day' => '25','hour' => '15','minute' => '6','second' => '30']
     */
    private static function makeFromUnknown($unknown, string $inputTimezone = 'utc', string $outputTimezone = 'utc', bool $failType = false)
    {
        if (in_array($unknown, [true, false, null], true)) {
            return $failType;
        }

        //see if the basics will work
        try {
            $timeObj = new FrozenTime($unknown, $inputTimezone);
            return $timeObj->setTimezone($outputTimezone);
        } catch (Throwable $exception) {

        }

        //see if it's a $this->request style array
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
                $timeObj = new FrozenTime($unknownStructuredString, $inputTimezone);
                return $timeObj->setTimezone($outputTimezone);
            } catch (Throwable $exception) {

            }
        }

        return $failType;
    }

}