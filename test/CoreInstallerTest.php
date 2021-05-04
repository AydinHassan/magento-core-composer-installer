<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreInstaller;
use AydinHassan\MagentoCoreComposerInstaller\Exclude;
use AydinHassan\MagentoCoreComposerInstaller\GitIgnore;
use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class CoreInstallerTest extends TestCase
{
    private CoreInstaller $installer;
    private GitIgnore $gitIgnore;
    private vfsStreamDirectory $root;

    public function setUp(): void
    {
        $this->gitIgnore = $this->getMockBuilder(GitIgnore::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addEntry', 'removeEntry', 'removeIgnoreDirectories', '__destruct'])
            ->getMock();

        $this->installer = new CoreInstaller(new Exclude('/src/path'), $this->gitIgnore, new Filesystem());
        $this->root = vfsStream::setup('root', null, array('source' => array(), 'destination' => array()));
    }

    public function testInstallerCopiesAllFilesAndAppendsToGitIgnore(): void
    {
        $this->installer = new CoreInstaller(new Exclude('/src/path'), $this->gitIgnore, new Filesystem());

        $source = $this->root->getChild('source');
        vfsStream::newFile('file1.txt')->at($source);
        vfsStream::newDirectory('folder1')->at($source);
        vfsStream::newFile('file2.txt')->at($source->getChild('folder1'));
        vfsStream::newFile('file3.txt')->at($source->getChild('folder1'));
        vfsStream::newDirectory('folder2')->at($source->getChild('folder1'));
        vfsStream::newFile('file4.txt')->at($source->getChild('folder1/folder2'));


        $this->gitIgnore
            ->expects($this->exactly(4))
            ->method('addEntry')
            ->withConsecutive(
                ['/file1.txt'],
                ['/folder1/file2.txt'],
                ['/folder1/file3.txt'],
                ['/folder1/folder2/file4.txt']
            );


        $this->installer->install(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertTrue($this->root->hasChild('destination/file1.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file2.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file3.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/folder2/file4.txt'));
    }

    public function testIfFolderExistsInDestinationItemIsSkipped(): void
    {
        $this->installer = new CoreInstaller(new Exclude('/src/path'), $this->gitIgnore, new Filesystem());

        vfsStream::newDirectory('folder1')->at($this->root->getChild('source'));
        vfsStream::newDirectory('folder1')->at($this->root->getChild('destination'));
        vfsStream::newFile('file1.txt')->at($this->root->getChild('source/folder1'));

        $this->installer->install(vfsStream::url('root/source'), vfsStream::url('root/destination'));

        $this->assertTrue($this->root->hasChild('destination/folder1/file1.txt'));
    }

    public function testIfBrokenSymlinkExistsWhereDirShouldBeExceptionIsThrown(): void
    {
        $tempSourceDir      = sprintf('%s/%s/source', sys_get_temp_dir(), $this->getName());
        $tempDestinationDir = sprintf('%s/%s/destination', sys_get_temp_dir(), $this->getName());
        mkdir($tempSourceDir, 0777, true);
        mkdir($tempDestinationDir, 0777, true);

        mkdir(sprintf('%s/directory', $tempSourceDir));

        @symlink('nonexistentfolder', sprintf('%s/directory', $tempDestinationDir));

        try {
            $this->installer->install($tempSourceDir, $tempDestinationDir);
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertEquals(
                sprintf(
                    'File: "%s/directory" appears to be a broken symlink referencing: "nonexistentfolder"',
                    $tempDestinationDir
                ),
                $e->getMessage()
            );
        }

        rmdir(sprintf('%s/directory', $tempSourceDir));
        rmdir($tempSourceDir);

        unlink(sprintf('%s/directory', $tempDestinationDir));
        rmdir($tempDestinationDir);
    }

    public function testExcludedFilesAreNotCopied(): void
    {
        $exclude = new Exclude(('/src/path'), array('file1.txt', 'folder1/file2.txt'));
        $this->installer = new CoreInstaller($exclude, $this->gitIgnore, new Filesystem());

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

    /**
     * Create a directory structure for us to work on
     */
    private function createDirStructureForUnInstaller(): void
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

    public function testUninstallRemovesAllFilesWhichExistInSourceAndRemovesEntriesFromGitIgnore(): void
    {
        $this->createDirStructureForUnInstaller();

        $this->gitIgnore
            ->expects($this->exactly(3))
            ->method('removeEntry')
            ->withConsecutive(['/file1.txt'], ['/file2.txt'], ['/folder1/file3.txt']);

        $this->gitIgnore
            ->expects($this->once())
            ->method('removeIgnoreDirectories');
        $this->installer->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));
        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file3.txt'));
        $this->assertEmpty($this->gitIgnore->getEntries());
    }

    public function testUninstallKeepsFoldersWhichOnlyExistInDestination(): void
    {
        $this->createDirStructureForUnInstaller();
        vfsStream::newFile('file4.txt')->at($this->root->getChild('destination/folder1'));
        $this->installer->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file4.txt'));
        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file3.txt'));
    }

    public function testFileIsSkippedIfItDoesNotExistInDestination(): void
    {
        $this->createDirStructureForUnInstaller();
        vfsStream::newFile('file4')->at($this->root->getChild('source'));
        $this->installer->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));
        $this->assertFalse($this->root->hasChild('destination/file4.txt'));
        $this->assertFalse($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertFalse($this->root->hasChild('destination/folder1/file3.txt'));
    }

    public function testExcludedFilesAreNotRemovedFromDestination(): void
    {
        $this->createDirStructureForUnInstaller();
        $this->installer = new CoreInstaller(
            new Exclude('/src/path', array('file1.txt', 'folder1/file3.txt')),
            $this->gitIgnore,
            new Filesystem()
        );
        $this->installer->unInstall(vfsStream::url('root/source'), vfsStream::url('root/destination'));
        $this->assertTrue($this->root->hasChild('destination/file1.txt'));
        $this->assertFalse($this->root->hasChild('destination/file2.txt'));
        $this->assertTrue($this->root->hasChild('destination/folder1/file3.txt'));
    }
}
