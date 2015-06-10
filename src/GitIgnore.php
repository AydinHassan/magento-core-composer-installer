<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

/**
 * Class GitIgnore
 * @package AydinHassan\MagentoCoreComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnore
{

    /**
     * @var array
     */
    protected $lines = array();

    /**
     * @var array
     */
    protected $directoriesToIgnoreEntirely = array();

    /**
     * @var string|null
     */
    protected $gitIgnoreLocation;

    /**
     * @var bool
     */
    protected $hasChanges = false;

    /**
     * @param string $fileLocation
     * @param array $directoriesToIgnoreEntirely
     * @param bool $gitIgnoreAppend
     */
    public function __construct($fileLocation, array $directoriesToIgnoreEntirely, $gitIgnoreAppend = true)
    {
        $this->gitIgnoreLocation = $fileLocation;

        if (file_exists($fileLocation) && $gitIgnoreAppend) {
            $this->lines = explode("\n", file_get_contents($fileLocation));
        }

        $this->directoriesToIgnoreEntirely = $directoriesToIgnoreEntirely;

        $this->addEntriesForDirectoriesToIgnoreEntirely();
    }

    /**
     * @param string $file
     */
    public function addEntry($file)
    {
        $addToGitIgnore = true;
        foreach ($this->directoriesToIgnoreEntirely as $directory) {
            if (substr($file, 0, strlen($directory)) === $directory) {
                $addToGitIgnore = false;
            }
        }

        if ($addToGitIgnore && !in_array($file, $this->lines)) {
            $this->lines[] = $file;
        }

        $this->hasChanges = true;
    }

    /**
     * @param string $file
     */
    public function removeEntry($file)
    {
        $index = array_search($file, $this->lines);

        if ($index !== false) {
            unset($this->lines[$index]);
            $this->hasChanges = true;
        }
    }

    /**
     * Remove all the directories to ignore
     */
    public function removeIgnoreDirectories()
    {
        foreach ($this->directoriesToIgnoreEntirely as $directory) {
            $this->removeEntry($directory);
        }
    }

    /**
     * @return array
     */
    public function getEntries()
    {
        return array_values($this->lines);
    }

    /**
     * Wipe out the gitginore
     */
    public function wipeOut()
    {
        $this->lines = array();
        $this->hasChanges = true;
    }

    /**
     * Write the file
     */
    public function __destruct()
    {
        if ($this->hasChanges) {
            file_put_contents($this->gitIgnoreLocation, implode("\n", $this->lines));
        }
    }

    /**
     * Add entires to for all directories ignored entirely.
     */
    protected function addEntriesForDirectoriesToIgnoreEntirely()
    {
        foreach ($this->directoriesToIgnoreEntirely as $directory) {
            if (!in_array($directory, $this->lines)) {
                $this->lines[] = $directory;
                $this->hasChanges = true;
            }
        }
    }
}
