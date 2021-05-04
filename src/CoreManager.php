<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\InstallerEvents;
use Composer\Installer\PackageEvents;
use Composer\Installer\PackageEvent;
use Composer\Util\Filesystem;
use Composer\Package\PackageInterface;
use Composer\Repository\PlatformRepository;
use Composer\Repository\CompositeRepository;

/**
 * Class CoreManager
 * @package AydinHassan\MagentoCoreComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CoreManager implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * Vendor Directory
     *
     * @var string
     */
    protected $vendorDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Output Prefix
     *
     * @var string
     */
    protected $ioPrefix = '  - <comment>MagentoCoreInstaller: </comment>';

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer     = $composer;
        $this->io           = $io;
        $this->vendorDir    = rtrim($composer->getConfig()->get('vendor-dir'), '/');
        $this->filesystem   = new Filesystem();
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        $targetDir = $package->getTargetDir();

        if ($targetDir) {
            return sprintf('%s/%s', $this->getPackageBasePath($package), $targetDir);
        }

        return $this->getPackageBasePath($package);
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    protected function getPackageBasePath(PackageInterface $package)
    {
        $this->filesystem->ensureDirectoryExists($this->vendorDir);
        $this->vendorDir = realpath($this->vendorDir);

        return ($this->vendorDir ? $this->vendorDir.'/' : '') . $package->getPrettyName();
    }

    /**
     * Tell event dispatcher what events we want to subscribe to
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            InstallerEvents::PRE_OPERATIONS_EXEC => array(
                array('checkCoreDependencies', 0)
            ),
            PackageEvents::POST_PACKAGE_INSTALL => array(
                array('installCore', 0)
            ),
            PackageEvents::PRE_PACKAGE_UPDATE => array(
                array('uninstallCore', 0)
            ),
            PackageEvents::POST_PACKAGE_UPDATE => array(
                array('installCore', 0)
            ),
            PackageEvents::PRE_PACKAGE_UNINSTALL => array(
                array('uninstallCore', 0)
            ),
        );
    }

    /**
     * Check that there is only 1 core package required
     */
    public function checkCoreDependencies(InstallerEvent $event)
    {
        $options = new Options($this->composer->getPackage()->getExtra());
        $installedCorePackages = array();

        $platformRepo = new PlatformRepository;
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        $installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
        $repositories = new CompositeRepository(
            array($installedRepo)
        );
        foreach ($repositories->getRepositories() as $repository) {
            foreach ($repository->getPackages() as $package) {
                if ($package->getType() === $options->getMagentoCorePackageType()) {
                    $installedCorePackages[$package->getName()] = $package;
                }
            }
        }

        $operations = array_filter($event->getTransaction()->getOperations(), function (OperationInterface $o) {
            return in_array($o->getOperationType(), array('install', 'uninstall'));
        });

        foreach ($operations as $operation) {
            $p = $operation->getPackage();
            if ($package && $package->getType() === $options->getMagentoCorePackageType()) {
                switch ($operation->getOperationType()) {
                    case "uninstall":
                        unset($installedCorePackages[$p->getName()]);
                        break;
                    case "install":
                        $installedCorePackages[$p->getName()] = $p;
                        break;
                }
            }
        }

        if (count($installedCorePackages) > 1) {
            throw new \RuntimeException("Cannot use more than 1 core package");
        }
    }


    /**
     * @param PackageEvent $event
     */
    public function installCore(PackageEvent $event)
    {
        switch ($event->getOperation()->getOperationType()) {
            case "install":
                $package = $event->getOperation()->getPackage();
                break;
            case "update":
                $package = $event->getOperation()->getTargetPackage();
                break;
        }

        $options = new Options($this->composer->getPackage()->getExtra());
        if ($package->getType() === $options->getMagentoCorePackageType()) {
            $this->ensureRootDirExists($options);

            $this->io->write(
                sprintf(
                    '%s<info>Installing: "%s" version: "%s" to: "%s"</info>',
                    $this->ioPrefix,
                    $package->getPrettyName(),
                    $package->getVersion(),
                    $options->getMagentoRootDir()
                )
            );

            $this->getInstaller($options, $package)
                ->install($this->getInstallPath($package), $options->getMagentoRootDir());
        }
    }

    /**
     * @param PackageEvent $event
     */
    public function uninstallCore(PackageEvent $event)
    {
        switch ($event->getOperation()->getOperationType()) {
            case "update":
                $package = $event->getOperation()->getInitialPackage();
                break;
            case "uninstall":
                $package = $event->getOperation()->getPackage();
                break;
        }

        $options = new Options($this->composer->getPackage()->getExtra());
        if ($package->getType() === $options->getMagentoCorePackageType()) {
            $this->io->write(
                sprintf(
                    '%s<info>Removing: "%s" version: "%s" from: "%s"</info>',
                    $this->ioPrefix,
                    $package->getPrettyName(),
                    $package->getVersion(),
                    $options->getMagentoRootDir()
                )
            );

            $this->getInstaller($options, $package)
                ->unInstall($this->getInstallPath($package), $options->getMagentoRootDir());
        }
    }

    /**
     * Create root directory if it doesn't exist already
     *
     * @param Options $options
     */
    private function ensureRootDirExists(Options $options)
    {
        if (!file_exists($options->getMagentoRootDir())) {
            mkdir($options->getMagentoRootDir(), 0755, true);
        }
    }

    /**
     * @param Options $options
     * @param PackageInterface $package
     * @return CoreInstaller
     */
    public function getInstaller(Options $options, PackageInterface $package)
    {
        $exclude = new Exclude($this->getInstallPath($package), $options->getDeployExcludes());

        $gitIgnore = new GitIgnore(
            sprintf("%s/.gitignore", $options->getMagentoRootDir()),
            $options->getIgnoreDirectories(),
            $options->appendToGitIgnore(),
            $options->gitIgnoreFunctionalityEnabled()
        );

        $installer = new CoreInstaller($exclude, $gitIgnore, $this->filesystem);
        return $installer;
    }
}
