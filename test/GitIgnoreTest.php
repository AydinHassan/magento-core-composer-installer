<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\GitIgnore;
use org\bovigo\vfs\vfsStream;

/**
 * Class GitIgnoreTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnoreTest extends \PHPUnit_Framework_TestCase
{
    protected $gitIgnoreFile;

    public function setUp()
    {
        vfsStream::setup('root');
        $this->gitIgnoreFile = vfsStream::url('root/.gitignore');
    }

    public function testIfFileNotExistsItIsCreated()
    {
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, array(), array(), array());
        $gitIgnore->addEntry("file1");
        unset($gitIgnore);

        $this->assertFileExists($this->gitIgnoreFile);
    }

    public function testIfFileExistsExistingLinesAreLoaded()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));

        $gitIgnore = new GitIgnore($this->gitIgnoreFile, array(), array(), true);

        $this->assertFileExists($this->gitIgnoreFile);
        $this->assertSame($lines, $gitIgnore->getEntries());
    }

    public function testWipeOutRemovesAllEntries()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));

        $gitIgnore = new GitIgnore($this->gitIgnoreFile, array(), array(), true);
        $gitIgnore->wipeOut();

        $this->assertFileExists($this->gitIgnoreFile);
        $this->assertSame(array(), $gitIgnore->getEntries());
        unset($gitIgnore);
        $this->assertEquals("", file_get_contents($this->gitIgnoreFile));
    }

    public function testIgnoreDirectoriesAreAddedToGitIgnore()
    {
        $folders = array('folder1', 'folder2');
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, $folders, array(), true);
        $gitIgnore->addEntry('folder1/file1.txt');
        $this->assertSame($folders, $gitIgnore->getEntries());
        unset($gitIgnore);
        $this->assertEquals("folder1\nfolder2", file_get_contents($this->gitIgnoreFile));
    }

    public function testIgnoreFilesAreAddedToGitIgnore()
    {
        $folders = array('file1.txt', 'file2.txt');
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, $folders, array(), true);
        $gitIgnore->addEntry('file1.txt');
        $this->assertSame($folders, $gitIgnore->getEntries());
        unset($gitIgnore);
        $this->assertEquals("file1.txt\nfile2.txt", file_get_contents($this->gitIgnoreFile));
    }

    public function testAddEntryDoesNotAddDuplicates()
    {
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, array(), array(), true);
        $gitIgnore->addEntry("file1.txt");
        $gitIgnore->addEntry("file1.txt");
        $this->assertCount(1, $gitIgnore->getEntries());
    }

    public function testAddEntryDoesNotAddFileOrDirectoryIfItIsInsideAnIgnoredDirectory()
    {
        $ignoreDirs = array("dir1", "dir2/lol/");
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, $ignoreDirs, array());
        $gitIgnore->addEntry("dir1/file1.txt");
        $gitIgnore->addEntry("dir2/lol/file2.txt");
        $gitIgnore->addEntry("dir2/file3.txt");

        $expected = array(
            'dir1',
            'dir2/lol/',
            'dir2/file3.txt',
        );

        $this->assertEquals($expected, $gitIgnore->getEntries());
    }

    public function testIgnoreDirectoriesAreNotWrittenIfNoEntriesAreAdded()
    {
        $folders = array('folder1', 'folder2');
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, $folders, array(), true);
        $this->assertSame($folders, $gitIgnore->getEntries());
        unset($gitIgnore);
        $this->assertFileNotExists($this->gitIgnoreFile);
    }

    public function testGitIgnoreIsNotWrittenIfNoAdditions()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));
        $writeTime = filemtime($this->gitIgnoreFile);

        $folders = array('folder1', 'folder2');
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, $folders, array(), true);
        unset($gitIgnore);

        clearstatcache();
        $this->assertEquals($writeTime, filemtime($this->gitIgnoreFile));
    }

    public function testCanRemoveEntry()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->gitIgnoreFile, implode("\n", $lines));

        $gitIgnore = new GitIgnore($this->gitIgnoreFile, array(), array(), true);
        $gitIgnore->removeEntry('line1');

        $this->assertEquals(array('line2'), $gitIgnore->getEntries());
    }

    public function testRemoveIgnoreDirectoriesSuccessfullyRemovesEntries()
    {
        $gitIgnore = new GitIgnore($this->gitIgnoreFile, array('line1', 'line2'), array());
        $this->assertEquals(array('line1', 'line2'), $gitIgnore->getEntries());
        $gitIgnore->removeIgnoreDirectories();
        $this->assertEquals(array(), $gitIgnore->getEntries());
    }
}
