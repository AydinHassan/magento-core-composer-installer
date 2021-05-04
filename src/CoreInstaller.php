<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

use Composer\Util\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class CoreInstaller
{
    public function __construct(private Exclude $exclude, private GitIgnore $gitIgnore, private Filesystem $fileSystem)
    {
    }

    public function install(string $source, string $destination): void
    {
        $iterator = $this->getIterator($source, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $destinationFile = sprintf("%s/%s", $destination, $iterator->getSubPathName());
            $filePath        = $iterator->getSubPathName();

            if ($this->exclude->exclude($filePath)) {
                continue;
            }

            if ($item->isDir()) {
                $fileExists = file_exists($destinationFile);
                if (!$fileExists && is_link($destinationFile)) {
                    throw new \RuntimeException(
                        sprintf(
                            'File: "%s" appears to be a broken symlink referencing: "%s"',
                            $destinationFile,
                            readlink($destinationFile)
                        )
                    );
                }

                if (!$fileExists) {
                    mkdir($destinationFile);
                }
                continue;
            }

            copy($item, $destinationFile);
            $this->gitIgnore->addEntry('/' . $iterator->getSubPathName());
        }
    }

    public function unInstall(string $source, string $destination): void
    {

        $iterator = $this->getIterator($source, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $destinationFile = sprintf("%s/%s", $destination, $iterator->getSubPathName());

            if ($this->exclude->exclude($iterator->getSubPathName())) {
                continue;
            }

            if (!file_exists($destinationFile)) {
                $this->gitIgnore->removeEntry($iterator->getSubPathName());
                continue;
            }

            if ($item->isDir()) {
                //check if there are not other files in this dir
                if ($this->fileSystem->isDirEmpty($destinationFile)) {
                    $this->fileSystem->removeDirectory($destinationFile);
                }
                continue;
            }

            $this->fileSystem->unlink($destinationFile);
            $this->gitIgnore->removeEntry('/' . $iterator->getSubPathName());
        }

        $this->gitIgnore->removeIgnoreDirectories();
    }

    public function getIterator(string $source, int $flags): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            $flags
        );
    }
}
