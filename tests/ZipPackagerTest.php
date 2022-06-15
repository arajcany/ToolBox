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
        //$this->zp->setVerbose(true);
        $this->tstHomeDir = str_replace("\\Utility", '', __DIR__) . DS;
        $this->tstTmpDir = __DIR__ . "\\..\\tmp\\";
    }


    /**
     * Test that counts match
     */
    public function testCountRawFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

        $expectedFilesCount = 11;

        $rawFileList = $this->zp->rawFileList($baseDir);
        $this->assertCount($expectedFilesCount, $rawFileList);
    }

    /**
     * Test that arrays match
     */
    public function testCompareRawFileList()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

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
            "vendor/tests/test.txt",
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
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

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
            "vendor/tests/test.txt",
        ];
        $rejectFolders = [
            "3 Three\\",
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
            "vendor/tests/test.txt",
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
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

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
            "vendor/tests/test.txt",
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
    public function testFilterOutVendorExtras()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

        //Filtering by file name
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
//            "vendor/tests/test.txt",
        ];
        $rawFileList = $this->zp->rawFileList($baseDir);
        $rawFileList = $this->zp->filterOutVendorExtras($rawFileList);
        sort($expectedFiles);
        sort($rawFileList);

        $this->assertEquals($expectedFiles, $rawFileList);
    }

    /**
     * Test that arrays match
     */
    public function testFilterOutByFileExtension()
    {
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

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
            "vendor/tests/test.txt",
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
        $baseDir = $this->tstHomeDir . "ZipMakerDirectoryStructureTest" . "/";

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
            "vendor/tests/test.txt",
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

    public function testExtractionsNormal()
    {
        $zipLocation = $this->tstHomeDir . "ZipMakerDirectoryStructureTest.zip";


        //extraction 1
        $expected = [
            "folders" => [
                0 => "ZipMakerDirectoryStructureTest/",
                1 => "ZipMakerDirectoryStructureTest/1 One/",
                2 => "ZipMakerDirectoryStructureTest/2 Two/",
                3 => "ZipMakerDirectoryStructureTest/3 Three/",
                4 => "ZipMakerDirectoryStructureTest/4 Empty Folder/",
                5 => "ZipMakerDirectoryStructureTest/Sample/",
                6 => "ZipMakerDirectoryStructureTest/vendor/",
                7 => "ZipMakerDirectoryStructureTest/vendor/tests/",
            ],
            "files" => [
                0 => "ZipMakerDirectoryStructureTest/1 One/empty.pdf",
                1 => "ZipMakerDirectoryStructureTest/1 One/empty.txt",
                2 => "ZipMakerDirectoryStructureTest/2 Two/empty.jpg",
                3 => "ZipMakerDirectoryStructureTest/2 Two/empty.txt",
                4 => "ZipMakerDirectoryStructureTest/3 Three/empty.indd",
                5 => "ZipMakerDirectoryStructureTest/3 Three/empty.txt",
                6 => "ZipMakerDirectoryStructureTest/config",
                7 => "ZipMakerDirectoryStructureTest/Sample/empty.bat",
                8 => "ZipMakerDirectoryStructureTest/Sample/empty.txt",
                9 => "ZipMakerDirectoryStructureTest/Sample.txt",
                10 => "ZipMakerDirectoryStructureTest/vendor/tests/test.txt",
            ],
        ];
        $rndDir = mt_rand(1111, 9999);
        $rndDir = $this->tstTmpDir . $rndDir . "/";
        $eliminateRoot = false;
        $toExtract = [];
        $result = $this->zp->extractZip($zipLocation, $rndDir, $eliminateRoot, $toExtract);
        $rawList = $this->zp->rawFileAndFolderList($rndDir);
        $this->assertEquals($expected, $rawList);
        $this->assertTrue($result['status']);
        //cleanup
        $adapter = new League\Flysystem\Local\LocalFilesystemAdapter($rndDir);
        $filesystem = new League\Flysystem\Filesystem($adapter);
        $filesystem->deleteDirectory('/');


        //extraction 2
        $expected = [
            "folders" => [
                0 => "1 One/",
                1 => "2 Two/",
                2 => "3 Three/",
                3 => "4 Empty Folder/",
                4 => "Sample/",
                5 => "vendor/",
                6 => "vendor/tests/",
            ],
            "files" => [
                0 => "1 One/empty.pdf",
                1 => "1 One/empty.txt",
                2 => "2 Two/empty.jpg",
                3 => "2 Two/empty.txt",
                4 => "3 Three/empty.indd",
                5 => "3 Three/empty.txt",
                6 => "config",
                7 => "Sample/empty.bat",
                8 => "Sample/empty.txt",
                9 => "Sample.txt",
                10 => "vendor/tests/test.txt",
            ],
        ];
        $rndDir = mt_rand(1111, 9999);
        $rndDir = $this->tstTmpDir . $rndDir . "/";
        $eliminateRoot = true;
        $toExtract = [];
        $result = $this->zp->extractZip($zipLocation, $rndDir, $eliminateRoot, $toExtract);
        $rawList = $this->zp->rawFileAndFolderList($rndDir);
        $this->assertEquals($expected, $rawList);
        $this->assertTrue($result['status']);
        //cleanup
        $adapter = new League\Flysystem\Local\LocalFilesystemAdapter($rndDir);
        $filesystem = new League\Flysystem\Filesystem($adapter);
        $filesystem->deleteDirectory('/');


        //extraction 3
        $expected = [
            "folders" => [
                0 => "ZipMakerDirectoryStructureTest/",
                1 => "ZipMakerDirectoryStructureTest/4 Empty Folder/",
            ],
            "files" => [
                0 => "ZipMakerDirectoryStructureTest/Sample.txt",
            ],
        ];
        $rndDir = mt_rand(1111, 9999);
        $rndDir = $this->tstTmpDir . $rndDir . "/";
        $eliminateRoot = false;
        $toExtract = [
            "ZipMakerDirectoryStructureTest/4 Empty Folder/",
            "ZipMakerDirectoryStructureTest/Sample.txt",
        ];
        $result = $this->zp->extractZip($zipLocation, $rndDir, $eliminateRoot, $toExtract);
        $rawList = $this->zp->rawFileAndFolderList($rndDir);
        $this->assertEquals($expected, $rawList);
        $this->assertFalse($result['status']); //report delivers false positive as there is an extract list
        //cleanup
        $adapter = new League\Flysystem\Local\LocalFilesystemAdapter($rndDir);
        $filesystem = new League\Flysystem\Filesystem($adapter);
        $filesystem->deleteDirectory('/');


        //extraction 4
        $expected = [
            "folders" => [
                0 => "4 Empty Folder/",
            ],
            "files" => [
                0 => "Sample.txt",
            ],
        ];
        $rndDir = mt_rand(1111, 9999);
        $rndDir = $this->tstTmpDir . $rndDir . "/";
        $eliminateRoot = true;
        $toExtract = [
            "ZipMakerDirectoryStructureTest/4 Empty Folder/",
            "ZipMakerDirectoryStructureTest/Sample.txt",
        ];
        $result = $this->zp->extractZip($zipLocation, $rndDir, $eliminateRoot, $toExtract);
        $rawList = $this->zp->rawFileAndFolderList($rndDir);
        $this->assertEquals($expected, $rawList);
        $this->assertFalse($result['status']); //report delivers false positive as there is an extract list
        //cleanup
        $adapter = new League\Flysystem\Local\LocalFilesystemAdapter($rndDir);
        $filesystem = new League\Flysystem\Filesystem($adapter);
        $filesystem->deleteDirectory('/');
    }


}
