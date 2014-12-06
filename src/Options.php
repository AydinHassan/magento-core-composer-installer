<?php

namespace Wearejh\MagentoComposerInstaller;

/**
 * Class Options
 * @package Wearejh\MagentoComposerInstaller
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Options
{
    /**
     * @var array Any path which start with any of these
     * entries should be ignored
     */
    protected $deployExcludes = array(
        ".git",
    );

    /**
     * @var array Directories to ignore
     * to reduce the size of the git ignore
     */
    protected $ignoreDirectories = array(
        'app/code/core/Mage',
        'app/code/core/Zend',
        'app/code/core/Enterprise',
        'lib/Zend',
        'lib/Varien',
        'lib/Magento',
        'lib/PEAR',
        'lib/Mage',
        'lib/phpseclib',
        'lib/flex',
        'lib/LinLibertineFont',
        'downloader',
        'js/extjs',
        'js/prototype',
        'js/calendar',
        'js/mage',
        'js/varien',
        'js/tiny_mce',
        'lib/Apache',
        'app/code/community/Phoenix/Moneybookers',

    );

    /**
     * Whether to append to the existing git ignore or remove it
     * and start fresh
     *
     * @var bool
     */
    protected $appendToGitIgnore = false;

    /**
     * Magento Root Directory
     *
     * @var string
     */
    protected $magentoRootDir;

    /**
     * @param array $packageExtra
     */
    public function __construct(array $packageExtra)
    {
        $coreInstallerOptions = array();
        if (isset($packageExtra['magento-core-deploy']) && is_array($packageExtra['magento-core-deploy'])) {
            $coreInstallerOptions = $packageExtra['magento-core-deploy'];
        }

        //merge excludes from root package composer.json file with default excludes
        if (isset($coreInstallerOptions['excludes'])) {
            $this->deployExcludes = array_merge($this->deployExcludes, $coreInstallerOptions['excludes']);
        }

        //overwrite default ignore directories if some are specified in root package composer.json
        if (isset($coreInstallerOptions['ignore-directories']) && is_array($packageExtra['ignore-directories'])) {
            $this->ignoreDirectories = $packageExtra['ignore-directories'];
        }

        if (isset($coreInstallerOptions['git-ignore-append'])) {
            $this->appendToGitIgnore = (bool) $coreInstallerOptions['git-ignore-append'];
        }

        if (!isset($packageExtra['magento-root-dir'])) {
            throw new \InvalidArgumentException("magento-root-dir must be specified in root package");
        }

        $this->magentoRootDir = rtrim($packageExtra['magento-root-dir'], "/");
    }

    /**
     * @return array
     */
    public function getDeployExcludes()
    {
        return $this->deployExcludes;
    }

    /**
     * @return array
     */
    public function getIgnoreDirectories()
    {
        return $this->ignoreDirectories;
    }

    /**
     * @return boolean
     */
    public function appendToGitIgnore()
    {
        return $this->appendToGitIgnore;
    }

    /**
     * @return string
     */
    public function getMagentoRootDir()
    {
        return $this->magentoRootDir;
    }
}
