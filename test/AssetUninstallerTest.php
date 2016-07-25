<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use ZF\AssetManager\AssetUninstaller;

class AssetUninstallerTest extends TestCase
{
    public function setUp()
    {
        // Create vfs directory for package
        //   - Should have a config subdir
        //     - Put config from above in that dir
        //
        // Create vfs directory for project
        //   - Should have a public subdir
        //
        // Seed a Composer package.
    }

    public function getValidConfig()
    {
        return [
            'asset_manager' => [
                'resolver_configs' => [
                    'paths' => [
                        __DIR__ . '/TestAsset/asset-set-1',
                        __DIR__ . '/TestAsset/asset-set-2',
                    ],
                ],
            ],
        ];
    }

    public function testUninstallerAbortsIfNoPublicSubdirIsPresentInProjectRoot()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with no subdirs.
        //
        // Seed a Composer package.
    }

    public function testUninstallerAbortsIfPackageDoesNotHaveConfiguration()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir
        //
        // Create vfs directory for package, with no subdirs
        //
        // Seed a Composer package.
    }

    public function testUninstallerAbortsIfConfigurationDoesNotContainAssetInformation()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir
        //
        // Create vfs directory for package, with config/module.config.php returning empty array.
        //
        // Seed a Composer package.
    }

    public function testUninstallerAbortsIfConfiguredAssetsAreNotPresentInDocroot()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir, but no copied assets.
        //
        // Create vfs directory for package, with config/module.config.php returning getValidConfig.
        //
        // Seed a Composer package.
    }

    public function testUninstallerRemovesAssetsFromDocumentRootBasedOnConfiguration()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir, and copied assets.
        //   - Asset directories MUST have .gitignore files present
        //
        // Create vfs directory for package, with config/module.config.php returning getValidConfig().
        //
        // Seed a Composer package.
        //
        // - Should loop through each path and:
        //   - recursively remove any subdirectories found from the document root, if matched.
    }

    public function testUninstallerDoesNotRemoveAssetsFromDcoumentRootIfGitignoreFilesAreMissing()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir, and copied assets.
        //   - Asset directories MUST NOT have .gitignore files present
        //
        // Create vfs directory for package, with config/module.config.php returning getValidConfig().
        //
        // Seed a Composer package.
        //
        // - Should loop through each path and DO NOTHING; asset dirs should remain.
    }
}
