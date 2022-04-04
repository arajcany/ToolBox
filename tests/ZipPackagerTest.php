<?php

use arajcany\ToolBox\ZipPackager;
use PHPUnit\Framework\TestCase;

class ZipPackagerTest extends TestCase
{
    public $tstHomeDir;
    public $tstTmpDir;
    public $now;
    public $zp;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->now = date("Y-m-d H:i:s");
        $this->zp = new ZipPackager();
        $this->tstHomeDir = str_replace("\\Utility", '', __DIR__) . DS;
        $this->tstTmpDir = __DIR__ . "\\..\\tmp\\";
    }


    /**
     * Test that counts match
     */
    public function testCountRawFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFilesCount = 10;

        $rawFileList = $this->zp->rawFileList($baseDir);
        $this->assertEquals($expectedFilesCount, count($rawFileList));
    }

    /**
     * Test that arrays match
     */
    public function testCompareRawFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One/empty.pdf",
            "1 One/empty.txt",
            "2 Two/empty.jpg",
            "2 Two/empty.txt",
            "3 Three/empty.indd",
            "3 Three/empty.txt",
            "Sample/empty.bat",
            "Sample/empty.txt",
        ];
        sort($expectedFiles);

        $rawFileList = $this->zp->rawFileList($baseDir);
        sort($rawFileList);

        $this->assertEquals($expectedFiles, $rawFileList);
    }

    /**
     * Test that arrays match
     */
    public function testFilterOutFoldersAndFiles()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        //Filtering Folders
        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One/empty.pdf",
            "1 One/empty.txt",
            "2 Two/empty.jpg",
            "2 Two/empty.txt",
//            "3 Three/empty.indd",
//            "3 Three/empty.txt",
//            "Sample/empty.bat",
//            "Sample/empty.txt",
        ];
        $rejectFolders = [
            "3 Three/",
            "sample/"
        ];
        $rawFileList = $this->zp->rawFileList($baseDir);
        $rawFileList = $this->zp->filterOutFoldersAndFiles($rawFileList, $rejectFolders);
        sort($expectedFiles);
        sort($rawFileList);
        $this->assertEquals($expectedFiles, $rawFileList);

        //Filtering Files
        $expectedFiles = [
            "config",
            "Sample.txt",
//            "1 One/empty.pdf",
            "1 One/empty.txt",
//            "2 Two/empty.jpg",
            "2 Two/empty.txt",
//            "3 Three/empty.indd",
            "3 Three/empty.txt",
            "Sample/empty.bat",
            "Sample/empty.txt",
        ];
        $rejectFolders = [
            "1 One/empty.pdf",
            "2 Two/empty.jpg",
            "3 Three/empty.indd",
        ];
        $rawFileList = $this->zp->rawFileList($baseDir);
        $rawFileList = $this->zp->filterOutFoldersAndFiles($rawFileList, $rejectFolders);
        sort($expectedFiles);
        sort($rawFileList);

        $this->assertEquals($expectedFiles, $rawFileList);
    }

    /**
     * Test that arrays match
     */
    public function testFilterOutByFileName()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        //Filtering by file name
        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One/empty.pdf",
//            "1 One/empty.txt",
            "2 Two/empty.jpg",
//            "2 Two/empty.txt",
            "3 Three/empty.indd",
//            "3 Three/empty.txt",
            "Sample/empty.bat",
//            "Sample/empty.txt",
        ];
        $rejectFileNames = [
            "empty.txt",
        ];
        $rawFileList = $this->zp->rawFileList($baseDir);
        $rawFileList = $this->zp->filterOutByFileName($rawFileList, $rejectFileNames);
        sort($expectedFiles);
        sort($rawFileList);

        $this->assertEquals($expectedFiles, $rawFileList);
    }

    /**
     * Test that arrays match
     */
    public function testFilterOutByFileExtension()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        //Filtering by file name
        $expectedFiles = [
            "config",
            "Sample.txt",
//            "1 One/empty.pdf",
            "1 One/empty.txt",
            "2 Two/empty.jpg",
            "2 Two/empty.txt",
            "3 Three/empty.indd",
            "3 Three/empty.txt",
//            "Sample/empty.bat",
            "Sample/empty.txt",
        ];
        $rejectFileExtensions = [
            "bat",
            "pdf",
        ];
        $rawFileList = $this->zp->rawFileList($baseDir);
        $rawFileList = $this->zp->filterOutByFileExtension($rawFileList, $rejectFileExtensions);
        sort($expectedFiles);
        sort($rawFileList);

        $this->assertEquals($expectedFiles, $rawFileList);
    }

    /**
     * Test conversion of RawFileList to ZipFileList
     */
    public function testConvertRawFileListToZipList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;

        $expectedFiles = [
            "config",
            "Sample.txt",
            "1 One/empty.pdf",
            "1 One/empty.txt",
            "2 Two/empty.jpg",
            "2 Two/empty.txt",
            "3 Three/empty.indd",
            "3 Three/empty.txt",
            "Sample/empty.bat",
            "Sample/empty.txt",
        ];
        sort($expectedFiles);

        $rawFileList = $this->zp->rawFileList($baseDir);
        sort($rawFileList);

        $zipList = $this->zp->convertRawFileListToZipList($rawFileList, $baseDir, "FooBar");

        $expectedEntryZero = [
            "external" => $baseDir . "1 One/empty.pdf",
            "internal" => "FooBar/1 One/empty.pdf",
        ];

        $this->assertEquals($expectedEntryZero, $zipList[0]);
    }

    public function testMakeZipFromZipList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . DS;
        print_r($this->tstTmpDir);
        $zipFilePath = $this->tstTmpDir . mt_rand(111, 999) . ".zip";
        $rawFileList = $this->zp->rawFileList($baseDir);
        $zipList = $this->zp->convertRawFileListToZipList($rawFileList, $baseDir, "FooBar");
        $result = $this->zp->makeZipFromZipList($zipFilePath, $zipList);
        unlink($zipFilePath);

        $this->assertTrue($result);
    }


}
