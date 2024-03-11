<?php

declare(strict_types=1);

namespace arajcany\ToolBox\Flysystem\Adapters;

use DirectoryIterator;
use FilesystemIterator;
use Generator;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\PathPrefixer;
use League\Flysystem\SymbolicLinkEncountered;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function is_dir;
use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

/**
 * The provided League/LocalFilesystemAdapter was having issues with CakePHP tmp directory files being locked.
 * This Class is used to trap [RuntimeException] errors on the LocalFilesystemAdapter::listContents() method.
 */
class LocalFilesystemAdapter extends \League\Flysystem\Local\LocalFilesystemAdapter
{
    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var int
     */
    private $linkHandling;

    /**
     * @var VisibilityConverter
     */
    private $visibility;

    /**
     * @var MimeTypeDetector
     */
    private $mimeTypeDetector;
    private int $writeFlags;

    public function __construct(
        string              $location,
        VisibilityConverter $visibility = null,
        int                 $writeFlags = LOCK_EX,
        int                 $linkHandling = self::DISALLOW_LINKS,
        MimeTypeDetector    $mimeTypeDetector = null
    )
    {
        parent::__construct($location, $visibility, $writeFlags, $linkHandling, $mimeTypeDetector);

        $this->prefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
    }


    public function listContents(string $path, bool $deep): iterable
    {
        $location = $this->prefixer->prefixPath($path);

        if (!is_dir($location)) {
            return;
        }

        /** @var SplFileInfo[] $iterator */
        $iterator = $deep ? $this->listDirectoryRecursively($location) : $this->listDirectory($location);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isLink()) {
                if ($this->linkHandling & self::SKIP_LINKS) {
                    continue;
                }
                throw SymbolicLinkEncountered::atLocation($fileInfo->getPathname());
            }

            $path = $this->prefixer->stripPrefix($fileInfo->getPathname());
            $isDirectory = $fileInfo->isDir();

            try {
                $lastModified = $fileInfo->getMTime();
                $permissions = octdec(substr(sprintf('%o', $fileInfo->getPerms()), -4));
                $visibility = $isDirectory ? $this->visibility->inverseForDirectory($permissions) : $this->visibility->inverseForFile($permissions);
                $fileSize = $fileInfo->getSize();
            } catch (\Throwable $exception) {
                $fileSize = null;
                $visibility = null;
                $lastModified = null;
            }

            yield $isDirectory ? new DirectoryAttributes(str_replace('\\', '/', $path), $visibility, $lastModified) : new FileAttributes(
                str_replace('\\', '/', $path),
                $fileSize,
                $visibility,
                $lastModified
            );
        }
    }

    private function listDirectoryRecursively(
        string $path,
        int $mode = RecursiveIteratorIterator::SELF_FIRST
    ): Generator {
        yield from new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    private function listDirectory(string $location): Generator
    {
        $iterator = new DirectoryIterator($location);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            yield $item;
        }
    }
}
