<?php

namespace AydinHassan\MagentoCoreComposerInstallerTest;

use AydinHassan\MagentoCoreComposerInstaller\CoreManager;
use AydinHassan\MagentoCoreComposerInstaller\Options;
use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\InstallerEvent;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use Composer\Script\PackageEvent;
use Composer\Util\Filesystem;

/**
 * Class CoreManagerTest
 * @package AydinHassan\MagentoCoreComposerInstallerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $composer;
    protected $config;
    protected $io;
    protected $repoManager;
    protected $localRepository;
    protected $plugin;
    protected $tmpDir;

    public function setUp()
    {
        $this->config = new Config();
        $this->composer = new Composer();
        $this->composer->setConfig($this->config);

        $this->tmpDir = sprintf("%s/magento-core-composer-installer", realpath(sys_get_temp_dir()));


        $this->config->merge(array(
            'config' => array(
                'vendor-dir'    => $this->tmpDir . "/vendor",
            ),
        ));

        $this->io = $this->getMock('Composer\IO\IOInterface');
        $this->repoManager = new RepositoryManager($this->io, $this->config);

        $this->composer->setRepositoryManager($this->repoManager);
        $this->localRepository = new WritableArrayRepository();
        $this->repoManager->setLocalRepository($this->localRepository);
        $this->plugin = new CoreManager;
        $this->plugin->activate($this->composer, $this->io);
    }

    public function testGetSubscribedEvents()
    {
        $events = CoreManager::getSubscribedEvents();
        $expected = array (
            'post-dependencies-solving' => array(array('checkCoreDependencies', 0)),
            'post-package-install'      => array(array('installCore', 0)),
            'pre-package-update'        => array(array('uninstallCore', 0)),
            'post-package-update'       => array(array('installCore', 0)),
            'pre-package-uninstall'     => array(array('uninstallCore', 0))
        );

        $this->assertEquals($expected, $events);
    }

    public function testCheckCoreDependenciesThrowsExceptionIfMoreThan2CorePackagesRequired()
    {
        $this->localRepository->addPackage($this->createCorePackage());
        $this->localRepository->addPackage($this->createCorePackage('magento/core2-package'));

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array()
        );

        $this->setExpectedException('RuntimeException', 'Cannot use more than 1 core package');
        $this->plugin->checkCoreDependencies($installerEvent);
    }

    public function testCheckCoreDependenciesIsSuccessfulWith1CorePackage()
    {
        $this->localRepository->addPackage($this->createCorePackage());

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array()
        );

        $this->plugin->checkCoreDependencies($installerEvent);
    }

    public function testCheckCoreDependenciesThrowsExceptionWhenASecondCorePackageIsRequired()
    {
        $corePackage1   = $this->createCorePackage();
        $corePackage2   = $this->createCorePackage('magento/core2-package');
        $installOp      = new InstallOperation($corePackage2);
        $this->localRepository->addPackage($corePackage1);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));
        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array($installOp)
        );

        $this->setExpectedException('RuntimeException', 'Cannot use more than 1 core package');
        $this->plugin->checkCoreDependencies($installerEvent);
    }

    public function testCheckCoreDependenciesIsSuccesfulWhenRemoving1CorePackageAndAddingAnother()
    {
        $corePackage1   = $this->createCorePackage();
        $corePackage2   = $this->createCorePackage('magento/core2-package');
        $installOp      = new InstallOperation($corePackage2);
        $unInstallOp    = new UninstallOperation($corePackage1);
        $this->localRepository->addPackage($corePackage1);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));
        $installerEvent = new InstallerEvent(
            'solver-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array($installOp, $unInstallOp)
        );

        $this->plugin->checkCoreDependencies($installerEvent);
    }

    public function testInstallCoreFromInstallOperation()
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(array('magento-root-dir' => 'htdocs'));
        $this->composer->setPackage($rootPackage);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
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

    public function testInstallCoreFromInstallOperationCreateRootDirectoryIfItDoesNotExist()
    {

        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(array('magento-root-dir' => 'htdocs'));
        $this->composer->setPackage($rootPackage);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array(),
            new InstallOperation($corePackage)
        );

        $plugin = $this->getPluginWithMockedInstaller('install');

        $l  = '  - <comment>MagentoCoreInstaller: </comment>';
        $l .= '<info>Installing: "magento/core-package" version: "1.0.0" to: "htdocs"</info>';

        $this->io->expects($this->once())
            ->method('write')
            ->with($l);

        $this->assertFileNotExists('htdocs');

        $plugin->activate($this->composer, $this->io);
        $plugin->installCore($event);
        $this->assertFileExists('htdocs');
    }

    public function testInstallCoreFromUpdateOperation()
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(array('magento-root-dir' => 'htdocs'));
        $this->composer->setPackage($rootPackage);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array(),
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

    public function testUnInstallCoreFromUnInstallOperation()
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(array('magento-root-dir' => 'htdocs'));
        $this->composer->setPackage($rootPackage);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array(),
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

    public function testUnInstallCoreFromUpdateOperation()
    {
        $corePackage = $this->createCorePackage();

        $rootPackage = new RootPackage('some/project', '1.0.0', 'some/project');
        $rootPackage->setExtra(array('magento-root-dir' => 'htdocs'));
        $this->composer->setPackage($rootPackage);

        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')
            ->disableOriginalConstructor()
            ->getMock();


        $compositeRepo = new CompositeRepository(array($this->localRepository));

        $event = new PackageEvent(
            'install-event',
            $this->composer,
            $this->io,
            false,
            $this->getMock('Composer\DependencyResolver\PolicyInterface'),
            $pool,
            $compositeRepo,
            $request,
            array(),
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

    public function testGetInstallPath()
    {
        $package = $this->createCorePackage();
        $package->setTargetDir('target');

        $this->assertEquals(
            sprintf('%s/vendor/magento/core-package/target', $this->tmpDir),
            $this->plugin->getInstallPath($package)
        );
    }

    public function getOptions()
    {
        return new Options(array(
            'magento-root-dir' => $this->tmpDir,
        ));
    }

    public function createRootPackage()
    {
        $package = new RootPackage("root/package", "1.0.0", "root/package");
        return $package;
    }

    public function createCorePackage($name = 'magento/core-package')
    {
        $package = new Package($name, "1.0.0", $name);
        $package->setType('magento-core');
        return $package;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPluginWithMockedInstaller($installerMethod)
    {
        $installer = $this->getMockBuilder('AydinHassan\MagentoCoreComposerInstaller\CoreInstaller')
            ->disableOriginalConstructor()
            ->getMock();

        $installer->expects($this->once())
            ->method($installerMethod);

        $plugin = $this->getMockBuilder('AydinHassan\MagentoCoreComposerInstaller\CoreManager')
            ->setMethods(array('getInstaller'))
            ->getMock();

        $plugin->expects($this->once())
            ->method('getInstaller')
            ->with($this->isInstanceOf('AydinHassan\MagentoCoreComposerInstaller\Options'))
            ->will($this->returnValue($installer));

        return $plugin;
    }

    public function testGetInstaller()
    {
        $this->assertInstanceOf(
            'AydinHassan\MagentoCoreComposerInstaller\CoreInstaller',
            $this->plugin->getInstaller($this->getOptions())
        );
    }

    public function tearDown()
    {
        if (file_exists('htdocs')) {
            $fs = new Filesystem;
            $fs->remove('htdocs');
        }
    }
}
