<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

use Composer\Util\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class CoreInstaller
 * @package AydinHassan\MagentoCoreComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreInstaller
{

    /**
     * @var Exclude
     */
    protected $exclude;

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @param Exclude $exclude
     * @param GitIgnore $gitIgnore
     * @param Filesystem $fileSystem
     */
    public function __construct(Exclude $exclude, GitIgnore $gitIgnore, Filesystem $fileSystem)
    {
        $this->exclude      = $exclude;
        $this->gitIgnore    = $gitIgnore;
        $this->fileSystem   = $fileSystem;
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function install($source, $destination)
    {
        $iterator = $this->getIterator($source, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {

            $destinationFile = sprintf("%s/%s", $destination, $iterator->getSubPathName());
            $filePath        = $iterator->getSubPathName();

            if ($this->exclude->exclude($filePath)) {
                continue;
            }

            if ($item->isDir()) {
                if (!file_exists($destinationFile)) {
                    mkdir($destinationFile);
                }
                continue;
            }

            copy($item, $destinationFile);
            $this->gitIgnore->addEntry('/' . $iterator->getSubPathName());
        }
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function unInstall($source, $destination)
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

    /**
     * @param string $source
     * @param int $flags
     * @return RecursiveIteratorIterator
     */
    public function getIterator($source, $flags)
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
