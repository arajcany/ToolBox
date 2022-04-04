<?php

namespace arajcany\ToolBox\Utility;

use Cake\Filesystem\Folder;
use ZipArchive;


/**
 * Class ZipMaker
 *
 * @deprecated Use ZipPackager instead.
 * @package arajcany\ToolBox
 */
class ZipMaker
{
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $message = "The ZipMaker Class has been deprecated. Use ZipPackager instead.";
        trigger_error($message, E_USER_DEPRECATED);
    }

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
     * @param bool $removeBasePath
     * @param null $extWhitelist
     * @param null $extBlacklist
     * @return array
     */
    public function makeFileList(
        $basePath = null,
        $ignoreList = [],
        $removeBasePath = false,
        $extWhitelist = null,
        $extBlacklist = null
    )
    {
        $basePathClone = $basePath;
        $basePath = TextFormatter::makeEndsWith($basePath, "\\");

        $folderObj = new Folder($basePath);

        if ($extWhitelist == null && $extBlacklist == null) {
            $files = $folderObj->findRecursive();
        } elseif (is_array($extWhitelist)) {
            //    final regex looks like this
            //    .*\.(jpg|pdf|txt)
            $extWhitelistForRegex = implode("|", $extWhitelist);
            $files = $folderObj->findRecursive('.*\.(' . $extWhitelistForRegex . ')');
        } elseif (is_array($extBlacklist)) {
            //    final regex looks like this
            //    ^(.(?!.*\.jpg|.*\.pdf|.*\.txt))*$
            $extBlacklistForRegex = [];
            foreach ($extBlacklist as $extBlacklistItem) {
                $extBlacklistForRegex[] = '.*\.' . $extBlacklistItem;
            }
            $extBlacklistForRegex = implode("|", $extBlacklistForRegex);
            $files = $folderObj->findRecursive('^(.(?!' . $extBlacklistForRegex . '))*$');
        }

        $accepted = [];
        $rejected = [];
        foreach ($files as $file) {
            $rejectedFlag = false;
            //compare to $ignoreLists
            foreach ($ignoreList as $ignored) {
                //add the base path
                $ignored = $basePath . $ignored;

                //based on directory
                if (TextFormatter::endsWith($ignored, "\\") || TextFormatter::endsWith($ignored, "/")) {
                    if (TextFormatter::startsWith($file, $ignored)) {
                        $rejectedFlag = true;
                    }
                }

                //based on file
                if ($file === $ignored) {
                    $rejectedFlag = true;
                }
            }

            if ($removeBasePath) {
                $file = str_replace($basePathClone, "", $file);
                $file = ltrim($file, "\\");
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
            if (is_string($file)) {
                $internalFile = $file;

                if (strlen($basePathRemove) > 0) {
                    $internalFile = str_replace($basePathRemove, "", $internalFile);
                }

                if (strlen($basePathAdd) > 0) {
                    $internalFile = $basePathAdd . $internalFile;
                }

                $zip->addFile($file, $internalFile);
            } elseif (is_array($file)) {
                if (isset($file['external']) && isset($file['internal'])) {
                    $zip->addFile($file['external'], $file['internal']);
                }
            }

            $counter++;
        }

        if ($zip->close()) {
            return $totalCount;
        } else {
            return false;
        }
    }
}