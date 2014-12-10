<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreInstaller;
use org\bovigo\vfs\vfsStream;

/**
 * Class CoreInstallerTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreInstallerTest extends \PHPUnit_Framework_TestCase
{
    protected $installer;
    protected $gitIgnore;
    protected $root;

    public function setUp()
    {
        $this->gitIgnore = $this->getMockBuilder('AydinHassan\MagentoCoreComposerInstaller\GitIgnore')
            ->disableOriginalConstructor()
            ->setMethods(array('addEntry', '__destruct'))
            ->getMock();

        $this->installer = new CoreInstaller(array(), $this->gitIgnore);
        $this->root      = vfsStream::setup('root', null, array('source' => array(), 'destination' => array()));
    }

    public function testInstallerCopiesAllFilesAndAppendsToGitIgnore()
    {
        $this->installer = new CoreInstaller(array(), $this->gitIgnore);

        $source = $this->root->getChild('source');
        vfsStream::newFile('file1.txt')->at($source);
        vfsStream::newDirectory('folder1')->at($source);
        vfsStream::newFile('file2.txt')->at($source->getChild('folder1'));
        vfsStream::newFile('file3.txt')->at($source->getChild('folder1'));
        vfsStream::newDirectory('folder2')->at($source->getChild('folder1'));
        vfsStream::newFile('file4.txt')->at($source->getChild('folder1/folder2'));

        $this->gitIgnore
            ->expects($this->at(0))
            ->method('addEntry')
            ->with('file1.txt');

        $this->gitIgnore
            ->expects($this->at(1))
            ->method('addEntry')
            ->with('folder1/file2.txt');

        $this->gitIgnore
            ->expects($this->at(2))
            ->method('addEntry')
            ->with('folder1/file3.txt');

        $this->gitIgnore
            ->expects($this->at(3))
            ->method('addEntry')
            ->with('folder1/folder2/file4.txt');

        $this->installer->install(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertTrue($this->root->hasChild('destination/file1.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file2.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file3.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/folder2/file4.txt'));
    }

    public function testIfFolderExistsInDestinationItemIsSkipped()
    {
        $this->installer = new CoreInstaller(array(), $this->gitIgnore);

        vfsStream::newDirectory('folder1')->at($this->root->getChild('source'));
        vfsStream::newDirectory('folder1')->at($this->root->getChild('destination'));
        vfsStream::newFile('file1.txt')->at($this->root->getChild('source/folder1'));

        $this->installer->install(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertTrue($this->root->hasChild('destination/folder1/file1.txt'));
    }

    public function testExcludedFilesAreNotCopied()
    {
        $this->installer = new CoreInstaller(array('file1.txt', 'folder1/file2.txt'), $this->gitIgnore);

        $source = $this->root->getChild('source');
        vfsStream::newFile('file1.txt')->at($source);
        vfsStream::newDirectory('folder1')->at($source);
        vfsStream::newFile('file2.txt')->at($source->getChild('folder1'));
        vfsStream::newFile('file3.txt')->at($source->getChild('folder1'));
        vfsStream::newDirectory('folder2')->at($source->getChild('folder1'));
        vfsStream::newFile('file4.txt')->at($source->getChild('folder1/folder2'));

        $this->installer->install(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file2.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file3.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/folder2/file4.txt'));
    }
}
