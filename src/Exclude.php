<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

/**
 * Class Exclude
 * @package AydinHassan\MagentoCoreComposerInstaller
 */
class Exclude
{

    /**
     * @var array
     */
    private $excludes;

    /**
     * @param array $excludes
     */
    public function __construct(array $excludes = array())
    {
        $this->excludes = $excludes;
    }

    /**
     * Should we exclude this file from the install?
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
