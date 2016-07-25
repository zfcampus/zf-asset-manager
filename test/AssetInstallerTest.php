<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\AssetManager\AssetInstaller;

class AssetInstallerTest extends TestCase
{
    public function setUp()
    {
        // Seed the test asset directories with files; these will then be copied to the vfs directory later
        //
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

    public function testInstallerAbortsIfNoPublicSubdirIsPresentInProjectRoot()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with no subdirs.
        //
        // Seed a Composer package.
    }

    public function testInstallerAbortsIfPackageDoesNotHaveConfiguration()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir
        //
        // Create vfs directory for package, with no subdirs
        //
        // Seed a Composer package.
    }

    public function testInstallerAbortsIfConfigurationDoesNotContainAssetInformation()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir
        //
        // Create vfs directory for package, with config/module.config.php returning empty array.
        //
        // Seed a Composer package.
    }

    public function testInstallerCopiesAssetsToDocumentRootBasedOnConfiguration()
    {
        $this->markTestIncomplete();
        // Create vfs directory for project, with public subdir.
        //
        // Create vfs directory for package, with config/module.config.php returning getValidConfig().
        //
        // Seed a Composer package.
        //
        // - Should loop through each path and:
        //   - recursively copy ONLY directories found under that path to the doc root in the project
    }
}
