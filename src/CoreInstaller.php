<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

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
     * @var array
     */
    protected $excludes = array();

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    /**
     * @param array $excludes
     * @param GitIgnore $gitIgnore
     */
    public function __construct(array $excludes, GitIgnore $gitIgnore)
    {
        $this->excludes     = $excludes;
        $this->gitIgnore    = $gitIgnore;
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

            if ($item->isDir()) {
                if (!file_exists($destinationFile)) {
                    mkdir($destinationFile);
                }
                continue;
            }

            if ($this->exclude($filePath)) {
                continue;
            }

            copy($item, $destinationFile);
            $this->gitIgnore->addEntry($iterator->getSubPathName());
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
