<?php

namespace Utility;

use arajcany\ToolBox\Utility\TextGrouper;
use PHPUnit\Framework\TestCase;

class TextGrouperTest extends TestCase
{

    public function testBySimilarityUnrelated()
    {
        $input = [
            'a' => 'there',
            'b' => 'is',
            'c' => 'no',
            'd' => 'logic',
            'e' => 'to',
            'f' => 'match',
            'g' => 'these',
            'h' => 'list',
            'i' => 'items',
        ];

        $expected = [
            ['a' => 'there'],
            ['b' => 'is'],
            ['c' => 'no'],
            ['d' => 'logic'],
            ['e' => 'to'],
            ['f' => 'match'],
            ['g' => 'these'],
            ['h' => 'list'],
            ['i' => 'items'],
        ];
        $actual = TextGrouper::bySimilarity($input, true, true);
        $this->assertEquals($expected, $actual);
    }


    public function testBySimilaritySimpleSequential()
    {
        $input = [
            'a' => 'file_9_bar_02.png',
            'b' => 'file_0_a_002.png',
            'c' => 'file_9_bar_12.png',
            'd' => 'file_0_a_001.png',
            'e' => 'file_9_bar_04.png',
            'f' => 'unrelated_file_001.png',
            'g' => 'file_0_a_003.png',
            'h' => 'file_0_a_004.png',
            'i' => 'file_9_bar_05.png',
        ];

        $expected = [
            ['a' => 'file_9_bar_02.png',
                'c' => 'file_9_bar_12.png',
                'e' => 'file_9_bar_04.png',
                'i' => 'file_9_bar_05.png'],
            ['b' => 'file_0_a_002.png',
                'd' => 'file_0_a_001.png',
                'g' => 'file_0_a_003.png',
                'h' => 'file_0_a_004.png'],
            ['f' => 'unrelated_file_001.png'],
        ];
        $actual = TextGrouper::bySimilarity($input, true, true);
        $this->assertEquals($expected, $actual);

        $expected = [
            ['a' => 'file_9_bar_02.png',
                'c' => 'file_9_bar_12.png',
                'e' => 'file_9_bar_04.png',
                'i' => 'file_9_bar_05.png'],
            ['b' => 'file_0_a_002.png'],
            ['d' => 'file_0_a_001.png'],
            ['f' => 'unrelated_file_001.png'],
            ['g' => 'file_0_a_003.png'],
            ['h' => 'file_0_a_004.png'],
        ];
        $actual = TextGrouper::bySimilarity($input, true, false);
        $this->assertEquals($expected, $actual);
    }


    public function testBySimilarityFalsePositive()
    {
        $input = [
            0 => 'a1',
            1 => 'a1',
            2 => 'a2',
            3 => 'a3',
            4 => 'b1',
            5 => 'b2',
            6 => 'b3',
        ];

        $expected = [
            [0 => 'a1',
                1 => 'a1'],
            [2 => 'a2'],
            [3 => 'a3'],
            [4 => 'b1'],
            [5 => 'b2'],
            [6 => 'b3'],
        ];
        $actual = TextGrouper::bySimilarity($input, true, true);
        $this->assertEquals($expected, $actual);

    }
}
