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
     * Source path of the files to be (maybe) excluded
     *
     * @var string
     */
    private $sourcePath;

    /**
     * @param string $sourcePath
     * @param array $excludes
     */
    public function __construct($sourcePath, array $excludes = array())
    {
        $this->excludes = $excludes;
        $this->sourcePath = $sourcePath;
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

            if ($this->isExcludeDir($exclude)) {
                if (substr($filePath, 0, strlen($exclude)) === $exclude) {
                    return true;
                }
            } elseif ($exclude === $filePath) {
                   return true;
            }
        }

        return false;
    }

    /**
     * @param string $exclude
     * @return bool
     */
    private function isExcludeDir($exclude)
    {
        return is_dir(
            sprintf('%s/%s', rtrim($this->sourcePath, '/'), ltrim('/', $exclude))
        );
    }
}
