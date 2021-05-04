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

class CoreManager implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;
    private string $vendorDir;
    protected Filesystem $filesystem;
    private string $ioPrefix = '  - <comment>MagentoCoreInstaller: </comment>';

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->vendorDir = rtrim($composer->getConfig()->get('vendor-dir'), '/');
        $this->filesystem = new Filesystem();
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function getInstallPath(PackageInterface $package): string
    {
        $targetDir = $package->getTargetDir();

        if ($targetDir) {
            return sprintf('%s/%s', $this->getPackageBasePath($package), $targetDir);
        }

        return $this->getPackageBasePath($package);
    }

    private function getPackageBasePath(PackageInterface $package): string
    {
        $this->filesystem->ensureDirectoryExists($this->vendorDir);
        $this->vendorDir = realpath($this->vendorDir);

        return ($this->vendorDir ? $this->vendorDir . '/' : '') . $package->getPrettyName();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InstallerEvents::PRE_OPERATIONS_EXEC => [
                ['checkCoreDependencies', 0]
            ],
            PackageEvents::POST_PACKAGE_INSTALL => [
                ['installCore', 0]
            ],
            PackageEvents::PRE_PACKAGE_UPDATE => [
                ['uninstallCore', 0]
            ],
            PackageEvents::POST_PACKAGE_UPDATE => [
                ['installCore', 0]
            ],
            PackageEvents::PRE_PACKAGE_UNINSTALL => [
                ['uninstallCore', 0]
            ],
        ];
    }

    /**
     * Check that there is only 1 core package required
     */
    public function checkCoreDependencies(InstallerEvent $event): void
    {
        $options = new Options($this->composer->getPackage()->getExtra());
        $installedCorePackages = [];

        $platformRepo = new PlatformRepository();
        $localRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        $installedRepo = new CompositeRepository([$localRepo, $platformRepo]);
        $repositories = new CompositeRepository(
            [$installedRepo]
        );
        foreach ($repositories->getRepositories() as $repository) {
            foreach ($repository->getPackages() as $package) {
                if ($package->getType() === $options->getMagentoCorePackageType()) {
                    $installedCorePackages[$package->getName()] = $package;
                }
            }
        }

        $operations = array_filter($event->getTransaction()->getOperations(), function (OperationInterface $o) {
            return in_array($o->getOperationType(), ['install', 'uninstall']);
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


    public function installCore(PackageEvent $event): void
    {
        $package = match ($event->getOperation()->getOperationType()) {
            "install" => $event->getOperation()->getPackage(),
            "update" => $event->getOperation()->getTargetPackage(),
        };

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

    public function uninstallCore(PackageEvent $event): void
    {
        $package = match ($event->getOperation()->getOperationType()) {
            "update" => $event->getOperation()->getInitialPackage(),
            "uninstall" => $event->getOperation()->getPackage(),
        };

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

    private function ensureRootDirExists(Options $options): void
    {
        if (!file_exists($options->getMagentoRootDir())) {
            mkdir($options->getMagentoRootDir(), 0755, true);
        }
    }

    public function getInstaller(Options $options, PackageInterface $package): CoreInstaller
    {
        $exclude = new Exclude($this->getInstallPath($package), $options->getDeployExcludes());

        $gitIgnore = new GitIgnore(
            sprintf("%s/.gitignore", $options->getMagentoRootDir()),
            $options->getIgnoreDirectories(),
            $options->appendToGitIgnore(),
            $options->gitIgnoreFunctionalityEnabled()
        );

        return new CoreInstaller($exclude, $gitIgnore, $this->filesystem);
    }
}
