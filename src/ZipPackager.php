<?php

namespace arajcany\ToolBox;


use arajcany\ToolBox\Utility\TextFormatter;
use League\CLImate\CLImate;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use ZipArchive;

/**
 * ZipPackager: Handy functions to create a Zip file from a directory
 *
 * @property CLImate $io
 */
class ZipPackager
{
    private CLImate $io;
    private bool $verbose = false;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->io = new CLImate;
    }

    /**
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    private function out($message)
    {
        if ($this->verbose) {
            $this->io->out($message);
        }
    }

    /**
     * Wrapper function
     *
     * @param string $localFsoRootPath
     * @return array
     */
    public function rawFileList(string $localFsoRootPath): array
    {
        return $this->rawList($localFsoRootPath, 'file');
    }

    /**
     * Wrapper function
     *
     * @param string $localFsoRootPath
     * @return array
     */
    public function rawFolderList(string $localFsoRootPath): array
    {
        return $this->rawList($localFsoRootPath, 'folder');
    }

    /**
     * Wrapper function
     *
     * @param string $localFsoRootPath
     * @return array multidimensional array ['folders'=>[], 'files'=>[]]
     */
    public function rawFileAndFolderList(string $localFsoRootPath): array
    {
        return $this->rawList($localFsoRootPath, 'both');
    }

    /**
     * Return a file/folder listing for a given directory.
     *
     * Returns an array of file/folder names - empty array on failure
     * The listing is pure and unfiltered.
     * File/folder names include paths relative to the $localFsoRootPath
     *
     * @param string $localFsoRootPath
     * @param string $mode
     * @return array
     */
    private function rawList(string $localFsoRootPath, string $mode): array
    {
        /**
         * @var FileAttributes|DirectoryAttributes $content
         */

        $localFilesystem = new Filesystem(new LocalFilesystemAdapter($localFsoRootPath));

        $rawFileList = [];
        $rawFolderList = [];
        try {
            $contents = $localFilesystem->listContents('', true);
        } catch (\Throwable $exception) {
            return [];
        }

        foreach ($contents as $content) {
            if ($content->isDir()) {
                $currentPath = $content->path();
                $currentPath = str_replace("\\", "/", $currentPath);
                $currentPath = TextFormatter::makeEndsWith($currentPath, "/");
                $rawFolderList[] = $currentPath;
            }

            if ($content->isFile()) {
                $currentPath = $content->path();
                $rawFileList[] = $currentPath;
            }
        }

        $this->out(__("Found {0} Files", count($rawFileList)));
        $this->out(__("Found {0} Folder", count($rawFileList)));

        if ($mode === 'folder') {
            return $rawFolderList;
        } elseif ($mode === 'file') {
            return $rawFileList;
        } elseif ($mode === 'both') {
            return [
                'folders' => $rawFolderList,
                'files' => $rawFileList,
            ];
        } else {
            return [];
        }

    }

    /**
     * Ignore these relative files/folders
     *
     * Works similar to a .gitignore file
     *  - elements ending in a slash are treated as directories
     *  - directories are ignored recursively
     *  - file ignore points to a single file
     *
     * $foldersAndFiles = [
     *  "config", //this will ignore the config file
     *  "config\\", //this will ignore the config directory recursively
     * ]
     *
     * @param $rawFileList
     * @param $rejectFoldersAndFiles
     * @return array
     */
    public function filterOutFoldersAndFiles($rawFileList, $rejectFoldersAndFiles): array
    {
        $toReturn = [];

        foreach ($rawFileList as $listItem) {
            $listItemNormalised = $this->normalisePath($listItem);

            $keepListItemFlag = true;
            foreach ($rejectFoldersAndFiles as $rejectFolderOrFile) {
                $rejectFolderOrFileNormalised = $this->normalisePath($rejectFolderOrFile);

                //based on directory
                if (TextFormatter::endsWith($rejectFolderOrFileNormalised, "/")) {
                    if (TextFormatter::startsWith($listItemNormalised, $rejectFolderOrFileNormalised)) {
                        $keepListItemFlag = false;
                    }
                }

                //based on file
                if ($listItemNormalised === $rejectFolderOrFileNormalised) {
                    $keepListItemFlag = false;
                }
            }

            if ($keepListItemFlag) {
                $toReturn[] = $listItem;
            }
        }

        return $toReturn;
    }

    /**
     * Wrapper function
     *
     * @param $rawFileList
     * @param array $filenames
     * @return array
     */
    public function filterOutByFileName($rawFileList, array $filenames = []): array
    {
        return $this->filterByEnding($rawFileList, $filenames);
    }

    /**
     * Wrapper function
     *
     * @param $rawFileList
     * @param array $extensions
     * @return array
     */
    public function filterOutByFileExtension($rawFileList, array $extensions = []): array
    {
        $extensionsCorrected = [];
        foreach ($extensions as $extension) {
            $extensionsCorrected[] = TextFormatter::makeStartsWith($extension, ".");
        }
        return $this->filterByEnding($rawFileList, $extensionsCorrected);
    }

    /**
     * Used to filter out a specific file name or file extension
     *
     * @param $rawFileList
     * @param $endings
     * @return array
     */
    private function filterByEnding($rawFileList, $endings): array
    {
        $toReturn = [];

        foreach ($rawFileList as $listItem) {
            $listItemNormalised = $this->normalisePath($listItem);

            $keepListItemFlag = true;
            foreach ($endings as $ending) {
                $endingNormalised = $this->normalisePath($ending);
                if (TextFormatter::endsWith($listItemNormalised, $endingNormalised)) {
                    $keepListItemFlag = false;
                }
            }

            if ($keepListItemFlag) {
                $toReturn[] = $listItem;
            }
        }

        return $toReturn;
    }

    /**
     * Static filter to strip out things like Vendor 'tests' directory
     *
     * @param $rawFileList
     * @return array
     */
    public function filterOutVendorExtras($rawFileList): array
    {
        $toReturn = [];

        $dirs = ['/tests/', '/docs/', '/examples/',];

        foreach ($rawFileList as $listItem) {
            $listItemNormalised = $this->normalisePath($listItem);

            $keepListItemFlag = true;
            foreach ($dirs as $dir) {
                if (
                    (strpos($listItemNormalised, '/vendor/') !== false || strpos($listItemNormalised, 'vendor/') !== false) &&
                    strpos($listItemNormalised, $dir) !== false
                ) {
                    $keepListItemFlag = false;
                }
            }

            if ($keepListItemFlag) {
                $toReturn[] = $listItem;
            }
        }

        return $toReturn;
    }

    /**
     * Convert a raw file list into a ZipList
     *
     * @param $rawFileList
     * @param string $fsoRoot
     * @param string $zipRoot
     * @return array
     */
    public function convertRawFileListToZipList($rawFileList, string $fsoRoot = '', string $zipRoot = ''): array
    {
        $zipList = [];

        if (strlen($fsoRoot) > 0) {
            $fsoRoot = rtrim($fsoRoot, "\\");
            $fsoRoot = TextFormatter::makeEndsWith($fsoRoot, "/");
        }

        if (strlen($zipRoot) > 0) {
            $zipRoot = rtrim($zipRoot, "\\");
            $zipRoot = TextFormatter::makeEndsWith($zipRoot, "/");
        }

        foreach ($rawFileList as $file) {
            if (is_file($fsoRoot . $file)) {
                $zipList[] = [
                    'external' => $fsoRoot . $file,
                    'internal' => $zipRoot . $file,
                ];
            }
        }

        return $zipList;
    }


    /**
     * Create a Zip from the given file list
     *
     * $zipList example
     * [
     * {
     * "external": "W:\\Projects\\Photo\\EyeSpy\\bin\\cake",
     * "internal": "EyeSpy\\bin\\cake"
     * },
     * {
     * "external": "W:\\Projects\\Photo\\EyeSpy\\bin\\cake.bat",
     * "internal": "EyeSpy\\bin\\cake.bat"
     * },
     * ]
     *
     *
     * @param array $zipList
     * @param string $zipLocation
     * @return bool
     */
    public function makeZipFromZipList(string $zipLocation = '', array $zipList = []): bool
    {
        //initialize archive object
        $zip = new ZipArchive();
        $zip->open($zipLocation, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $totalCount = count($zipList);
        $counter = 0;
        foreach ($zipList as $file) {
            if (is_array($file)) {
                if (isset($file['external']) && isset($file['internal'])) {
                    $result = $zip->addFile($file['external'], $file['internal']);
                    if ($result) {
                        $counter++;
                        if ($counter % 500 === 0) {
                            $this->out(__("Zipped {0} of {1} files.", $counter, $totalCount));
                        }
                    }
                }
            }
        }

        $this->out(__("Closing Zip file."));
        if ($zip->close()) {
            if ($counter === $totalCount) {
                $this->out(__("Zipped {0} files.", $totalCount));
                return true;
            } else {
                $this->out(__("Only zipped {0} of {1} files", $counter, $totalCount));
                return false;
            }
        } else {
            $this->out(__("Failed to close Zip file."));
            return false;
        }
    }

    /**
     * Normalise for comparison
     *  - lowercase
     *  - forward slash separators
     *
     * @param $dirtyPath
     * @return array|string|string[]
     */
    private function normalisePath($dirtyPath)
    {
        $cleanPath = $dirtyPath;
        $cleanPath = strtolower($cleanPath);
        $cleanPath = str_replace("\\", "/", $cleanPath);
        return $cleanPath;
    }


    /**
     * @param $zipLocation
     * @param $localFsoRootPath
     * @return array
     */
    public function extractZip($zipLocation, $localFsoRootPath = null, $eliminateRoot = false): array
    {
        $localFsoRootPath = TextFormatter::makeEndsWith(trim($localFsoRootPath, "\\/"), "\\");
        if (!is_dir($localFsoRootPath)) {
            @mkdir($localFsoRootPath, 0777, true);
        }

        $previousFileAndFolderList = $this->rawFileAndFolderList($localFsoRootPath);
        $previousFolderList = $previousFileAndFolderList['folders'];
        $previousFileList = $previousFileAndFolderList['files'];


        $za = new ZipArchive();
        $za->open($zipLocation);

        $fileList = [];
        $fileNames = [];
        $folderList = [];
        $folderNames = [];
        $roots = [];
        for ($i = 0; $i < $za->numFiles; $i++) {
            $entry = $za->statIndex($i);
            $entry = str_replace("\\", "/", $entry);

            if (TextFormatter::endsWith($entry['name'], "/")) {
                $folderList[] = $entry;
                $folderNames[] = $entry['name'];

                //directory so always include first part
                $entryParts = explode("/", $entry['name']);
                if (isset($entryParts[0])) {
                    $roots[] = $entryParts[0];
                }
            } else {
                $fileList[] = $entry;
                $fileNames[] = $entry['name'];

                //could be a file in the root of zip so double check
                $entryParts = explode("/", $entry['name']);
                if (isset($entryParts[1])) {
                    $roots[] = $entryParts[0];
                }
            }
        }

        $roots = array_values(array_unique($roots));

        if ($eliminateRoot && count($roots) === 1) {
            //special extraction to eliminate root folder
            $rootReplacement = TextFormatter::makeEndsWith($roots[0], "/");

            //make directories
            foreach ($folderNames as $folderName) {
                $localPathFinal = $localFsoRootPath . str_replace($rootReplacement, "", $folderName);
                @mkdir($localPathFinal, 0777, true);
            }

            //extract files
            foreach ($fileNames as $fileName) {
                $fp = $za->getStream($fileName);
                $contents = '';
                if ($fp) {
                    while (!feof($fp)) {
                        $contents .= fread($fp, 1024);
                    }
                    $localPathFinal = $localFsoRootPath . str_replace($rootReplacement, "", $fileName);
                    fclose($fp);
                    file_put_contents($localPathFinal, $contents);
                }
            }
        } else {
            //extract files as per structure in the zip
            $rootReplacement = '';
            if (is_dir($localFsoRootPath)) {
                $za->extractTo($localFsoRootPath);
            }
        }

        $report = [
            'status' => false,
            'folders' => $folderList,
            'files' => $fileList,
            'folder_names' => $folderNames,
            'file_names' => $fileNames,
            'root_folders' => $roots,
            'extract_passed' => [],
            'extract_failed' => [],
            'crc_passed' => [],
            'crc_failed' => [],
        ];

        foreach ($fileList as $file) {
            $fullPath = $localFsoRootPath . str_replace($rootReplacement, '', $file['name']);
            if (is_file($fullPath)) {
                $report['extract_passed'][] = $file['name'];

                //extraction may have happened but could be corrupt
                $crcCheck = crc32(file_get_contents($fullPath));
                if (strval($crcCheck) === strval($file['crc'])) {
                    $report['crc_passed'][] = $file['name'];
                } else {
                    $report['crc_failed'][] = $file['name'];
                }
            } else {
                $report['extract_failed'][] = $file['name'];
            }
        }

        //file/folder listing diff
        $currentFolderList = str_replace($rootReplacement, '', $folderNames);
        $currentFileList = str_replace($rootReplacement, '', $fileNames);
        $report['folder_list_diff'] = array_values(array_diff($previousFolderList, $currentFolderList));
        $report['file_list_diff'] = array_values(array_diff($previousFileList, $currentFileList));

        //true is based on crc_passed extracted files === number of files in archive
        if (count($report['crc_passed']) === count($report['file_names'])) {
            $report['status'] = true;
        }

        return $report;
    }


}