<?php

namespace I18n;

use PHPUnit\Framework\TestCase;
use arajcany\ToolBox\I18n\TimeMaker;
use \Cake\I18n\Time;
use \Cake\I18n\FrozenTime;

/**
 * Class TimeMakerTest
 * @package phpUnitTutorial\Test
 *
 */
class TimeMakerTest extends TestCase
{
    public $now;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->now = date("Y-m-d H:i:s");
    }

    /**
     * Test that Sting input matches Array input
     */
    public function testStringMatchesArray()
    {
        $stringInput = TimeMaker::makeFrozenTimeFromUnknown(
            '2018-04-03 14:02:55'
        );

        $arrayInput = TimeMaker::makeFrozenTimeFromUnknown(
            ['year' => '2018', 'month' => '4', 'day' => '3', 'hour' => '14', 'minute' => '2', 'second' => '55']
        );

        $this->assertEquals($stringInput, $arrayInput);
    }

    /**
     * Test that an existing DT object is not incorrectly modified by $inputTimezone
     */
    public function testExistingDateTimeIsNotModified()
    {
        $cakeFrozenTime = new FrozenTime($this->now, "UTC");
        $timeMakerFrozenTime = TimeMaker::makeFrozenTimeFromUnknown($cakeFrozenTime, "Australia/Sydney");

        $this->assertEquals($cakeFrozenTime, $timeMakerFrozenTime);
    }

    /**
     * Test that TZ gets converted to default 'UTC'
     */
    public function testInputTimezoneToUtc()
    {
        $timeMakerFrozenTime = TimeMaker::makeFrozenTimeFromUnknown($this->now, "Australia/Sydney");

        $this->assertEquals($timeMakerFrozenTime->timezoneName, "UTC");
    }

    /**
     * Test that TZ gets converted to specified "Australia/Sydney"
     */
    public function testInputTimezoneAndOutputTimezone()
    {
        $timeMakerFrozenTime = TimeMaker::makeFrozenTimeFromUnknown($this->now, "Australia/Sydney", "Australia/Sydney");

        $this->assertEquals($timeMakerFrozenTime->timezoneName, "Australia/Sydney");
    }

}