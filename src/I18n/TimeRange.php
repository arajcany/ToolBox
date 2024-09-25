<?php


namespace arajcany\ToolBox\I18n;


use Cake\I18n\DateTime;

/**
 * Class TimeRange
 * Class to help make a time range based on common expressions.
 * In other words, you could use this class to help make start and end dates
 * based on common expressions like 'today', 'tomorrow', 'last week' etc.
 *
 * Start and end dates are useful for constructing SQL statements.
 *
 * @package arajcany\ToolBox\I18n
 */
class TimeRange
{


    /**
     * Standardises the String into known keywords for the conversion process
     *
     * @param string $inputString
     * @return string
     */
    public function cleanupString($inputString = ''): string
    {
        //lower case
        $outputString = strtolower($inputString);

        //trim
        $outputString = trim($outputString);

        //word substitution
        $substitutionTables = [
            'last' => ['previous', 'past'],
            'this' => ['current', 'present'],
            'next' => ['forward', 'future'],
            'second' => ['seconds', 'secs'],
            'minute' => ['minutes', 'mins'],
            'hour' => ['hours'],
            'day' => ['days'],
            'month' => ['months'],
            'year' => ['years'],
            'week' => ['weeks'],
            'quarter' => ['quarters'],
            'jan' => ['january'],
            'feb' => ['february'],
            'mar' => ['march'],
            'apr' => ['april'],
            'may' => ['may'],
            'jun' => ['june'],
            'jul' => ['july'],
            'aug' => ['august'],
            'sep' => ['september', 'sept'],
            'oct' => ['october'],
            'nov' => ['november'],
            'dec' => ['december'],
        ];
        foreach ($substitutionTables as $cleanWord => $dirtyWords) {
            foreach ($dirtyWords as $dirtyWord) {
                $outputString = str_replace($dirtyWord, $cleanWord, $outputString);
            }
        }

        return $outputString;
    }

}