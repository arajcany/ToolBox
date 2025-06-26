<?php

namespace Utility;

use arajcany\ToolBox\Utility\TextExtractor;
use PHPUnit\Framework\TestCase;

class TextExtractorTest extends TestCase
{

    public function testExtractWithClearNotations()
    {
        $text = "/tmp/to_print/order-3428/job-435892/file.pdf";
        $expected = ['order_id' => 3428, 'job_id' => 435892];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractWithShortNotations()
    {
        $text = "/tmp/order-3428-job-435892.pdf";
        $expected = ['order_id' => 3428, 'job_id' => 435892];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractWithAbbreviatedNotations()
    {
        $text = "/tmp/O3428-J435892.pdf";
        $expected = ['order_id' => 3428, 'job_id' => 435892];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractWithoutNotationsMultipleNumbers()
    {
        $text = "/tmp/445345/9843987034/746568734/file.pdf";
        $expected = ['order_id' => 9843987034, 'job_id' => 746568734];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractWithoutNotationsWithDashes()
    {
        $text = "/tmp/445345-9843987034-746568734.pdf";
        $expected = ['order_id' => 9843987034, 'job_id' => 746568734];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractMixedWithNotationsAndExtraNumbers()
    {
        $text = "/tmp/order-445345/job-9843987034/746568734/file.pdf";
        $expected = ['order_id' => 445345, 'job_id' => 9843987034];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractSingleNumberWithPreferenceOrder()
    {
        $text = "/tmp/only_one_id_678910.pdf";
        $expected = ['order_id' => 678910, 'job_id' => null];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text, ['preference' => 'order']));
    }

    public function testExtractSingleNumberWithPreferenceJob()
    {
        $text = "/tmp/only_one_id_678910.pdf";
        $expected = ['order_id' => null, 'job_id' => 678910];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text, ['preference' => 'job']));
    }

    public function testExtractNoValidIds()
    {
        $text = "/tmp/some_folder/no_ids_here/file.txt";
        $expected = ['order_id' => null, 'job_id' => null];
        $this->assertSame($expected, TextExtractor::extractOrderAndJobId($text));
    }

    public function testExtractQtyWithWordBeforeNumber()
    {
        $text = "img_00000 (copy 1).jpg";
        $this->assertSame(1, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWithWordAfterNumber()
    {
        $text = "img_00000 (1 copy).jpg";
        $this->assertSame(1, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWithNoisySpacing()
    {
        $text = "document - quantity:   25.pdf";
        $this->assertSame(25, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWithAbbreviatedNotation()
    {
        $text = "order970-job74688-cps42";
        $this->assertSame(42, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWithCopiesBeforeNumber()
    {
        $text = "A3_scans_copies_15_final.pdf";
        $this->assertSame(15, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWithNumberBeforeLabel()
    {
        $text = "file-10copies.pdf";
        $this->assertSame(10, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWhenNoQtyPresentUsesDefault()
    {
        $text = "file-no-qty-info.pdf";
        $this->assertSame(1, TextExtractor::extractQty($text));
    }

    public function testExtractQtyWhenNoQtyPresentUsesCustomDefault()
    {
        $text = "nothing-matches-here.txt";
        $this->assertSame(99, TextExtractor::extractQty($text, ['default_qty' => 99]));
    }
}
