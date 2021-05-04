<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\Exclude;
use PHPUnit\Framework\TestCase;

class ExcludeTest extends TestCase
{
    /**
     * @dataProvider filePathProvider
     */
    public function testFilesAreCorrectlyExcluded(string $file, bool $expectedResult): void
    {
        $exclude = new Exclude(
            '/src/path',
            [
                'file1.txt',
                'file2.txt',
                'folder1/file3.txt',
                'folder1/file2.txt',
            ]
        );

        $this->assertSame($exclude->exclude($file), $expectedResult);
    }

    public function filePathProvider(): array
    {
        return [
            ['file1.txt', true],
            ['file2.txt', true],
            ['folder1/file3.txt', true],
            ['folder1/file2.txt', true],
            ['folder1/file1.txt', false],
            ['folder1/file4.txt', false],
            ['file3.txt', false],
            ['file2.txt.bak', false],
        ];
    }


    public function testSubDirectoriesOfExcludeAreAlsoExcluded(): void
    {
        $sourcePath = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        @mkdir($sourcePath, 0775, true);
        @mkdir(sprintf('%s/%s', $sourcePath, 'folder1'));

        $exclude = new Exclude(
            $sourcePath,
            ['folder1']
        );

        $this->assertTrue($exclude->exclude('folder1/file1.txt'));
        $this->assertTrue($exclude->exclude('folder1/file2.txt'));
        $this->assertTrue($exclude->exclude('folder1/folder2/file3.txt'));

        rmdir(sprintf('%s/%s', $sourcePath, 'folder1'));
        rmdir($sourcePath);
    }

    public function testExcludeFileIsNotGreedy(): void
    {
        $exclude = new Exclude(
            '/src/path',
            ['file1.txt',]
        );

        $this->assertTrue($exclude->exclude('file1.txt'));
        $this->assertFalse($exclude->exclude('file1.txt.bak'));
    }
}
