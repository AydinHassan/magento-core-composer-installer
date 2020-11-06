magento-core-composer-installer
===============================

[![Build Status](https://github.com/AydinHassan/magento-core-composer-installer/workflows/MagentoCoreComposerInstaller/badge.svg)](https://github.com/AydinHassan/magento-core-composer-installer/actions?query=workflow%3AMagentoCoreComposerInstaller)
[![Build Status](https://travis-ci.org/AydinHassan/magento-core-composer-installer.svg?branch=master)](https://travis-ci.org/AydinHassan/magento-core-composer-installer)
[![Latest Stable Version](https://poser.pugx.org/aydin-hassan/magento-core-composer-installer/v/stable.svg)](https://packagist.org/packages/aydin-hassan/magento-core-composer-installer)
[![Latest Unstable Version](https://poser.pugx.org/aydin-hassan/magento-core-composer-installer/v/unstable.svg)](https://packagist.org/packages/aydin-hassan/magento-core-composer-installer)

A Composer Plugin to manage Magento Core

This tool allows you to manage Magento Core in your project using Composer. 

Advantages:
 * Keep Magento out of your project repository
 * Allows for easy upgrades to new Magento versions
 
You require this tool in your project and require a specific version of Magento. An initial install of a 
Magento package will trigger an install to your Magento root directory. A `.gitignore` file will be automatically created
with all of the files in the Magento core package (Some are grouped, more on this later).

When a package is removed, either using `composer remove magento/magento` or manually removing from your `composer.json` file
and running `composer update` all of the files present in the version of Magento you are removing will be deleted from
your `magento-root-dir` folder. If there are custom files in any of these folders, these will not be deleted.

This allows you to install and remove the Magento core in to your project without having to commit it as the `.gitignore`
is automatically updated. Further to this, in your project root you could ignore the `.gitignore` in the `magento-root-dir`
This would mean that you can install, update & remove Magento core without any untracked files showing in your
repository.

Now updating Magento Core is easy, simply change your require to `"magento/magento": 1.10.0` or whatever the newest version is and run `composer update`!

Compatibility
-------------

This tool works with any version of PHP >= 7.0. It is automatically tested using Travis on version PHP versions 7.0, 7.1, 7.2, 7.3, 7.4. 

Installation
------------

    $ cd magento-project
    $ composer require aydin-hassan/magento-core-composer-installer

The latest stable version will be installed.

In order for the installer to actually do anything, you will also need to require a core Magento package.
This should be a Magento release with a `composer.json` file in the root. It should be defined like so:

    {
        "name": "magento/magento",
        "description": "Magento Mirror",
        "type": "magento-core"
    }

See [here](https://github.com/AydinHassan/magento-community/blob/1.9/composer.json) for an example. You can use this repository for your Magento CE builds if you wish so.

You can create your own public or private Magento repository to host the different versions.
You should tag each version as the version it is. The `type` key is important. The Magento Core Composer Installer
will only install packages which have a type of `magento-core`.

Read [here](#creating-a-core-package) to see how you can create your own Magento Source Code Repository.

To use the Magento package you will have to add the repository to your projects `composer.json` file:

    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:AydinHassan/magento-community.git"
        }
    ],
    
Then you can do:

    $ composer require "magento/magento:1.9.1.0"
    
    
Overall your projects `composer.json` should look something like:

    {
        "name": "somevendor/someproject",
        "description": "Magento build for ...",
        "require-dev": {
            "phpunit/phpunit": "~4.4"
        },
        "require": {
            "aydin-hassan/magento-core-composer-installer" : "^2.0",
            "magento/magento" : "1.9.1.0"
        },
        "authors": [
            {
                "name": "Aydin Hassan",
                "email": "aydin@hotmail.co.uk"
            }
        ],
        "repositories": [
            {
                "type": "vcs",
                "url": "git@github.com:AydinHassan/magento-community.git"
            }
        ],
        "extra": {
            "magento-root-dir": "htdocs"
        }
    }
    
Also note the `magento-root-dir`, this is used to specify where you want Magento to be installed to.
    
Configuration
-------------
Configuration can be provided under the `magento-core-deploy` under the `extra` key in your root `composer.json` file

Available configuration

 * `excludes` - An array of files to not copy when installing the core, useful if local overrides are necessary, or you want to track a `.htaccess`.
 * `ignore-directories` - An array of folders to group ignores together to make the core `.gitignore` smaller.
 * `git-ignore-append` - Defaults to `true`. Whether to append to the `.gitignore` in the `magento-root-dir` folder. If false it will be wiped out on each deploy.
 * `git-ignore-enable` - Defaults to `true`. Setting this to `false` will prevent any actions being taken on the `.gitignore` file in the `magento-root-dir` folder.

Example configuration:

    {
         ...
         "extra" {
             "magento-root-dir": "htdocs",
             "magento-core-deploy" : {
                "excludes": [ 
                    ".htaccess"
                ],
                "ignore-directories": [
                    "lib/Zend"
                ],
                "git-ignore-append": false
             }
         }
    }
    
The above config will, install everything from the core package, except the file `.htaccess`, it will group all files
under `lib\Zend` in the `htdocs\.gitignore` file. And it will wipe the `htdocs\.gitignore` every time Magento is updated or removed. 
 
### Excludes

Use this to exclude certain files and folders from being copied from the core in to your root directory. If the exclude path you include is actually a folder, everything within that folder will be excluded.

Exclude all files within `lib`


    {
         ...
         "extra" {
             "magento-root-dir": "htdocs",
             "magento-core-deploy" : {
                "excludes": [ 
                    "lib"
                ]
             }
         }
    }

Exclude just `app/etc/local.xml`

    {
         ...
         "extra" {
             "magento-root-dir": "htdocs",
             "magento-core-deploy" : {
                "excludes": [ 
                    "app/etc/local.xml"
                ]
             }
         }
    }


### Ignore Directories

This one requires a little more explanation. Generating a `.gitignore` for every file in Magento results in a file well over 
10,000 lines. On investigation this seems to slow git commands like `git status` down quite a bit. Some issues  were taking
up to 14 seconds for me. 

In order to combat this, any files which are in a default set of folders, will not be added to the `.gitignore`, instead
only the folder will, this greatly reduces the size of the `.gitignore`. The list of folders which are ignored by default can be 
found [here](https://github.com/AydinHassan/magento-core-composer-installer/blob/master/src/Options.php#L24) 

If you need to commit files inside these directories then you can override this list by setting the `ignore-directories`
key, noted above. Your list will not be merged, it will be used instead. This is in case you want to remove one of the ignore directories.

Creating a core package
-----------------------

I have provided a script which allows you to easily manage a mirror of Magento. It will work for both Community and Enterprise. The below instructions explain how to create a core package.

Add new Magento version script: https://gist.github.com/AydinHassan/6ed0bf2219ea0f122402

1. Create a repository or clone an existing one: `cd && mkdir magento-mirror && git init`
2. Download a version of Magento and extract it: `cd && tar -xzf magento.tar.gz`
3. Download this script to your home directory: `cd && curl https://gist.githubusercontent.com/AydinHassan/6ed0bf2219ea0f122402/raw/28d1e629947ef4e92082914172c5000a417d87c5/add-magento-version.php -o add-magento-version.php`
4. Run it with the locations of your repository and the extracted Magento code: `php add-magento-version.php ~/magento-mirror ~/magento`
5. The new version will be committed & tagged. You can now push this up to the remote.

The script will figure out the version and edition of Magento from the source. It will create branches and tags based on those versions. 

Branches are major.minor:

so `1.9.0.0`, `1.9.0.1` & `1.9.10` all go in the `1.9` branch

`1.10.0.0` would cause a new `1.10` branch to be created.
 
`1.10` will be branched of `1.9` so you can diff them easily


Running the Tests
-----------------

    $ git clone git@github.com:AydinHassan/magento-core-composer-installer.git
    $ cd magento-core-composer-installer
    $ composer install
    $ ./vendor/bin/phpunit


