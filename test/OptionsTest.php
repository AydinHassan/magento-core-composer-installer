<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new Options(['magento-root-dir' => '/']);

        $this->assertTrue($options->appendToGitIgnore());
        $this->assertSame("", $options->getMagentoRootDir());
        $this->assertSame([".git", 'composer.lock', 'composer.json'], $options->getDeployExcludes());
        $this->assertIsArray($options->getIgnoreDirectories());
    }

    public function testExceptionIsThrownIfMagentoRootDirIsNotSet(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('magento-root-dir must be specified in root package');
        new Options([]);
    }

    public function testExcludesMergesWithExistingExcludes(): void
    {
        $options = new Options([
            'magento-root-dir' => '/',
            'magento-core-deploy' => [
                'excludes' => [
                    'excludeme'
                ]
            ],
        ]);

        $this->assertSame(['.git', 'composer.lock', 'composer.json', 'excludeme'], $options->getDeployExcludes());
    }

    public function testExceptionIsThrownIfExcludesIsNotAnArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('excludes must be an array of files/directories to ignore');

        new Options([
            'magento-root-dir' => '/',
            'magento-core-deploy' => [
                'excludes' => new \stdClass(),
            ],
        ]);
    }

    public function testIgnoreDirsOverwritesExistingIgnoreDirs(): void
    {
        $options = new Options([
            'magento-root-dir' => '/',
            'magento-core-deploy' => [
                'ignore-directories' => [
                    'ignoreme'
                ]
            ],
        ]);

        $this->assertSame(['ignoreme'], $options->getIgnoreDirectories());
    }

    public function testExceptionIsThrownIfIgnoreDirsIsNotAnArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ignore-directories must be an array of files/directories');

        new Options([
            'magento-root-dir' => '/',
            'magento-core-deploy' => [
                'ignore-directories' => new \stdClass(),
            ],
        ]);
    }

    public function testGitIgnoreAppendFlag(): void
    {
        $options = new Options([
            'magento-root-dir' => '/',
            'magento-core-deploy' => [
                'git-ignore-append' => true,
            ],
        ]);

        $this->assertTrue($options->appendToGitIgnore());
    }
}
