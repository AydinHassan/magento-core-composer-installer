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
     * @var Exclude
     */
    protected $exclude;

    public function setUp()
    {
        $excludes = array(
            'file1.txt',
            'file2.txt',
            'folder1/file3.txt',
            'folder1/file2.txt',
        );
        $this->exclude = new Exclude($excludes);
    }

    /**
     * @param string $file
     * @param bool $expectedResult
     * @dataProvider filePathProvider
     */
    public function testFilesAreCorrectlyExcluded($file, $expectedResult)
    {
        $res = $this->exclude->exclude($file);
        $this->assertSame($res, $expectedResult);
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
        );
    }


    public function testSubDirectoriesOfExcludeAreAlsoExcluded()
    {
        $excludes = array(
            'folder1',
        );
        $exclude = new Exclude($excludes);

        $this->assertTrue($exclude->exclude('folder1/file1.txt'));
        $this->assertTrue($exclude->exclude('folder1/file2.txt'));
        $this->assertTrue($exclude->exclude('folder1/folder2/file3.txt'));
    }
}
