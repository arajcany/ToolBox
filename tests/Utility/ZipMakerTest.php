<?php

namespace arajcany\Test\Utility;

use PHPUnit\Framework\TestCase;
use arajcany\ToolBox\Utility\ZipMaker;

/**
 * Class TimeMakerTest
 * @package phpUnitTutorial\Test
 *
 * @property \arajcany\ToolBox\Utility\ZipMaker $zm
 */
class ZipMakerTest extends TestCase
{
    public $tstHomeDir;
    public $tstTmpDir;
    public $now;
    public $zm;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->now = date("Y-m-d H:i:s");
        $this->zm = new ZipMaker();
        $this->tstHomeDir = __DIR__ . "\\..\\";
        $this->tstTmpDir = __DIR__ . "\\..\\..\\tmp\\";
    }

    /**
     * Test that Sting input matches Array input
     */
    public function testMakeFileList()
    {
        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One\\empty.txt",
            "2 Two\\empty.txt",
            "3 Three\\empty.txt",
            "Sample\\empty.txt",
        ];

        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest";
        $fileList = $this->zm->makeFileList($baseDir);
        $fileListString = implode("\r\n", $fileList);
        foreach ($expectedFiles as $expectedFile) {
            $this->assertContains($expectedFile, $fileListString);
        }

        $this->assertEquals(count($expectedFiles), count($fileList));
    }

    /**
     * Test that Sting input matches Array input
     */
    public function testMakeZipFromFileList()
    {
        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One\\empty.txt",
            "2 Two\\empty.txt",
            "3 Three\\empty.txt",
            "Sample\\empty.txt",
        ];

        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest";
        $fileList = $this->zm->makeFileList($baseDir);
        //$zipFile = ROOT . "\\tmp\\{$this->now}_out.zip";
        //$zipResult = $this->zm->makeZipFromFileList($fileList, $zipFile);

        $this->assertEquals($expectedFiles, $expectedFiles);
    }

    private function fpc($filename, $data, $flags = 0, $context = null)
    {
        file_put_contents($filename, $data, $flags, $context);
    }


}