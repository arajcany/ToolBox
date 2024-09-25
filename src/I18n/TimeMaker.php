<?php

namespace arajcany\ToolBox\I18n;

use Cake\I18n\DateTime;
use Throwable;

class TimeMaker
{

    /**
     * Wrapper function
     *
     * @param $unknown
     * @param string $inputTimezone
     * @param string $outputTimezone
     * @return DateTime|bool|null
     */
    public static function makeFrozenTimeFromUnknown($unknown, string $inputTimezone = 'utc', string $outputTimezone = 'utc'): DateTime|bool|null
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
     * @return DateTime|bool|null
     */
    private static function makeFromUnknown($unknown, string $inputTimezone = 'utc', string $outputTimezone = 'utc', bool $failType = false): DateTime|bool|null
    {
        if (in_array($unknown, [true, false, null], true)) {
            return $failType;
        }

        //see if the basics will work
        try {
            $timeObj = new DateTime($unknown, $inputTimezone);
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
                $timeObj = new DateTime($unknownStructuredString, $inputTimezone);
                return $timeObj->setTimezone($outputTimezone);
            } catch (Throwable $exception) {

            }
        }

        return $failType;
    }

}