<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreUnInstaller;
use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;

/**
 * Class CoreUnInstallerTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreUnInstallerTest extends \PHPUnit_Framework_TestCase
{
    protected $unInstaller;
    protected $gitIgnore;
    protected $root;

    public function setUp()
    {
        $this->gitIgnore = $this->getMockBuilder('AydinHassan\MagentoCoreComposerInstaller\GitIgnore')
            ->disableOriginalConstructor()
            ->setMethods(array('removeEntry', 'removeIgnoreDirectories', '__destruct'))
            ->getMock();

        $this->unInstaller = new CoreUnInstaller(array(), $this->gitIgnore, new Filesystem);
        $this->root        = vfsStream::setup('root', null, array('source' => array(), 'destination' => array()));
    }

    /**
     * Create a directory structure for us to work on
     */
    private function createDirStructure()
    {
        vfsStream::newFile('file1.txt')->at($this->root->getChild('source'));
        vfsStream::newFile('file1.txt')->at($this->root->getChild('destination'));
        vfsStream::newFile('file2.txt')->at($this->root->getChild('source'));
        vfsStream::newFile('file2.txt')->at($this->root->getChild('destination'));
        vfsStream::newDirectory('folder1')->at($this->root->getChild('source'));
        vfsStream::newDirectory('folder1')->at($this->root->getChild('destination'));
        vfsStream::newFile('file3.txt')->at($this->root->getChild('source/folder1'));
        vfsStream::newFile('file3.txt')->at($this->root->getChild('destination/folder1'));
    }

    public function testUninstallRemovesAllFilesWhichExistInSourceAndRemovesEntriesFromGitIgnore()
    {
        $this->createDirStructure();

        $this->gitIgnore
            ->expects($this->at(0))
            ->method('removeEntry')
            ->with('file1.txt');

        $this->gitIgnore
            ->expects($this->at(1))
            ->method('removeEntry')
            ->with('file2.txt');

        $this->gitIgnore
            ->expects($this->at(2))
            ->method('removeEntry')
            ->with('folder1/file3.txt');

        $this->gitIgnore
            ->expects($this->once())
            ->method('removeIgnoreDirectories');

        $this->unInstaller->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file3.txt'));
        $this->assertEmpty($this->gitIgnore->getEntries());
    }

    public function testUninstallKeepsFoldersWhichOnlyExistInDestination()
    {
        $this->createDirStructure();

        vfsStream::newFile('file4.txt')->at($this->root->getChild('destination/folder1'));

        $this->unInstaller->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertTrue($this->root->hasChild('destination/folder1/file4.txt'));
        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file3.txt'));
    }

    public function testFileIsSkippedIfItDoesNotExistInDestination()
    {
        $this->createDirStructure();
        vfsStream::newFile('file4')->at($this->root->getChild('source'));

        $this->unInstaller->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertFalse($this->root->hasChild('destination/file4.txt'));
        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file3.txt'));
    }

    public function testExcludedFilesAreNotRemovedFromDestination()
    {
        $this->createDirStructure();

        $this->unInstaller = new CoreUnInstaller(
            array('file1.txt', 'folder1/file3.txt'),
            $this->gitIgnore,
            new Filesystem
        );

        $this->unInstaller->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertTrue($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file3.txt'));
    }
}
