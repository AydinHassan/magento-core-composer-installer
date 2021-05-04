<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\Options;

/**
 * Class OptionsTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class OptionsTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaults()
    {
        $options = new Options(array('magento-root-dir' => '/'));

        $this->assertTrue($options->appendToGitIgnore());
        $this->assertSame("", $options->getMagentoRootDir());
        $this->assertSame(array(".git", 'composer.lock', 'composer.json'), $options->getDeployExcludes());
        $this->assertIsArray($options->getIgnoreDirectories());
    }

    public function testExceptionIsThrownIfMagentoRootDirIsNotSet()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('magento-root-dir must be specified in root package');
        new Options(array());
    }

    public function testExcludesMergesWithExistingExcludes()
    {
        $options = new Options(array(
            'magento-root-dir'      => '/',
            'magento-core-deploy'   => array(
                'excludes' => array(
                    'excludeme'
                )
            ),
        ));

        $this->assertSame(array('.git', 'composer.lock', 'composer.json', 'excludeme'), $options->getDeployExcludes());
    }

    public function testExceptionIsThrownIfExcludesIsNotAnArray()
    {
        $this->expectException(
            'InvalidArgumentException'
        );
        $this->expectExceptionMessage('excludes must be an array of files/directories to ignore');

        new Options(array(
            'magento-root-dir'      => '/',
            'magento-core-deploy'   => array(
                'excludes' => new \stdClass,
            ),
        ));
    }

    public function testIgnoreDirsOverwritesExistingIgnoreDirs()
    {
        $options = new Options(array(
            'magento-root-dir'      => '/',
            'magento-core-deploy'   => array(
                'ignore-directories' => array(
                    'ignoreme'
                )
            ),
        ));

        $this->assertSame(array('ignoreme'), $options->getIgnoreDirectories());
    }

    public function testExceptionIsThrownIfIgnoreDirsIsNotAnArray()
    {
        $this->expectException(
            'InvalidArgumentException'
        );
        $this->expectExceptionMessage('ignore-directories must be an array of files/directories');

        new Options(array(
            'magento-root-dir'      => '/',
            'magento-core-deploy'   => array(
                'ignore-directories' => new \stdClass,
            ),
        ));
    }

    public function testGitIgnoreAppendFlag()
    {
        $options = new Options(array(
            'magento-root-dir'      => '/',
            'magento-core-deploy'   => array(
                'git-ignore-append' => true,
            ),
        ));

        $this->assertTrue($options->appendToGitIgnore());
    }
}
