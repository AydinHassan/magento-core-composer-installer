<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

class GitIgnore
{
    private array $lines = [];
    private array $directoriesToIgnoreEntirely = [];
    private string $gitIgnoreLocation;
    private bool $hasChanges = false;
    private bool $gitIgnoreEnabled;

    public function __construct(
        string $gitIgnoreLocation,
        array $directoriesToIgnoreEntirely,
        bool $gitIgnoreAppend = true,
        bool $gitIgnoreEnabled = true
    ) {
        $this->gitIgnoreLocation = $gitIgnoreLocation;

        $this->gitIgnoreEnabled = $gitIgnoreEnabled;

        if (file_exists($gitIgnoreLocation) && $gitIgnoreAppend) {
            $this->lines = explode("\n", file_get_contents($gitIgnoreLocation));
        }

        $this->directoriesToIgnoreEntirely = $directoriesToIgnoreEntirely;

        $this->addEntriesForDirectoriesToIgnoreEntirely();
    }

    public function addEntry(string $file): void
    {
        $addToGitIgnore = true;
        foreach ($this->directoriesToIgnoreEntirely as $directory) {
            if (str_starts_with($file, $directory)) {
                $addToGitIgnore = false;
            }
        }

        if ($addToGitIgnore && !in_array($file, $this->lines)) {
            $this->lines[] = $file;
        }

        $this->hasChanges = true;
    }

    public function removeEntry(string $file): void
    {
        $index = array_search($file, $this->lines);

        if ($index !== false) {
            unset($this->lines[$index]);
            $this->hasChanges = true;
        }
    }

    public function removeIgnoreDirectories(): void
    {
        foreach ($this->directoriesToIgnoreEntirely as $directory) {
            $this->removeEntry($directory);
        }
    }

    public function getEntries(): array
    {
        return array_values($this->lines);
    }

    public function wipeOut(): void
    {
        $this->lines = [];
        $this->hasChanges = true;
    }

    /**
     * Write the file
     */
    public function __destruct()
    {
        if ($this->gitIgnoreEnabled && $this->hasChanges) {
            file_put_contents($this->gitIgnoreLocation, implode("\n", $this->lines));
        }
    }

    /**
     * Add entries to for all directories ignored entirely.
     */
    private function addEntriesForDirectoriesToIgnoreEntirely(): void
    {
        foreach ($this->directoriesToIgnoreEntirely as $directory) {
            if (!in_array($directory, $this->lines)) {
                $this->lines[] = $directory;
                $this->hasChanges = true;
            }
        }
    }
}
