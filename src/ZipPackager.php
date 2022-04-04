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
     * Return a file listing for a given directory.
     *
     * Returns an array of file names - empty array on failure
     * The listing is pure and unfiltered.
     * File names include paths relative to the $localFsoRootPath
     *
     * @param string $localFsoRootPath
     * @return array
     */
    public function rawFileList(string $localFsoRootPath): array
    {
        /**
         * @var FileAttributes|DirectoryAttributes $content
         */

        $localFilesystem = new Filesystem(new LocalFilesystemAdapter($localFsoRootPath));

        $rawFileList = [];
        try {
            $contents = $localFilesystem->listContents('', true);
        } catch (\Throwable $exception) {
            return $rawFileList;
        }

        foreach ($contents as $content) {
            if ($content->isDir()) {
                continue;
            }

            $currentPath = $content->path();
            $rawFileList[] = $currentPath;
        }

        $this->out(__("Found {0} Files", count($rawFileList)));

        return $rawFileList;
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

        $dirs = ['/tests/',];

        foreach ($rawFileList as $listItem) {
            $listItemNormalised = $this->normalisePath($listItem);

            $keepListItemFlag = true;
            foreach ($dirs as $dir) {
                if (
                    strpos($listItemNormalised, '/vendor/') !== false &&
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


}