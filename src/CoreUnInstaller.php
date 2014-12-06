<?php

namespace Wearejh\MagentoComposerInstaller;


use Composer\Util\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class CoreUnInstaller
 * @package Wearejh\MagentoComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreUnInstaller
{

    /**
     * @var Filesystem|null
     */
    protected $fileSystem = null;

    /**
     * @param Filesystem $fileSystem
     */
    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param string $source
     * @param $destination
     * @return string bool
     */
    public function uninstall($source, $destination)
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
        }

        return true;
    }
}
