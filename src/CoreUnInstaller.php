<?php

namespace AydinHassan\MagentoCoreComposerInstaller;


use Composer\Util\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class CoreUnInstaller
 * @package AydinHassan\MagentoCoreComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreUnInstaller
{

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @param Filesystem $fileSystem
     * @param GitIgnore $gitIgnore
     */
    public function __construct(Filesystem $fileSystem, GitIgnore $gitIgnore)
    {
        $this->fileSystem   = $fileSystem;
        $this->gitIgnore    = $gitIgnore;
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function unInstall($source, $destination)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $destinationFile = sprintf("%s/%s", $destination, $iterator->getSubPathName());

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
            $this->gitIgnore->removeEntry($iterator->getSubPathName());
        }

        $this->gitIgnore->removeIgnoreDirectories();
    }
}
