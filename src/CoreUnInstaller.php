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
     * @var array
     */
    protected $excludes = array();

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @param array $excludes
     * @param GitIgnore $gitIgnore
     * @param Filesystem $fileSystem
     */
    public function __construct(array $excludes, GitIgnore $gitIgnore, Filesystem $fileSystem)
    {
        $this->excludes     = $excludes;
        $this->gitIgnore    = $gitIgnore;
        $this->fileSystem   = $fileSystem;
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

            if ($this->exclude($iterator->getSubPathName())) {
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
            $this->gitIgnore->removeEntry($iterator->getSubPathName());
        }

        $this->gitIgnore->removeIgnoreDirectories();
    }

    /**
     * Should we exclude this file from the deploy?
     *
     * @param string $filePath
     * @return bool
     */
    public function exclude($filePath)
    {
        foreach ($this->excludes as $exclude) {
            if (substr($filePath, 0, strlen($exclude)) === $exclude) {
                return true;
            }
        }

        return false;
    }
}
