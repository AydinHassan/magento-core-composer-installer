<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreUnInstaller;
use Composer\Util\Filesystem;

/**
 * Class CoreUnInstallerTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreUnInstallerTest extends \PHPUnit_Framework_TestCase
{
    protected $unInstaller;
    protected $fileSystem;

    protected $sourceFolder;
    protected $destinationFolder;

    public function setUp()
    {
        $this->fileSystem           = new Filesystem();
        $this->unInstaller          = new CoreUnInstaller($this->fileSystem);
        $this->sourceFolder         = sprintf("%s/magento-core-composer-installer/source", sys_get_temp_dir());
        $this->destinationFolder    = sprintf("%s/magento-core-composer-installer/dest", sys_get_temp_dir());
        mkdir($this->sourceFolder, 0777, true);
        mkdir($this->destinationFolder, 0777, true);
    }

    public function testUninstallRemovesAllFilesWhichExistInSource()
    {
        $this->createFile('file1.txt');
        $this->createFile('file2.txt');
        $this->createFolder('folder1');
        $this->createFile('folder1/file3.txt');

        $this->unInstaller->uninstall($this->sourceFolder, $this->destinationFolder);
        $this->assertTrue($this->fileSystem->isDirEmpty($this->destinationFolder));
    }

    public function testUninstallKeepsFoldersWhichOnlyExistInDestination()
    {
        $this->createFile('file1.txt');
        $this->createFile('file2.txt');
        $this->createFolder('folder1');
        $this->createFile('folder1/file3.txt');
        touch(sprintf('%s/folder1/file4.txt', $this->destinationFolder));

        $this->unInstaller->uninstall($this->sourceFolder, $this->destinationFolder);

        $this->assertFileExists(sprintf('%s/folder1/file4.txt', $this->destinationFolder));
        $this->assertFileNotExists(sprintf('%s/folder1/file3.txt', $this->destinationFolder));
        $this->assertFileNotExists(sprintf('%s/file1.txt', $this->destinationFolder));
        $this->assertFileNotExists(sprintf('%s/file2.txt', $this->destinationFolder));
    }

    public function testFileIsSkippedIfItDoesNotExistInDestination()
    {
        $this->createFile('file1.txt');
        $this->createFile('file2.txt');
        $this->createFolder('folder1');
        $this->createFile('folder1/file3.txt');
        touch(sprintf('%s/folder1/file4.txt', $this->sourceFolder));

        $this->unInstaller->uninstall($this->sourceFolder, $this->destinationFolder);
        $this->assertTrue($this->fileSystem->isDirEmpty($this->destinationFolder));
    }

    protected function createFile($file)
    {
        touch(sprintf('%s/%s', $this->sourceFolder, $file));
        touch(sprintf('%s/%s', $this->destinationFolder, $file));
    }

    protected function createFolder($folder)
    {
        mkdir(sprintf('%s/%s', $this->sourceFolder, $folder));
        mkdir(sprintf('%s/%s', $this->destinationFolder, $folder));
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->removeDirectory($this->sourceFolder);
        $fs->removeDirectory($this->destinationFolder);
    }
}
