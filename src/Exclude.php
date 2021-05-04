<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

class Exclude
{
    public function __construct(private string $sourcePath, private array $excludes = [])
    {
    }

    public function exclude(string $filePath): bool
    {
        foreach ($this->excludes as $exclude) {
            if ($this->isExcludeDir($exclude)) {
                if (str_starts_with($filePath, $exclude)) {
                    return true;
                }
            } elseif ($exclude === $filePath) {
                return true;
            }
        }

        return false;
    }

    private function isExcludeDir(string $exclude): bool
    {
        return is_dir(
            sprintf('%s/%s', rtrim($this->sourcePath, '/'), ltrim('/', $exclude))
        );
    }
}
