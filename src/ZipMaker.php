<?php

namespace arajcany\ToolBox;

use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use ZipArchive;

/**
 * Class ZipMaker
 *
 * @package arajcany\ToolBox
 */
class ZipMaker
{
    /**
     * Make a file list based on an input directory.
     * Can pass in a list of relative files/folders to ignore.
     *
     * `
     * //the directory to Zip
     * $basePath = "c:\dir\to\zip\"
     *
     * //ignore these relative files/folders
     * //elements ending in a slash are treated as directories
     * $ignoreList = [
     *  "config", //this will ignore the config file
     *  "config\\", //this will ignore the config directory recursively
     * ]
     * `
     *
     * @param null $basePath
     * @param array $ignoreList
     * @return array
     */
    public function makeFileList($basePath = null, $ignoreList = [])
    {
        $basePath = Formatter::makeEndsWith($basePath, "\\");

        $folderObj = new Folder($basePath);
        $files = $folderObj->findRecursive();

        $accepted = [];
        $rejected = [];
        foreach ($files as $file) {
            $rejectedFlag = false;
            //compare to $ignoreLists
            foreach ($ignoreList as $ignored) {
                //add the base path
                $ignored = $basePath . $ignored;

                //based on directory
                if (Formatter::endsWith($ignored, "\\") || Formatter::endsWith($ignored, "/")) {
                    if (Formatter::startsWith($file, $ignored)) {
                        $rejectedFlag = true;
                    }
                }

                //based on file
                if ($file === $ignored) {
                    $rejectedFlag = true;
                }
            }

            if ($rejectedFlag) {
                $rejected[] = $file;
            } else {
                $accepted[] = $file;
            }
        }

        return $accepted;
    }

    /**
     * Create a Zip from the give file list
     *
     * @param array $fileList
     * @param string $zipLocation
     * @param string $basePathRemove
     * @param string $basePathAdd
     * @return bool|int
     */
    public function makeZipFromFileList($fileList = [], $zipLocation = '', $basePathRemove = '', $basePathAdd = '')
    {
        //initialize archive object
        $zip = new ZipArchive();
        $zip->open($zipLocation, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $totalCount = count($fileList);
        $counter = 1;
        foreach ($fileList as $file) {
            $internalFile = $file;

            if (strlen($basePathRemove) > 0) {
                $internalFile = str_replace($basePathRemove, "", $internalFile);
            }

            if (strlen($basePathAdd) > 0) {
                $internalFile = $basePathAdd . "\\" . $internalFile;
            }

            $zip->addFile($file, $internalFile);
            $counter++;
        }

        if ($zip->close()) {
            return $totalCount;
        } else {
            return false;
        }
    }
}