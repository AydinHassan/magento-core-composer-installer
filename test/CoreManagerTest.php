<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreInstaller;
use AydinHassan\MagentoCoreComposerInstaller\CoreManager;
use AydinHassan\MagentoCoreComposerInstaller\Options;
use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Transaction;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\ArrayRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryManager;
use Composer\Repository\InstalledArrayRepository;
use Composer\Installer\PackageEvent;
use Composer\Util\Filesystem;
use Composer\Util\HttpDownloader;
use PHPUnit\Framework\MockObject\MockObject;

class CoreManagerTest extends \PHPUnit\Framework\TestCase
{
    private Composer $composer;
    private Config $config;
    private IOInterface $io;
    private RepositoryManager $repoManager;
    private InstalledArrayRepository $localRepository;
    private CoreManager $plugin;
    private string $tmpDir;
    private HttpDownloader $httpDownloader;

    public function setUp(): void
    {
        $this->config = new Config();
        $this->composer = new Composer();
        $this->composer->setConfig($this->config);

        $this->tmpDir = sprintf("%s/magento-core-composer-installer", realpath(sys_get_temp_dir()));


        $this->config->merge([
            'config' => [
                'vendor-dir' => $this->tmpDir . "/vendor",
            ],
        ]);

        $this->io = $this->createMock(IOInterface::class);
        $this->httpDownloader = new HttpDownloader($this->io, $this->config);
        $this->repoManager = new RepositoryManager($this->io, $this->config, $this->httpDownloader);

        $this->composer->setRepositoryManager($this->repoManager);
        $this->localRepository = new InstalledArrayRepository();
        $this->repoManager->setLocalRepository($this->localRepository);
        $this->plugin = new CoreManager();
        $this->plugin->activate($this->composer, $this->io);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CoreManager::getSubscribedEvents();
        $expected = [
            'pre-operations-exec' => [['checkCoreDependencies', 0]],
            'post-package-install' => [['installCore', 0]],
            'pre-package-update' => [['uninstallCore', 0]],
            'post-package-update' => [['installCore', 0]],
            'pre-package-uninstall' => [['uninstallCore', 0]]
        ];

        $this->assertEquals($expected, $events);
    }

    public function testCheckCoreDependenciesThrowsExceptionIfMoreThan2CorePackagesRequired(): void
    {
        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $this->localRepository->addPackage($this->createCorePackage());
        $this->localRepository->addPackage($this->createCorePackage('magento/core2-package'));

        $presentPackages = [];
        $package1 = new Package('magento/core-package', "1.0.0", 'magento/core-package');
        $package1->setType('magento-core');
        $package2 = new Package('magento/core2-package', "1.0.0", 'magento/core2-package');
        $package2->setType('magento-core');
        $resultPackages = [
            $package1,
            $package2
        ];

        $transaction = new Transaction($presentPackages, $resultPackages);

        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->createMock(PolicyInterface::class),
            $transaction
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Cannot use more than 1 core package');
        $this->plugin->checkCoreDependencies($installerEvent);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCheckCoreDependenciesIsSuccessfulWith1CorePackage(): void
    {
        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $this->localRepository->addPackage($this->createCorePackage());

        $presentPackages = [];
        $package1 = new Package('magento/core-package', "1.0.0", 'magento/core-package');
        $package1->setType('magento-core');
        $resultPackages = [
            $package1
        ];

        $transaction = new Transaction($presentPackages, $resultPackages);

        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->createMock('Composer\DependencyResolver\PolicyInterface'),
            $transaction
        );

        $this->plugin->checkCoreDependencies($installerEvent);
    }

    public function testCheckCoreDependenciesThrowsExceptionWhenASecondCorePackageIsRequired(): void
    {
        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $corePackage1 = $this->createCorePackage();
        $corePackage2 = $this->createCorePackage('magento/core2-package');
        $this->localRepository->addPackage($corePackage1);
        $this->localRepository->addPackage($corePackage2);
        $this->localRepository->addPackage($corePackage2);

        $presentPackages = [];
        $resultPackages = [
            $corePackage1,
            $corePackage2,
            $corePackage2
        ];

        $transaction = new Transaction($presentPackages, $resultPackages);

        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->createMock(PolicyInterface::class),
            $transaction
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Cannot use more than 1 core package');
        $this->plugin->checkCoreDependencies($installerEvent);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCheckCoreDependenciesIsSuccessfulWhenRemoving1CorePackageAndAddingAnother(): void
    {
        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $corePackage1 = $this->createCorePackage();
        $corePackage2 = $this->createCorePackage('magento/core2-package');
        $this->localRepository->addPackage($corePackage1);

        $presentPackages = [];
        $resultPackages = [
            $corePackage2
        ];

        $transaction = new Transaction($presentPackages, $resultPackages);

        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->createMock(PolicyInterface::class),
            $transaction
        );

        $this->plugin->checkCoreDependencies($installerEvent);
    }

    public function testInstallCoreFromInstallOperation(): void
    {
        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $corePackage = $this->createCorePackage();

        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $compositeRepo,
            array(),
            new InstallOperation($corePackage)
        );

        $plugin = $this->getPluginWithMockedInstaller('install');

        $l  = '  - <comment>MagentoCoreInstaller: </comment>';
        $l .= '<info>Installing: "magento/core-package" version: "1.0.0" to: "htdocs"</info>';

        $this->io->expects($this->once())
            ->method('write')
            ->with($l);

        $plugin->activate($this->composer, $this->io);
        $plugin->installCore($event);
    }

    public function testInstallCoreFromInstallOperationCreateRootDirectoryIfItDoesNotExist(): void
    {

        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $compositeRepo = new CompositeRepository([$this->localRepository]);

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $compositeRepo,
            [],
            new InstallOperation($corePackage)
        );

        $plugin = $this->getPluginWithMockedInstaller('install');

        $l  = '  - <comment>MagentoCoreInstaller: </comment>';
        $l .= '<info>Installing: "magento/core-package" version: "1.0.0" to: "htdocs"</info>';

        $this->io->expects($this->once())
            ->method('write')
            ->with($l);

        $this->assertFileDoesNotExist('htdocs');

        $plugin->activate($this->composer, $this->io);
        $plugin->installCore($event);
        $this->assertFileExists('htdocs');
    }

    public function testInstallCoreFromUpdateOperation(): void
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $compositeRepo = new CompositeRepository([$this->localRepository]);

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $compositeRepo,
            [],
            new UpdateOperation($this->createCorePackage('magento/initial'), $corePackage)
        );

        $plugin = $this->getPluginWithMockedInstaller('install');

        $l  = '  - <comment>MagentoCoreInstaller: </comment>';
        $l .= '<info>Installing: "magento/core-package" version: "1.0.0" to: "htdocs"</info>';

        $this->io->expects($this->once())
            ->method('write')
            ->with($l);

        $plugin->activate($this->composer, $this->io);
        $plugin->installCore($event);
    }

    public function testUnInstallCoreFromUnInstallOperation(): void
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $compositeRepo = new CompositeRepository([$this->localRepository]);

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $compositeRepo,
            [],
            new UninstallOperation($corePackage)
        );

        $plugin = $this->getPluginWithMockedInstaller('unInstall');

        $l  = '  - <comment>MagentoCoreInstaller: </comment>';
        $l .= '<info>Removing: "magento/core-package" version: "1.0.0" from: "htdocs"</info>';

        $this->io->expects($this->once())
            ->method('write')
            ->with($l);

        $plugin->activate($this->composer, $this->io);
        $plugin->uninstallCore($event);
    }

    public function testUnInstallCoreFromUpdateOperation(): void
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(['magento-root-dir' => 'htdocs']);
        $this->composer->setPackage($rootPackage);

        $compositeRepo = new CompositeRepository([$this->localRepository]);

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $compositeRepo,
            [],
            new UpdateOperation($corePackage, $this->createCorePackage('magento/target'))
        );

        $plugin = $this->getPluginWithMockedInstaller('unInstall');

        $l  = '  - <comment>MagentoCoreInstaller: </comment>';
        $l .= '<info>Removing: "magento/core-package" version: "1.0.0" from: "htdocs"</info>';

        $this->io->expects($this->once())
            ->method('write')
            ->with($l);

        $plugin->activate($this->composer, $this->io);
        $plugin->uninstallCore($event);
    }

    public function testGetInstallPath(): void
    {
        $package = $this->createCorePackage();
        $package->setTargetDir('target');

        $this->assertEquals(
            sprintf('%s/vendor/magento/core-package/target', $this->tmpDir),
            $this->plugin->getInstallPath($package)
        );
    }

    public function getOptions(): Options
    {
        return new Options([
            'magento-root-dir' => $this->tmpDir,
        ]);
    }

    public function createRootPackage(): RootPackage
    {
        return new RootPackage("root/package", "1.0.0", "root/package");
    }

    public function createCorePackage($name = 'magento/core-package'): Package
    {
        $package = new Package($name, "1.0.0", $name);
        $package->setType('magento-core');
        return $package;
    }

    private function getPluginWithMockedInstaller($installerMethod): MockObject
    {
        $installer = $this->createMock(CoreInstaller::class);

        $installer->expects($this->once())
            ->method($installerMethod);

        $plugin = $this->getMockBuilder(CoreManager::class)
            ->onlyMethods(['getInstaller'])
            ->getMock();

        $plugin->expects($this->once())
            ->method('getInstaller')
            ->with($this->isInstanceOf(Options::class))
            ->will($this->returnValue($installer));

        return $plugin;
    }

    public function testGetInstaller(): void
    {
        $this->assertInstanceOf(
            CoreInstaller::class,
            $this->plugin->getInstaller($this->getOptions(), new Package('some/package', "1.0.0", 'some/package'))
        );
    }

    public function tearDown(): void
    {
        if (file_exists('htdocs')) {
            $fs = new Filesystem();
            $fs->remove('htdocs');
        }
    }
}
