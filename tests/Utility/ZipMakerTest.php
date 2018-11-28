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
        $this->tstHomeDir = str_replace("\\Utility", '', __DIR__) . DS;
        $this->tstTmpDir = __DIR__ . "\\..\\..\\tmp\\";
    }

    /**
     * Test that counts match
     */
    public function testCountFileList()
    {

        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One\\empty.pdf",
            "1 One\\empty.txt",
            "2 Two\\empty.jpg",
            "2 Two\\empty.txt",
            "3 Three\\empty.indd",
            "3 Three\\empty.txt",
            "Sample\\empty.bat",
            "Sample\\empty.txt",
        ];

        $fileList = $this->zm->makeFileList($baseDir);
        $this->assertEquals(count($expectedFiles), count($fileList));
    }

    /**
     * Test that arrays match
     */
    public function testCompareFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFiles = [
            $baseDir . "config",
            $baseDir . "Sample.txt",
            $baseDir . "1 One\\empty.pdf",
            $baseDir . "1 One\\empty.txt",
            $baseDir . "2 Two\\empty.jpg",
            $baseDir . "2 Two\\empty.txt",
            $baseDir . "3 Three\\empty.indd",
            $baseDir . "3 Three\\empty.txt",
            $baseDir . "Sample\\empty.bat",
            $baseDir . "Sample\\empty.txt",
        ];

        $fileList = $this->zm->makeFileList($baseDir);
        $this->assertEquals($expectedFiles, $fileList);
    }

    /**
     * Test that whitelist
     */
    public function testWhitelistFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFiles = [
            $baseDir . "Sample.txt",
            $baseDir . "1 One\\empty.txt",
            $baseDir . "2 Two\\empty.txt",
            $baseDir . "3 Three\\empty.txt",
            $baseDir . "Sample\\empty.txt",
        ];
        $whitelist = ['txt'];
        $fileList = $this->zm->makeFileList($baseDir, [], false, $whitelist, null);
        $this->assertEquals($expectedFiles, $fileList);


        $expectedFiles = [
            $baseDir . "Sample.txt",
            $baseDir . "1 One\\empty.pdf",
            $baseDir . "1 One\\empty.txt",
            $baseDir . "2 Two\\empty.txt",
            $baseDir . "3 Three\\empty.txt",
            $baseDir . "Sample\\empty.txt",
        ];
        $whitelist = ['txt', 'pdf'];
        $fileList = $this->zm->makeFileList($baseDir, [], false, $whitelist, null);
        $this->assertEquals($expectedFiles, $fileList);


        $expectedFiles = [
            $baseDir . "1 One\\empty.pdf",
            $baseDir . "3 Three\\empty.indd",
        ];
        $whitelist = ['indd', 'pdf'];
        $fileList = $this->zm->makeFileList($baseDir, [], false, $whitelist, null);
        $this->assertEquals($expectedFiles, $fileList);
    }

    /**
     * Test that blacklist
     */
    public function testBlacklistFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFiles = [
            $baseDir . "config",
            //$baseDir . "Sample.txt",
            $baseDir . "1 One\\empty.pdf",
            //$baseDir . "1 One\\empty.txt",
            $baseDir . "2 Two\\empty.jpg",
            //$baseDir . "2 Two\\empty.txt",
            $baseDir . "3 Three\\empty.indd",
            //$baseDir . "3 Three\\empty.txt",
            $baseDir . "Sample\\empty.bat",
            //$baseDir . "Sample\\empty.txt",
        ];
        $blacklist = ['txt'];
        $fileList = $this->zm->makeFileList($baseDir, [], false, null, $blacklist);
        $this->assertEquals($expectedFiles, $fileList);


        $expectedFiles = [
            $baseDir . "config",
            //$baseDir . "Sample.txt",
            //$baseDir . "1 One\\empty.pdf",
            //$baseDir . "1 One\\empty.txt",
            $baseDir . "2 Two\\empty.jpg",
            //$baseDir . "2 Two\\empty.txt",
            $baseDir . "3 Three\\empty.indd",
            //$baseDir . "3 Three\\empty.txt",
            $baseDir . "Sample\\empty.bat",
            //$baseDir . "Sample\\empty.txt",
        ];
        $blacklist = ['txt', 'pdf'];
        $fileList = $this->zm->makeFileList($baseDir, [], false, null, $blacklist);
        $this->assertEquals($expectedFiles, $fileList);


        $expectedFiles = [
            $baseDir . "config",
            $baseDir . "Sample.txt",
            //$baseDir . "1 One\\empty.pdf",
            $baseDir . "1 One\\empty.txt",
            $baseDir . "2 Two\\empty.jpg",
            $baseDir . "2 Two\\empty.txt",
            //$baseDir . "3 Three\\empty.indd",
            $baseDir . "3 Three\\empty.txt",
            $baseDir . "Sample\\empty.bat",
            $baseDir . "Sample\\empty.txt",
        ];
        $blacklist = ['indd', 'pdf'];
        $fileList = $this->zm->makeFileList($baseDir, [], false, null, $blacklist);
        $this->assertEquals($expectedFiles, $fileList);
    }

    private function fpc($filename, $data, $flags = 0, $context = null)
    {
        file_put_contents($filename, $data, $flags, $context);
    }


}