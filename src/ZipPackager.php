<?php

namespace arajcany\ToolBox;


use arajcany\ToolBox\Utility\TextFormatter;
use League\CLImate\CLImate;
use League\CLImate\TerminalObject\Dynamic\Progress;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use arajcany\ToolBox\Flysystem\Adapters\LocalFilesystemAdapter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use ZipArchive;

/**
 * ZipPackager: Handy functions to create a Zip file from a directory
 *
 * @property CLImate $io
 */
class ZipPackager
{
    private CLImate|false $io;
    private Progress|false $_progressBar = false;
    private bool $progressiveSaving = true;
    private bool $verbose = false;
    private bool $debugResults = false; //send output to JSON file in tmp directory
    private array $cache = [];

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        try {
            $this->io = new CLImate;
        } catch (\Throwable $exception) {
            $this->io = false;
        }
    }

    /**
     * @param bool $verbose
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @param bool $debugResults
     */
    public function setDebugResults(bool $debugResults): void
    {
        $this->debugResults = $debugResults;
    }

    /**
     * Progressive saving works by closing and reopening the zip at
     * specified intervals. It speeds up the zip operation by avoiding
     * a single long closing time at the end of the zip operation.
     *
     * @param bool $progressiveSaving
     */
    public function setProgressiveSaving(bool $progressiveSaving): void
    {
        $this->progressiveSaving = $progressiveSaving;
    }

    private function debugResults($results, string $filenameAppend = null): bool|int
    {
        if (!$this->debugResults) {
            return false;
        }

        $tmpDir = __DIR__ . "/../tmp/";
        if (!is_dir($tmpDir)) {
            return false;
        }

        $filename = date("Ymd-His-") . substr(explode(".", microtime(true))[1], 0, 3);
        if (!empty($filenameAppend)) {
            $filename .= "-$filenameAppend.json";
        } else {
            $filename .= ".json";
        }
        $filename = $tmpDir . $filename;
        $results = json_encode($results, JSON_PRETTY_PRINT);
        return file_put_contents($filename, $results);
    }

    private function applyReplacements($message, $replacers)
    {
        if (is_string($replacers) || is_int($replacers)) {
            $replacers = [$replacers];
        }

        if (!empty($replacers)) {
            foreach ($replacers as $k => $replacement) {
                $search = "{" . $k . "}";
                $message = str_replace($search, $replacement, $message);
            }
        }
        return $message;
    }

    private function out($message, ...$replacers)
    {
        if (!$this->verbose || !$this->io) {
            return;
        }
        $message = $this->applyReplacements($message, $replacers);
        $this->io->out($message);
    }

    private function info($message, ...$replacers)
    {
        if (!$this->verbose || !$this->io) {
            return;
        }
        $message = $this->applyReplacements($message, $replacers);
        $this->io->lightBlue($message);
    }

    private function success($message, ...$replacers)
    {
        if (!$this->verbose || !$this->io) {
            return;
        }
        $message = $this->applyReplacements($message, $replacers);
        $this->io->green($message);
    }

    private function warning($message, ...$replacers)
    {
        if (!$this->verbose || !$this->io) {
            return;
        }
        $message = $this->applyReplacements($message, $replacers);
        $this->io->lightYellow($message);
    }

    private function error($message, ...$replacers)
    {
        if (!$this->verbose || !$this->io) {
            return;
        }
        $message = $this->applyReplacements($message, $replacers);
        $this->io->lightRed($message);
    }

    private function progressBar($current, $total, $label = null)
    {
        if (!$this->verbose || !$this->io) {
            return;
        }

        $factor = ($total / 100);
        $currentFixed = min(100, intval(ceil($current / $factor)));
        $totalFixed = 100;
        if (empty($this->_progressBar)) {
            $this->io->out('');
            $this->_progressBar = $this->io->progress($totalFixed);
        }
        $this->_progressBar->total($totalFixed);
        $this->_progressBar->current($currentFixed, $label);

        //100% so reset for next time
        if ($current === $total) {
            $this->io->out('');
            $this->_progressBar = false;
        }
    }

    /**
     * Wrapper function
     *
     * @param string $localFsoRootPath
     * @return array
     */
    public function rawFileList(string $localFsoRootPath, $recursive = true): array
    {
        return $this->rawList($localFsoRootPath, 'file', $recursive);
    }

    /**
     * Wrapper function
     *
     * @param string $localFsoRootPath
     * @return array
     */
    public function rawFolderList(string $localFsoRootPath, $recursive = true): array
    {
        return $this->rawList($localFsoRootPath, 'folder', $recursive);
    }

    /**
     * Wrapper function
     *
     * @param string $localFsoRootPath
     * @return array multidimensional array ['folders'=>[], 'files'=>[]]
     */
    public function rawFileAndFolderList(string $localFsoRootPath, $recursive = true): array
    {
        return $this->rawList($localFsoRootPath, 'both', $recursive);
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
    private function rawList(string $localFsoRootPath, string $mode, $recursive = true): array
    {
        /**
         * @var FileAttributes|DirectoryAttributes $content
         */

        $localFilesystem = new Filesystem(new LocalFilesystemAdapter($localFsoRootPath));

        $rawFileList = [];
        $rawFolderList = [];
        try {
            $contents = $localFilesystem->listContents('', $recursive);
        } catch (\Throwable $exception) {
            return [];
        }

        foreach ($contents as $content) {
            if ($content->isDir()) {
                $currentPath = $content->path();
                $currentPath = str_replace("\\", "/", $currentPath);
                $currentPath = TextFormatter::makeDirectoryTrailingForwardSlash($currentPath);
                $rawFolderList[] = $currentPath;
            }

            if ($content->isFile()) {
                $currentPath = $content->path();
                $rawFileList[] = $currentPath;
            }
        }

        $this->out("Found {0} Files in FSO", count($rawFileList));
        $this->out("Found {0} Folders in FSO", count($rawFolderList));

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
     * Static filter to strip out things like Vendor 'tests' directories
     *
     * @param $rawFileList
     * @return array
     */
    public function filterOutVendorExtras($rawFileList): array
    {
        $toReturn = [];

        $dirs = ['/tests/', '/docs/', '/examples/',];

        $filteredOut = [];

        foreach ($rawFileList as $listItem) {
            $listItemNormalised = $this->normalisePath($listItem);
            $keepListItemFlag = true;

            if (str_contains($listItemNormalised, '/vendor/') || TextFormatter::startsWith($listItemNormalised, 'vendor/')) {
                foreach ($dirs as $dir) {
                    if (str_contains($listItemNormalised, $dir)) {
                        $keepListItemFlag = false;
                    }
                }
            }

            if ($keepListItemFlag) {
                $toReturn[] = $listItem;
            } else {
                $filteredOut[] = $listItem;
            }
        }
        return $toReturn;
    }

    /**
     * Static filter to strip out things like Vendor 'tests' directories
     *
     * @param $rawFileList
     * @return array
     */
    public function filterOutHidden($rawFileList): array
    {
        $toReturn = [];

        $dirs = ['.git/', '.idea/',];

        $filteredOut = [];

        foreach ($rawFileList as $listItem) {
            $listItemNormalised = $this->normalisePath($listItem);
            $keepListItemFlag = true;

            foreach ($dirs as $dir) {
                if (str_contains($listItemNormalised, $dir)) {
                    $keepListItemFlag = false;
                }
            }

            if ($keepListItemFlag) {
                $toReturn[] = $listItem;
            } else {
                $filteredOut[] = $listItem;
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
            $fsoRoot = TextFormatter::makeDirectoryTrailingForwardSlash($fsoRoot);
        }

        if (strlen($zipRoot) > 0) {
            $zipRoot = rtrim($zipRoot, "\\");
            $zipRoot = TextFormatter::makeDirectoryTrailingForwardSlash($zipRoot);
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
        $everyNFiles = 17; //every N files update the progress bar
        $progressiveSaveEvery = intval(ceil($totalCount / 7)); //every N files close and reopen the zip - aids with the final closign speed

        $counter = 0;
        foreach ($zipList as $file) {
            if (is_array($file)) {
                if (isset($file['external']) && isset($file['internal'])) {
                    if (is_file($file['external'])) {
                        $result = $zip->addFile($file['external'], $file['internal']);
                        if ($result) {
                            $counter++;
                            if (($counter % $everyNFiles === 0) || $counter === $totalCount) {
                                $message = $this->applyReplacements("Zipped {0} of {1} files.", [$counter, $totalCount]);
                                $this->progressBar($counter, $totalCount, $message);
                            }

                            if ($this->progressiveSaving) {
                                if (($counter % $progressiveSaveEvery === 0) && $counter !== $totalCount) {
                                    if ($zip->close()) {
                                        $zip->open($zipLocation);
                                    } else {
                                        $this->error("Failed to progressively save the Zip file.");
                                        return false;
                                    }
                                }
                            }
                        }
                    } else {
                        $this->error("Could not find the file {0}", $file['external']);
                    }
                }
            }
        }

        $this->out("Closing Zip file.");
        if ($zip->close()) {
            if ($counter === $totalCount) {
                $this->out("Zipped {0} files.", $totalCount);
                return true;
            } else {
                $this->error("Only zipped {0} of {1} files", $counter, $totalCount);
                return false;
            }
        } else {
            $this->error("Failed to close Zip file.");
            return false;
        }
    }


    /**
     * Normalise for comparison
     *  - lowercase
     *  - forward slash separators (PHP zip better compatability)
     *
     * @param string $dirtyPath
     * @param bool $lowerCase
     * @return string
     */
    private function normalisePath(string $dirtyPath, bool $lowerCase = true): string
    {
        $cleanPath = $dirtyPath;
        if ($lowerCase) {
            $cleanPath = strtolower($cleanPath);
        }
        $cleanPath = str_replace("\\", "/", $cleanPath);
        return $cleanPath;
    }


    /**
     * Determines what needs to be extracted based on missing files and change in CRC checksum.
     * Hands the unzipping to $this->extractZip().
     *
     * @param string $zipLocation
     * @param string $localFsoRootPath
     * @param bool $eliminateRoot
     * @return array
     */
    public function extractZipDifference(string $zipLocation, string $localFsoRootPath, bool $eliminateRoot = false): array
    {
        $diffReport = $this->getZipFsoDifference($zipLocation, $localFsoRootPath, $eliminateRoot);
        $toExtract = array_merge($diffReport['zipChanged'], $diffReport['zipExtra']);

        if (empty($toExtract)) {
            $this->info("Nothing to extract, FSO and ZIP are in sync.");
            $report = [
                'status' => true,
                'timer' => 0,
            ];
            $this->debugResults($report, 'extractZipDifference');
            return $report;
        }

        return $this->extractZip($zipLocation, $localFsoRootPath, $eliminateRoot, $toExtract);
    }


    /**
     * Zip Extraction workhorse. Simply extracts the zip to the given zip to the given location.
     * Can optionally remover the root folder or just extract the given list entries.
     *
     * @param string $zipLocation
     * @param string $localFsoRootPath
     * @param bool $eliminateRoot only removes if ALL files/folders are in a common root folder
     * @param null $toExtract array of entries to extract
     * @return array
     */
    public function extractZip(string $zipLocation, string $localFsoRootPath, bool $eliminateRoot = false, $toExtract = null): array
    {
        $timeStart = microtime(true);

        $localFsoRootPath = TextFormatter::makeDirectoryTrailingBackwardSlash($localFsoRootPath);
        if (!is_dir($localFsoRootPath)) {
            if (!$this->mkdir($localFsoRootPath, 0777, true)) {
                return ['status' => false,];
            }
        }

        //collect information that will be used in cross-checking and extraction
        $entryStats = $this->zipStats($zipLocation, true);
        $fileEntries = [];
        $fileNames = [];
        $folderEntries = [];
        $folderNames = [];
        $roots = [];
        $massExtractionGroups = [];
        foreach ($entryStats as $k => $stat) {
            if (!empty($toExtract)) {
                if (!in_array($stat['name'], $toExtract)) {
                    continue;
                }
            }

            if ($stat['type'] == 'folder') {
                $folderEntries[] = $stat;
            } elseif ($stat['type'] == 'file') {
                $fileEntries[] = $stat;
            }

            if (!empty($stat['path_info_folder'])) {
                $folderNames[] = $stat['path_info_folder'];
            }

            if (!empty($stat['path_info_file'])) {
                $fileNames[] = $stat['path_info_file'];
            }

            if (!empty($stat['root'])) {
                $roots[] = $stat['root'];
            }

            $massExtractionGroups[($k % 100)][] = $stat['name'];
        }
        $roots = array_values(array_unique($roots));
        $folderNames = array_values(array_unique($folderNames));
        $fileNames = array_values(array_unique($fileNames));

        //perform the extraction
        $za = new ZipArchive();
        $za->open($zipLocation);
        if ($eliminateRoot && count($roots) === 1) {
            //special extraction to eliminate root folder
            $rootReplacement = TextFormatter::makeDirectoryTrailingForwardSlash($roots[0]);

            //make directories ($folderNames could be empty if the maker of the ZIP did not explicitly put directories into the zip)
            foreach ($folderNames as $folderName) {
                $localPathFinal = $localFsoRootPath . str_replace($rootReplacement, "", $folderName);
                if (!is_dir($localPathFinal)) {
                    $this->mkdir($localPathFinal, 0777, true);
                }
            }

            //extract files
            $everyNFiles = 17;
            $totalCount = count($fileEntries);
            $counter = 0;
            foreach ($fileEntries as $fileEntry) {
                $counter++;
                if (($counter % $everyNFiles === 0) || $counter === $totalCount) {
                    $message = $this->applyReplacements("Extracting {0} of {1} files.", [$counter, $totalCount]);
                    $this->progressBar($counter, $totalCount, $message);
                }

                $fp = $za->getStream($fileEntry['name']);
                $contents = '';
                if ($fp) {
                    while (!feof($fp)) {
                        $contents .= fread($fp, 1024);
                    }
                    fclose($fp);
                    $localPathFinal = $localFsoRootPath . str_replace($rootReplacement, "", $fileEntry['name_normalised']);

                    //test that dir exists
                    $bareDir = pathinfo($localPathFinal, PATHINFO_DIRNAME);
                    if (!is_dir($bareDir)) {
                        $this->mkdir($bareDir, 0777, true);
                    }

                    file_put_contents($localPathFinal, $contents);
                }
            }
        } else {
            //extract files as per structure in the zip
            $this->info("Extracting files, please be patient this could take a while.");
            $rootReplacement = '';
            if (is_dir($localFsoRootPath)) {
                $totalCount = count($folderEntries) + count($fileEntries);
                $totalGroups = count($massExtractionGroups);
                $groupCount = 0;
                $counter = 0;
                foreach ($massExtractionGroups as $groupOfNames) {
                    $groupCount = $groupCount + count($groupOfNames);
                    $counter++;
                    $message = $this->applyReplacements("Extracting {0} of {1} files.", [$groupCount, $totalCount]);
                    $this->progressBar($counter, $totalGroups, $message);
                    $za->extractTo($localFsoRootPath, $groupOfNames);
                }
            }
        }

        //considered successful extraction if the FSO is in sync with the ZIP
        $diffReport = $this->getZipFsoDifference($zipLocation, $localFsoRootPath, $eliminateRoot);
        if (empty($diffReport['zipChanged']) && empty($diffReport['zipExtra'])) {
            $isSuccess = true;
        } else {
            $isSuccess = false;
        }
        $report = [
            'status' => $isSuccess,
            'timer' => 0,
        ];
        $timerEnd = microtime(true);
        $report['timer'] = $timerEnd - $timeStart;

        $this->debugResults($report, 'extractZip');

        return $report;
    }

    /**
     * Determines the difference between FSO and ZIP based on missing files and changes in CRC checksum.
     *
     * @param string $zipLocation
     * @param string $localFsoRootPath
     * @param bool $eliminateRoot
     * @return array
     */
    public function getZipFsoDifference(string $zipLocation, string $localFsoRootPath, bool $eliminateRoot = false): array
    {
        $options = [
            'directory' => true,
            'file' => true,
            'sha1' => false,
            'crc32' => true,
            'mime' => false,
            'size' => false
        ];

        $localFsoRootPath = TextFormatter::makeDirectoryTrailingForwardSlash($localFsoRootPath);

        $zipStats = $this->zipStats($zipLocation, false);
        $fsoStats = $this->fileStats($localFsoRootPath, null, $options, false);

        $zipRootFolder = '';
        if (isset($zipStats[0]['root'])) {
            $zipRootFolder = TextFormatter::makeDirectoryTrailingForwardSlash($zipStats[0]['root']);
        }

        $report = [
            'fsoChanged' => [], //files present in ZIP and FSO but crc32 do not match
            'zipChanged' => [], //files present in ZIP and FSO but crc32 do not match

            'fsoMissing' => [], //missing files in the FSO (same as extra in the ZIP)
            'zipExtra' => [], //extra files on the ZIP (same as missing in the FSO)

            'fsoExtra' => [], //extra files on the FSO (same as missing in the ZIP)
            'zipMissing' => [], //missing files in the ZIP (same as extra in the FSO)
        ];


        $zipMap = [];
        foreach ($zipStats as $zipStat) {
            $nPath = $zipStat['name_normalised'];

            if (!TextFormatter::endsWith($nPath, "/")) {
                if ($eliminateRoot) {
                    $nPath = str_replace($zipStat['root'], '', $nPath);
                }
                $nPath = ltrim($nPath, "/");
                $zipMap[$nPath] = $zipStat;
            }
        }
        ksort($zipMap, SORT_NATURAL);
        //$this->debugResults($zipMap, 'zipMap');

        $fsoMap = [];
        foreach ($fsoStats as $fsoStat) {
            $nPath = $this->normalisePath($fsoStat['file'], false);
            $fsoMap[$nPath] = $fsoStat['crc32'];
        }
        ksort($fsoMap, SORT_NATURAL);
        //$this->debugResults($fsoMap, 'fsoMap');


        //--------------changed----------------------------
        foreach ($zipMap as $itemName => $itemStat) {
            if (isset($fsoMap[$itemName])) {
                if ($itemStat['crc'] !== $fsoMap[$itemName]) {
                    $report['fsoChanged'][] = $localFsoRootPath . $itemName;
                    $report['zipChanged'][] = $itemStat['name'];
                }
            }
        }

        //--------------zipExtra/fsoMissing----------------------------
        foreach ($zipMap as $itemName => $itemStat) {
            if (!isset($fsoMap[$itemName])) {
                $report['fsoMissing'][] = $localFsoRootPath . $itemName;
                $report['zipExtra'][] = $itemStat['name'];
            }
        }

        //--------------fsoExtra/zipMissing----------------------------
        foreach ($fsoMap as $itemName => $itemStat) {
            if (!isset($zipMap[$itemName])) {
                $report['zipMissing'][] = $zipRootFolder . $itemName;
                $report['fsoExtra'][] = $localFsoRootPath . $itemName;
            }
        }


        $this->debugResults($report, 'getZipFsoDifference');

        return $report;
    }

    /**
     * Get information about files from the FSO.
     * Can use caching to speed up sequential reads.
     *
     * @param string $baseDir base directory of files - will read all files if no $rawList supplied
     * @param null $rawList read only these files from the base directory
     * @param array $options type of file information to provide
     * @param bool $useCache
     * @return array
     */
    public function fileStats(string $baseDir, $rawList = null, array $options = [], bool $useCache = false): array
    {
        $cacheKey = sha1(json_encode([$baseDir, $rawList, $options]));
        if (isset($this->cache[$cacheKey]) && $useCache === true) {
            return $this->cache[$cacheKey];
        }

        $defaultOptions = [
            'directory' => true,
            'file' => true,
            'sha1' => true,
            'crc32' => true,
            'mime' => true,
            'size' => true
        ];
        $options = array_merge($defaultOptions, $options);

        if (empty($baseDir)) {
            return [];
        }

        if (!is_dir($baseDir)) {
            return [];
        }

        $baseDir = TextFormatter::makeDirectoryTrailingForwardSlash($baseDir);

        if (empty($rawList)) {
            $rawList = $this->rawFileList($baseDir);
        }

        $everyNFiles = 17;
        $totalCount = count($rawList);

        $stats = [];
        $counter = 0;
        foreach ($rawList as $relativeFile) {
            $fullPath = $baseDir . $relativeFile;
            $stats[$counter] = array_combine(array_keys($options), array_pad([], count($options), null));
            $stats[$counter]['directory'] = $baseDir;
            $stats[$counter]['file'] = $relativeFile;

            if ($options['sha1'] || $options['crc32'] || $options['mime']) {
                $contents = file_get_contents($fullPath);
            } else {
                $contents = false;
            }

            if ($options['sha1']) {
                $stats[$counter]['sha1'] = sha1($contents);
            }

            if ($options['crc32']) {
                $stats[$counter]['crc32'] = crc32($contents);
            }

            if ($options['mime']) {
                $detector = new  FinfoMimeTypeDetector();
                $stats[$counter]['mime'] = $detector->detectMimeTypeFromBuffer($contents);
            }

            if ($options['size']) {
                if ($contents) {
                    $stats[$counter]['size'] = strlen($contents);
                } else {
                    $stats[$counter]['size'] = filesize($fullPath);
                }
            }

            $counter++;
            if (($counter % $everyNFiles === 0) || $counter === $totalCount) {
                $message = $this->applyReplacements("Analysed {0} of {1} files in FSO.", [$counter, $totalCount]);
                $this->progressBar($counter, $totalCount, $message);
            }
        }

        $this->cache[$cacheKey] = $stats;
        return $stats;
    }

    /**
     * Get information about files inside a zip file.
     * Can use caching to speed up sequential reads.
     *
     * @param string $zipLocation
     * @param bool $useCache
     * @return array
     */
    public function zipStats(string $zipLocation, bool $useCache = false): array
    {
        $cacheKey = sha1_file($zipLocation);
        if (isset($this->cache[$cacheKey]) && $useCache === true) {
            return $this->cache[$cacheKey];
        }

        if (empty($zipLocation) || !is_file($zipLocation)) {
            return [];
        }

        $stats = [];
        $roots = [];
        $counter = 0;

        $za = new ZipArchive();
        $za->open($zipLocation);

        $everyNFiles = 17;
        $totalCount = $za->numFiles;

        for ($i = 0; $i < $za->numFiles; $i++) {
            $entry = $za->statIndex($i);
            $entry['name_normalised'] = $this->normalisePath($entry['name'], false);

            if (TextFormatter::endsWith($entry['name_normalised'], "/")) {
                //this is a directory or sub-directory so add the first part as the root folder
                $roots[] = explode("/", $entry['name_normalised'])[0];
                $entry['type'] = 'folder';
                $entry['path_info_file'] = '';
                $entry['path_info_folder'] = $entry['name_normalised'];
            } else {
                //could be a file in the root of zip so double check before adding first part as the root folder
                $entryParts = explode("/", $entry['name_normalised']);
                if (isset($entryParts[1])) {
                    //this is a folder/file path
                    $roots[] = $entryParts[0];
                    $entry['path_info_file'] = pathinfo($entry['name_normalised'], PATHINFO_BASENAME);
                    $entry['path_info_folder'] = pathinfo($entry['name_normalised'], PATHINFO_DIRNAME) . '/';
                } else {
                    //just a file in the top level of the zip
                    $roots[] = '';
                    $entry['path_info_file'] = pathinfo($entry['name_normalised'], PATHINFO_BASENAME);
                    $entry['path_info_folder'] = '';
                }
                $entry['type'] = 'file';
            }

            $stats[] = $entry;

            $counter++;
            if (($counter % $everyNFiles === 0) || $counter === $totalCount) {
                $message = $this->applyReplacements("Analysed {0} of {1} entries in Zip.", [$counter, $totalCount]);
                $this->progressBar($counter, $totalCount, $message);
            }
        }

        //determine if there really is a single root folder
        $roots = array_values(array_unique(array_filter($roots)));
        if (count($roots) === 1) {
            $rootFolder = $roots[0];
        } else {
            $rootFolder = '';
        }
        foreach ($stats as $k => $stat) {
            $stats[$k]['root'] = $rootFolder;
        }

        $this->cache[$cacheKey] = $stats;
        return $stats;
    }

    /**
     * Wrapper function with better error suppression.
     *
     * @param string $directory
     * @param int $permissions
     * @param bool $recursive
     * @param $context
     * @return bool
     */
    private function mkdir(string $directory, int $permissions = 0777, bool $recursive = false, $context = null)
    {
        set_error_handler(function () {
            //do nothing
        });
        $result = @mkdir($directory, $permissions, $recursive, $context);
        restore_error_handler();

        return $result;
    }

}
