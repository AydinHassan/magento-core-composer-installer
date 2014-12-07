<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\GitIgnore;

/**
 * Class GitIgnoreTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnoreTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpGitIgnore;

    public function setUp()
    {
        $this->tmpGitIgnore = sprintf("%s/%s.gitignore", sys_get_temp_dir(), get_class($this));
    }

    public function testIfFileNotExistsItIsCreated()
    {
        new GitIgnore($this->tmpGitIgnore, array());

        $this->assertFileExists($this->tmpGitIgnore);
    }

    public function testIfFileExistsExistingLinesAreLoaded()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->tmpGitIgnore, implode("\n", $lines));

        $gitIgnore = new GitIgnore($this->tmpGitIgnore, array(), true);

        $this->assertFileExists($this->tmpGitIgnore);
        $this->assertSame($lines, $gitIgnore->getEntries());
    }

    public function testWipeOutRemovesAllEntries()
    {
        $lines = array('line1', 'line2');
        file_put_contents($this->tmpGitIgnore, implode("\n", $lines));

        $gitIgnore = new GitIgnore($this->tmpGitIgnore, array(), true);
        $gitIgnore->wipeOut();

        $this->assertFileExists($this->tmpGitIgnore);
        $this->assertSame(array(), $gitIgnore->getEntries());
        unset($gitIgnore);
        $this->assertEquals("", file_get_contents($this->tmpGitIgnore));
    }

    public function testIgnoreDirectoriesAreAddedToGitIgnore()
    {
        $folders = array('folder1', 'folder2');
        $gitIgnore = new GitIgnore($this->tmpGitIgnore, $folders, true);
        $this->assertFileExists($this->tmpGitIgnore);
        $this->assertSame($folders, $gitIgnore->getEntries());
        unset($gitIgnore);
        $this->assertEquals("folder1\nfolder2", file_get_contents($this->tmpGitIgnore));
    }

    public function testAddEntryDoesNotAddDuplicates()
    {
        $gitIgnore = new GitIgnore($this->tmpGitIgnore, array(), true);
        $gitIgnore->addEntry("file1.txt");
        $gitIgnore->addEntry("file1.txt");
        $this->assertCount(1, $gitIgnore->getEntries());
    }

    public function testAddEntryDoesNotAddFileOrDirectoryIfItIsInsideAnIgnoredDirectory()
    {
        $ignoreDirs = array("dir1", "dir2/lol/");
        $gitIgnore = new GitIgnore($this->tmpGitIgnore, $ignoreDirs);
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

    public function tearDown()
    {
        unlink($this->tmpGitIgnore);
    }
}
