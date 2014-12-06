<?php

namespace Wearejh\MagentoComposerInstaller;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class CoreInstaller
 * @package Wearejh\MagentoComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreInstaller
{

    /**
     * @var GitIgnore
     */
    protected $gitIgnore = true;

    /**
     * @var array
     */
    protected $excludes = array();

    /**
     * @param array $excludes
     * @param GitIgnore $gitIgnore
     */
    public function __construct(array $excludes, GitIgnore $gitIgnore = null)
    {
        $this->gitIgnore    = $gitIgnore;
        $this->excludes     = $excludes;
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function install($source, $destination)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {

            $destinationFile = sprintf("%s/%s", $destination, $iterator->getSubPathName());
            $filePath        = $iterator->getSubPathName();


            if ($this->exclude($filePath)) {
                continue;
            }

            if ($item->isDir()) {
                if (!file_exists($destinationFile)) {
                    mkdir($destinationFile);
                }
                continue;
            }

            copy($item, $destinationFile);

            if ($this->gitIgnore) {
                $this->gitIgnore->addEntry($iterator->getSubPathName());
            }
        }
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
