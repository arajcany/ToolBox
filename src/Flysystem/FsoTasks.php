<?php

namespace arajcany\ToolBox\Flysystem;

use arajcany\ToolBox\Flysystem\Adapters\LocalFilesystemAdapter;
use arajcany\ToolBox\Utility\Feedback\ReturnAlerts;
use League\Flysystem\Filesystem;

/**
 * Perform FSO operations using the Flysystem library.
 * This class handles issues with PHP native functions when doing things between UNC and local paths.
 */
class FsoTasks
{
    use ReturnAlerts;

    /**
     * Move a file to a new location.
     *  - Is a 3-step operation (copy, validate the copy, delete the original).
     *  - PHP rename() function can't handle a rename between UNC and local paths.
     *
     * @param string $sourceFile
     * @param string $destinationFile
     * @param bool $deleteSourceFile
     * @return bool
     */
    private function moveFile(string $sourceFile, string $destinationFile, bool $deleteSourceFile = true): bool
    {
        // Initialize Flysystem Adapters
        $sourceAdapter = new LocalFilesystemAdapter(dirname($sourceFile));
        $destinationAdapter = new LocalFilesystemAdapter(dirname($destinationFile));

        $sourceFs = new Filesystem($sourceAdapter);
        $destinationFs = new Filesystem($destinationAdapter);

        // Extract filenames from paths
        $sourceFileName = basename($sourceFile);
        $destinationFileName = basename($destinationFile);

        // Check if source file exists
        try {
            if (!$sourceFs->fileExists($sourceFileName)) {
                $this->addDangerAlerts('Source file does not exist.');
                return false;
            }
        } catch (\Throwable $exception) {
            return false;
        }

        try {
            // Open stream from source file
            $stream = $sourceFs->readStream($sourceFileName);
            if (!$stream) {
                return false;
            }

            // Write stream to destination
            $destinationFs->writeStream($destinationFileName, $stream);
            fclose($stream);

            // Verify destination file exists
            if (!$destinationFs->fileExists($destinationFileName)) {
                return false;
            }

            // Delete the original file if $deleteSourceFile is true
            if ($deleteSourceFile) {
                $sourceFs->delete($sourceFileName);

                // Verify source file is deleted
                if ($sourceFs->fileExists($sourceFileName)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }


    /**
     * Move a directory to a new location.
     * - Is a 3-step operation (copy, validate the copy, delete the original).
     * - PHP rename() function can't handle a rename between UNC and local paths.
     * - PHP unlink() cannot handle non-empty directories.
     *
     * @param string $sourceDir
     * @param string $destinationDir
     * @param bool $deleteSourceDir
     * @return bool
     */
    private function moveDirectory(string $sourceDir, string $destinationDir, bool $deleteSourceDir = true): bool
    {
        $sourceFs = new Filesystem(new LocalFilesystemAdapter($sourceDir));
        $destinationFs = new Filesystem(new LocalFilesystemAdapter($destinationDir));

        try {
            $contents = $sourceFs->listContents('', true);
        } catch (\Throwable $exception) {
            return false;
        }

        $failCounter = 0;

        foreach ($contents as $item) {
            $relativePath = $item->path(); // Path relative to the source directory
            $destinationPath = ltrim($relativePath, '/'); // Ensure correct destination path

            try {
                if ($item->isDir()) {
                    $destinationFs->createDirectory($destinationPath);

                    // Check if directory exists
                    if (!$destinationFs->directoryExists($destinationPath)) {
                        $failCounter++;
                    }
                } elseif ($item->isFile()) {
                    $stream = $sourceFs->readStream($relativePath);
                    $destinationFs->writeStream($destinationPath, $stream);
                    fclose($stream);

                    // Check if file exists
                    if (!$destinationFs->fileExists($destinationPath)) {
                        $failCounter++;
                    }
                }
            } catch (\Throwable $exception) {
                $failCounter++;
            }
        }

        if ($deleteSourceDir) {
            $deleteResult = $this->deleteDirectory($sourceDir);
            if (!$deleteResult) {
                $failCounter++;
            }
        }

        return $failCounter === 0;
    }


    /**
     * Recursively delete a directory.
     *  - PHP unlink() cannot handle non-empty directories.
     *
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $adapter = new LocalFilesystemAdapter($dir);
        $tmpFileSystem = new Filesystem($adapter);
        $path = '';
        try {
            $tmpFileSystem->deleteDirectory($path);
            if (is_dir($dir)) {
                return false;
            } else {
                return true;
            }
        } catch (\Throwable $exception) {
            return false;
        }
    }
}