<?php

namespace Utility;

use arajcany\ToolBox\Utility\TextFormatter;
use PHPUnit\Framework\TestCase;

class TextFormatterTest extends TestCase
{

    public function testMakeDirectoryTrailingSmartSlash()
    {
        $string = '\\here\\is\\a\\backward\\slash\\dir';
        $expected = '\\here\\is\\a\\backward\\slash\\dir\\';
        $actual = TextFormatter::makeDirectoryTrailingSmartSlash($string);
        $this->assertEquals($expected, $actual);

        $string = '\\here\\is\\a\\backward/slash\\dir';
        $expected = '\\here\\is\\a\\backward/slash\\dir\\';
        $actual = TextFormatter::makeDirectoryTrailingSmartSlash($string);
        $this->assertEquals($expected, $actual);

        $string = '/here/is/a/forward/slash/dir';
        $expected = '/here/is/a/forward/slash/dir/';
        $actual = TextFormatter::makeDirectoryTrailingSmartSlash($string);
        $this->assertEquals($expected, $actual);

        $string = '/here/is/a/forward\\slash/dir';
        $expected = '/here/is/a/forward\\slash/dir/';
        $actual = TextFormatter::makeDirectoryTrailingSmartSlash($string);
        $this->assertEquals($expected, $actual);
    }

    public function testUnmakeEndsWith()
    {
        $string = 'path/string/with/repeating/path/repeating/path/path';
        $expected = 'path/string/with/repeating/path/repeating/path';
        $actual = TextFormatter::unmakeEndsWith($string, "/path");
        $this->assertEquals($expected, $actual);
    }

    public function testUnmakeStartsWith()
    {
        $string = 'path/path/string/with/repeating/path/repeating/path/path/path';
        $expected = 'path/string/with/repeating/path/repeating/path/path/path';
        $actual = TextFormatter::unmakeStartsWith($string, "path/");
        $this->assertEquals($expected, $actual);
    }

    public function testRemoveRepeatingDelimiters()
    {
        $delimList = [',', '-', '_', '|', ' '];
        $string = "remove  repeating|| delimiters-from--this___really,,long,,  string";
        $expected = "remove repeating| delimiters-from-this_really,long, string";
        $actual = TextFormatter::removeRepeatingDelimiters($string, $delimList);
        $this->assertEquals($expected, $actual);
    }


    public function testMakeDirectoryTrailingForwardSlash()
    {
        $string = 'some/path/goes/here';
        $expected = 'some/path/goes/here/';
        $actual = TextFormatter::makeDirectoryTrailingForwardSlash($string);
        $this->assertEquals($expected, $actual);

        $string = 'some/path/goes/here/';
        $expected = 'some/path/goes/here/';
        $actual = TextFormatter::makeDirectoryTrailingForwardSlash($string);
        $this->assertEquals($expected, $actual);
    }

    public function testMakeDirectoryTrailingBackwardSlash()
    {
        $string = 'some\\path\\goes\\here';
        $expected = 'some\\path\\goes\\here\\';
        $actual = TextFormatter::makeDirectoryTrailingBackwardSlash($string);
        $this->assertEquals($expected, $actual);

        $string = 'some\\path\\goes\\here\\';
        $expected = 'some\\path\\goes\\here\\';
        $actual = TextFormatter::makeDirectoryTrailingBackwardSlash($string);
        $this->assertEquals($expected, $actual);
    }

    public function testMakeStartsWithAndEndsWith()
    {
        $string = 'abcdefg';
        $expected = 'abcdefg';
        $actual = TextFormatter::makeStartsWithAndEndsWith($string, 'a', 'g');
        $this->assertEquals($expected, $actual);

        $string = 'bcdef';
        $expected = 'abcdefg';
        $actual = TextFormatter::makeStartsWithAndEndsWith($string, 'a', 'g');
        $this->assertEquals($expected, $actual);
    }

    public function testMakeStartsWith()
    {
        $string = 'abcdefg';
        $expected = 'abcdefg';
        $actual = TextFormatter::makeStartsWith($string, 'a');
        $this->assertEquals($expected, $actual);

        $string = 'bcdefg';
        $expected = 'abcdefg';
        $actual = TextFormatter::makeStartsWith($string, 'a');
        $this->assertEquals($expected, $actual);
    }

    public function testMakeEndsWith()
    {
        $string = 'abcdefg';
        $expected = 'abcdefg';
        $actual = TextFormatter::makeEndsWith($string, 'g');
        $this->assertEquals($expected, $actual);

        $string = 'abcdef';
        $expected = 'abcdefg';
        $actual = TextFormatter::makeEndsWith($string, 'g');
        $this->assertEquals($expected, $actual);
    }

    public function testStartsWith()
    {
        $string = 'abcdefg';
        $expected = true;
        $actual = TextFormatter::startsWith($string, 'a');
        $this->assertEquals($expected, $actual);

        $string = 'abcdefg';
        $expected = false;
        $actual = TextFormatter::startsWith($string, 'z');
        $this->assertEquals($expected, $actual);
    }

    public function testEndsWith()
    {
        $string = 'abcdefg';
        $expected = true;
        $actual = TextFormatter::endsWith($string, 'g');
        $this->assertEquals($expected, $actual);

        $string = 'abcdefg';
        $expected = false;
        $actual = TextFormatter::endsWith($string, 'z');
        $this->assertEquals($expected, $actual);
    }

    public function testNormaliseSlashes()
    {
        //first mode
        $tests = [
            '/some/dir\\with/slashes/' => '/some/dir/with/slashes/',
            '/some\\dir\\with\\slashes\\' => '/some/dir/with/slashes/',
            'c:\\some\\dir\\with/slashes\\' => 'c:\\some\\dir\\with\\slashes\\',
            '\\some\\dir/with/slashes\\' => '\\some\\dir\\with\\slashes\\',
        ];
        foreach ($tests as $input => $expected) {
            $actual = TextFormatter::normaliseSlashes($input, 'first');
            $this->assertEquals($expected, $actual);
        }

        //count mode
        $tests = [
            '/some/dir\\with/slashes/' => '/some/dir/with/slashes/',
            '/some\\dir\\with\\slashes\\' => '\\some\\dir\\with\\slashes\\',
            'c:\\some\\dir\\with/slashes\\' => 'c:\\some\\dir\\with\\slashes\\',
            '\\some/dir/with/slashes\\equal\\' => '\\some\\dir\\with\\slashes\\equal\\',
        ];
        foreach ($tests as $input => $expected) {
            $actual = TextFormatter::normaliseSlashes($input, 'count');
            $this->assertEquals($expected, $actual);
        }

    }

    public function testStripBetweenTags()
    {
        $startTag = '<--start';
        $endTag = 'end-->';

        //basic strip
        $string = "Hello<--start all of this will be removed end-->World";
        $expect = "Hello                                           World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //basic strip twice
        $string = "Hello<--start removed end-->World  Hello<--start removed end-->World";
        $expect = "Hello                       World  Hello                       World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //nested tags will work
        $string = "Hello<--start <--start <--start removed end--> end--> end-->World";
        $expect = "Hello                                                       World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //balanced nested tags will work
        $string = "Hello<--start <--start removed end-->  <--start removed end--> end-->World";
        $expect = "Hello                                                                World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //balanced nested tags will work
        $string = "Hello<--start <--start removed <--start  end--> removed end--> end-->World";
        $expect = "Hello                                                                World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //out of order tags so no stripping
        $string = "Hello end--> end--> end--> not removed <--start <--start <--start World";
        $expect = "Hello end--> end--> end--> not removed <--start <--start <--start World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //some out of order tags so some stripping will happen
        $string = "Hello end--> end--> end--> not removed <--start removed end--> <--start <--start <--start World";
        $expect = "Hello end--> end--> end--> not removed                         <--start <--start <--start World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //unbalanced start tags so no stripping
        $string = "Hello<--start all of this will not be removed end-- World";
        $expect = "Hello<--start all of this will not be removed end-- World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //unbalanced end tags so no stripping
        $string = "Hello --start all of this will not be removed end-->World";
        $expect = "Hello --start all of this will not be removed end-->World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //basic strip with line breaks preserved
        $string = "Hello\r\n<--start all of this \r\nwill be removed end-->\r\nWorld";
        $expect = "Hello\r\n+++++++++++++++++++++\r\n++++++++++++++++++++++\r\nWorld";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag, '+');
        $this->assertEquals($expect, $actual);

        //basic strip with line breaks removed
        $string = "Hello\r\n<--start all of this \r\nwill be removed end-->\r\nWorld";
        $expect = "Hello\r\n+++++++++++++++++++++++++++++++++++++++++++++\r\nWorld";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag, '+', false);
        $this->assertEquals($expect, $actual);

        //basic strip with defined replacement char
        $string = "Hello<--start all of this will be removed end-->World";
        $expect = "Hello+++++++++++++++++++++++++++++++++++++++++++World";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag, '+');
        $this->assertEquals($expect, $actual);

        //basic strip with no replacement char
        $string = "Hello<--start all of this will be removed end-->World";
        $expect = "HelloWorld";
        $actual = TextFormatter::stripBetweenTags($string, $startTag, $endTag, '');
        $this->assertEquals($expect, $actual);
    }

    public function testFindBetweenTags()
    {
        $startTag = '<--start';
        $endTag = 'end-->';

        //simple tags
        $string = "Hello<--start{Found_A}end--><--start{Found_B}end--><--start{Found_C}end-->World";
        $expect = ['{Found_A}', '{Found_B}', '{Found_C}'];
        $actual = TextFormatter::findBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);

        //nested tags
        $string = "Hello<--start{Found_A<--start{Found_B<--start{Found_C}end-->}end-->}end-->World";
        $expect = ['{Found_A<--start{Found_B<--start{Found_C}end-->}end-->}', '{Found_B<--start{Found_C}end-->}', '{Found_C}'];
        $actual = TextFormatter::findBetweenTags($string, $startTag, $endTag);
        $this->assertEquals($expect, $actual);
    }
}
