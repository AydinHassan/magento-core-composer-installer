<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreInstaller;
use Composer\Util\Filesystem;

/**
 * Class CoreInstallerTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreInstallerTest extends \PHPUnit_Framework_TestCase
{
    protected $installer;
    protected $fileSystem;
    protected $gitIgnore;

    protected $sourceFolder;
    protected $destinationFolder;

    public function setUp()
    {
        $this->gitIgnore = $this->getMockBuilder('AydinHassan\MagentoCoreComposerInstaller\GitIgnore')
            ->disableOriginalConstructor()
            ->setMethods(array('addEntry', '__destruct'))
            ->getMock();

        $this->fileSystem           = new Filesystem();
        $this->installer            = new CoreInstaller(array(), $this->gitIgnore);
        $this->sourceFolder         = sprintf("%s/magento-core-composer-installer/source", sys_get_temp_dir());
        $this->destinationFolder    = sprintf("%s/magento-core-composer-installer/dest", sys_get_temp_dir());
        mkdir($this->sourceFolder, 0777, true);
        mkdir($this->destinationFolder, 0777, true);
    }

    public function testInstallerCopiesAllFiles()
    {
        //creating without gitignore object
        $this->installer = new CoreInstaller(array());

        $file1 = 'file1.txt';
        $file2 = 'folder1/file2.txt';
        $file3 = 'folder1/file3.txt';
        $file4 = 'folder1/folder2/file4.txt';

        mkdir(sprintf('%s/%s', $this->sourceFolder, dirname($file2)));
        touch(sprintf('%s/%s', $this->sourceFolder, $file1));
        touch(sprintf('%s/%s', $this->sourceFolder, $file2));
        touch(sprintf('%s/%s', $this->sourceFolder, $file3));
        mkdir(sprintf('%s/%s', $this->sourceFolder, dirname($file4)));
        touch(sprintf('%s/%s', $this->sourceFolder, $file4));

        $this->installer->install($this->sourceFolder, $this->destinationFolder);

        $this->assertFileExists(sprintf('%s/%s', $this->destinationFolder, $file1));
        $this->assertFileExists(sprintf('%s/%s', $this->destinationFolder, $file2));
        $this->assertFileExists(sprintf('%s/%s', $this->destinationFolder, $file3));
        $this->assertFileExists(sprintf('%s/%s', $this->destinationFolder, $file4));
    }

    public function testIfGitIgnorePresentOnlyFilesAreAppended()
    {
        $file1 = 'file1.txt';
        $file2 = 'folder1/file2.txt';
        $file3 = 'folder1/file3.txt';
        $file4 = 'folder1/folder2/file4.txt';

        mkdir(sprintf('%s/%s', $this->sourceFolder, dirname($file2)));
        touch(sprintf('%s/%s', $this->sourceFolder, $file1));
        touch(sprintf('%s/%s', $this->sourceFolder, $file2));
        touch(sprintf('%s/%s', $this->sourceFolder, $file3));
        mkdir(sprintf('%s/%s', $this->sourceFolder, dirname($file4)));
        touch(sprintf('%s/%s', $this->sourceFolder, $file4));

//        $this->gitIgnore
//            ->expects($this->at(0))
//            ->method('addEntry')
//            ->with('folder1/folder2/file4.txt');
//
//        $this->gitIgnore
//            ->expects($this->at(1))
//            ->method('addEntry')
//            ->with('folder1/file2.txt');
//
//        $this->gitIgnore
//            ->expects($this->at(2))
//            ->method('addEntry')
//            ->with('folder1/file3.txt');
//
//        $this->gitIgnore
//            ->expects($this->at(3))
//            ->method('addEntry')
//            ->with('file1.txt');

        $this->installer->install($this->sourceFolder, $this->destinationFolder);
    }

    public function testIfFolderExistsInDestinationItemIsSkipped()
    {
        $this->installer = new CoreInstaller(array());
        $file1 = 'folder1/file1.txt';

        mkdir(sprintf('%s/%s', $this->sourceFolder, dirname($file1)));
        touch(sprintf('%s/%s', $this->sourceFolder, $file1));
        mkdir(sprintf('%s/%s', $this->destinationFolder, dirname($file1)));


        $this->installer->install($this->sourceFolder, $this->destinationFolder);
        $this->assertFileExists(sprintf('%s/%s', $this->destinationFolder, $file1));
    }

    public function testExcludedFilesAreNotCopied()
    {
        $this->installer = new CoreInstaller(array('file1.txt', 'folder1/file2.txt'));

        $file1 = 'file1.txt';
        $file2 = 'folder1/file2.txt';
        $file3 = 'folder1/file3.txt';

        mkdir(sprintf('%s/%s', $this->sourceFolder, dirname($file2)));
        touch(sprintf('%s/%s', $this->sourceFolder, $file1));
        touch(sprintf('%s/%s', $this->sourceFolder, $file2));
        touch(sprintf('%s/%s', $this->sourceFolder, $file3));

        $this->installer->install($this->sourceFolder, $this->destinationFolder);
        $this->assertFileExists(sprintf('%s/%s', $this->destinationFolder, $file3));
        $this->assertFileNotExists(sprintf('%s/%s', $this->destinationFolder, $file2));
        $this->assertFileNotExists(sprintf('%s/%s', $this->destinationFolder, $file1));

    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->removeDirectory($this->sourceFolder);
        $fs->removeDirectory($this->destinationFolder);
    }
}
