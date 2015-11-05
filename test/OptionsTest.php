<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\Options;

/**
 * Class OptionsTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $options = new Options(array('magento-root-dir' => '/'));

        $this->assertTrue($options->appendToGitIgnore());
        $this->assertSame("", $options->getMagentoRootDir());
        $this->assertSame(array(".git", 'composer.lock', 'composer.json'), $options->getDeployExcludes());
        $this->assertInternalType('array', $options->getIgnoreDirectories());
    }

    public function testExceptionIsThrownIfMagentoRootDirIsNotSet()
    {
        $this->setExpectedException('InvalidArgumentException', 'magento-root-dir must be specified in root package');
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
        $this->setExpectedException(
            'InvalidArgumentException',
            'excludes must be an array of files/directories to ignore'
        );

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
        $this->setExpectedException(
            'InvalidArgumentException',
            'ignore-directories must be an array of files/directories'
        );

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
