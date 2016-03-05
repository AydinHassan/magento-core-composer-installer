<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\Exclude;
use AydinHassan\MagentoCoreComposerInstaller\Options;

/**
 * Class ExcludeTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ExcludeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param string $file
     * @param bool $expectedResult
     * @dataProvider filePathProvider
     */
    public function testFilesAreCorrectlyExcluded($file, $expectedResult)
    {
        $exclude = new Exclude(
            '/src/path',
            array(
                'file1.txt',
                'file2.txt',
                'folder1/file3.txt',
                'folder1/file2.txt',
            )
        );

        $this->assertSame($exclude->exclude($file), $expectedResult);
    }

    /**
     * @return array
     */
    public function filePathProvider()
    {
        return array(
            array('file1.txt', true),
            array('file2.txt', true),
            array('folder1/file3.txt', true),
            array('folder1/file2.txt', true),
            array('folder1/file1.txt', false),
            array('folder1/file4.txt', false),
            array('file3.txt', false),
            array('file2.txt.bak', false),
        );
    }


    public function testSubDirectoriesOfExcludeAreAlsoExcluded()
    {
        $sourcePath = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        @mkdir($sourcePath, 0775, true);
        @mkdir(sprintf('%s/%s', $sourcePath, 'folder1'));

        $exclude = new Exclude(
            $sourcePath,
            array(
                'folder1',
            )
        );

        $this->assertTrue($exclude->exclude('folder1/file1.txt'));
        $this->assertTrue($exclude->exclude('folder1/file2.txt'));
        $this->assertTrue($exclude->exclude('folder1/folder2/file3.txt'));

        rmdir(sprintf('%s/%s', $sourcePath, 'folder1'));
        rmdir($sourcePath);
    }

    public function testExcludeFileIsNotGreedy()
    {
        $exclude = new Exclude(
            '/src/path',
            array(
                'file1.txt',
            )
        );

        $this->assertTrue($exclude->exclude('file1.txt'));
        $this->assertFalse($exclude->exclude('file1.txt.bak'));
    }
}
