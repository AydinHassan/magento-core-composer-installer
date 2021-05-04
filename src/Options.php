<?php

namespace AydinHassan\MagentoCoreComposerInstaller;

/**
 * Class Options
 * @package AydinHassan\MagentoCoreComposerInstaller
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
        "composer.lock",
        "composer.json",
    );

    /**
     * @var array Directories to ignore
     * to reduce the size of the git ignore
     */
    protected $ignoreDirectories = array(
        '/app/code/core/Mage',
        '/app/code/core/Zend',
        '/app/code/core/Enterprise',
        '/dev',
        '/lib/Zend',
        '/lib/Varien',
        '/lib/Magento',
        '/lib/PEAR',
        '/lib/Mage',
        '/lib/phpseclib',
        '/lib/flex',
        '/lib/LinLibertineFont',
        '/downloader',
        '/js/extjs',
        '/js/prototype',
        '/js/calendar',
        '/js/mage',
        '/js/varien',
        '/js/tiny_mce',
        '/lib/Apache',
        '/app/code/community/Phoenix/Moneybookers',
        '/app/design/adminhtml/default/default/template/bundle',
        '/app/design/adminhtml/default/default/template/catalog',
        '/app/design/adminhtml/default/default/template/customer',
        '/app/design/adminhtml/default/default/template/downloadable',
        '/app/design/adminhtml/default/default/template/newsletter',
        '/app/design/adminhtml/default/default/template/payment',
        '/app/design/adminhtml/default/default/template/sales',
        '/app/design/adminhtml/default/default/template/system',
        '/app/design/adminhtml/default/default/template/widget',
        '/app/design/adminhtml/default/default/template/xmlconnect',
        '/app/design/frontend/base/default/template/bundle',
        '/app/design/frontend/base/default/template/catalog',
        '/app/design/frontend/base/default/template/checkout',
        '/app/design/frontend/base/default/template/customer',
        '/app/design/frontend/base/default/template/downloadable',
        '/app/design/frontend/base/default/template/page',
        '/app/design/frontend/base/default/template/payment',
        '/app/design/frontend/base/default/template/paypal',
        '/app/design/frontend/base/default/template/reports',
        '/app/design/frontend/base/default/template/sales',
        '/app/design/frontend/base/default/template/wishlist',
        '/app/design/frontend/default/iphone/template/catalog',
        '/app/design/frontend/default/iphone/template/checkout',
        '/app/design/frontend/default/iphone/template/page',
        '/app/design/frontend/default/iphone/template/sales',
        '/app/design/frontend/default/iphone/template/wishlist',
        '/app/design/frontend/rwd/default/template/bundle',
        '/app/design/frontend/rwd/default/template/catalog',
        '/app/design/frontend/rwd/default/template/checkout',
        '/app/design/frontend/rwd/default/template/customer',
        '/app/design/frontend/rwd/default/template/downloadable',
        '/app/design/frontend/base/default/template/cataloginventory',
        '/app/design/frontend/base/default/template/catalogsearch',
        '/app/design/frontend/base/default/template/pagecache',
        '/app/design/frontend/default/iphone/template/catalogsearch',
        '/app/design/frontend/rwd/default/template/cataloginventory',
        '/app/design/frontend/rwd/default/template/catalogsearch',
        '/app/design/frontend/rwd/default/template/configurableswatches',
        '/app/design/frontend/rwd/default/template/email',
        '/app/design/frontend/rwd/default/template/page',
        '/app/design/frontend/rwd/default/template/paypal',
        '/app/design/frontend/rwd/default/template/persistent',
        '/app/design/frontend/rwd/default/template/reports',
        '/app/design/frontend/rwd/default/template/sales',
        '/app/design/frontend/rwd/default/template/wishlist',
        '/app/design/install/default/default/template/install',
        '/skin/adminhtml/default/default/images/xmlconnect',
        '/skin/frontend/base/default/images/moneybookers',
        '/skin/frontend/rwd/default/scss',
        '/app/design/adminhtml/default/default/template/paypal',
        '/app/design/adminhtml/default/default/template/permissions',
        '/app/design/adminhtml/default/default/template/report',
        '/app/design/adminhtml/default/default/template/tax',
        '/app/design/frontend/base/default/template/cms',
        '/app/design/frontend/base/default/template/email',
        '/app/design/frontend/base/default/template/moneybookers',
        '/app/design/frontend/base/default/template/oauth',
        '/app/design/frontend/base/default/template/review',
        '/app/design/frontend/default/modern/template/catalog',
        '/skin/adminhtml/default/default/xmlconnect',
        '/skin/frontend/base/default/images/cookies',
        '/skin/frontend/base/default/images/xmlconnect',
        '/app/design/frontend/default/modern/template/catalogsearch'
    );

    /**
     * Whether to append to the existing git ignore or remove it
     * and start fresh
     *
     * @var bool
     */
    protected $appendToGitIgnore = true;

    /**
     * Whether the git ignore functionality is enabled
     *
     * @var bool
     */
    protected $gitIgnoreFunctionalityEnabled = true;

    /**
     * Magento Root Directory
     *
     * @var string
     */
    protected $magentoRootDir;

    /**
     * Name of Magento Core Package
     *
     * @var string
     */
    protected $magentoCorePackageType = 'magento-core';

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
            if (!is_array($coreInstallerOptions['excludes'])) {
                throw new \InvalidArgumentException("excludes must be an array of files/directories to ignore");
            }
            $this->deployExcludes = array_merge($this->deployExcludes, $coreInstallerOptions['excludes']);
        }

        //overwrite default ignore directories if some are specified in root package composer.json
        if (isset($coreInstallerOptions['ignore-directories'])) {
            if (!is_array($coreInstallerOptions['ignore-directories'])) {
                throw new \InvalidArgumentException("ignore-directories must be an array of files/directories");
            }
            $this->ignoreDirectories = $coreInstallerOptions['ignore-directories'];
        }

        if (isset($coreInstallerOptions['git-ignore-enable'])) {
            $this->gitIgnoreFunctionalityEnabled = (bool) $coreInstallerOptions['git-ignore-enable'];
        }

        if (isset($coreInstallerOptions['git-ignore-append'])) {
            $this->appendToGitIgnore = (bool) $coreInstallerOptions['git-ignore-append'];
        }

        if (!isset($packageExtra['magento-root-dir'])) {
            throw new \InvalidArgumentException("magento-root-dir must be specified in root package");
        }

        if (isset($packageExtra['magento-core-package-type'])) {
            $this->magentoCorePackageType = $packageExtra['magento-core-package-type'];
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
     * @return bool
     */
    public function gitIgnoreFunctionalityEnabled()
    {
        return $this->gitIgnoreFunctionalityEnabled;
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

    /**
     * @return string
     */
    public function getMagentoCorePackageType()
    {
        return $this->magentoCorePackageType;
    }
}
