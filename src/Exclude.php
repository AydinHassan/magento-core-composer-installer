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
            if ($this->isRegExp($exclude)) {
                if (preg_match($exclude, $filePath) !== 0) {
                    return true;
                }
            } elseif (substr($filePath, 0, strlen($exclude)) === $exclude) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test if the passed is string a valid regular expression
     *
     * @param string $string
     *
     * @return bool
     */
    private function isRegExp($string)
    {
        try {
            new \RegexIterator(new \ArrayIterator(array()), $string);

            return true;
        } catch(\InvalidArgumentException $e) {
            return false;
        }
    }
}
