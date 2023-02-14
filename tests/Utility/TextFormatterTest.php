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
}
