<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Installer\InstallationManager;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use ZF\AssetManager\AssetInstaller;

class AssetInstallerTest extends TestCase
{
    protected $expectedAssets = [
        'zf-apigility/css/styles.css',
        'zf-apigility/img/favicon.ico',
        'zf-apigility/js/scripts.js',
        'zf-barbaz/css/styles.css',
        'zf-barbaz/img/favicon.ico',
        'zf-barbaz/js/scripts.js',
        'zf-foobar/images/favicon.ico',
        'zf-foobar/scripts/scripts.js',
        'zf-foobar/styles/styles.css',
    ];

    public function setUp()
    {
        // Create virtual filesystem
        $this->filesystem = vfsStream::setup('project');
    }

    public function createInstaller()
    {
        $this->package = $this->prophesize(PackageInterface::class);

        $installationManager = $this->prophesize(InstallationManager::class);
        $installationManager
            ->getInstallPath($this->package->reveal())
            ->willReturn(vfsStream::url('project/vendor/org/package'))
            ->shouldBeCalled();

        $composer = $this->prophesize(Composer::class);
        $composer
            ->getInstallationManager()
            ->will([$installationManager, 'reveal'])
            ->shouldBeCalled();

        $operation = $this->prophesize(InstallOperation::class);
        $operation
            ->getPackage()
            ->will([$this->package, 'reveal'])
            ->shouldBeCalled();

        $this->event = $this->prophesize(PackageEvent::class);
        $this->event
            ->getOperation()
            ->will([$operation, 'reveal'])
            ->shouldBeCalled();

        $this->io = $this->prophesize(IOInterface::class);

        return new AssetInstaller(
            $composer->reveal(),
            $this->io->reveal()
        );
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
        $composer = $this->prophesize(Composer::class);
        $composer->getInstallationManager()->shouldNotBeCalled();

        $installer = new AssetInstaller(
            $composer->reveal(),
            $this->prophesize(IOInterface::class)->reveal()
        );
        $installer->setProjectPath(vfsStream::url('project'));

        $event = $this->prophesize(PackageEvent::class);
        $event->getOperation()->shouldNotBeCalled();

        $this->assertNull($installer($event->reveal()));
    }

    public function testInstallerAbortsIfPackageDoesNotHaveConfiguration()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        $installer = $this->createInstaller();
        $installer->setProjectPath(vfsStream::url('project'));

        $this->assertNull($installer($this->event->reveal()));

        foreach ($this->expectedAssets as $asset) {
            $path = vfsStream::url('project/public/' . $asset);
            $this->assertFileNotExists($path, sprintf('File %s discovered, when it should not exist', $path));
        }
    }

    public function testInstallerAbortsIfConfigurationDoesNotContainAssetInformation()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent('<' . "?php\nreturn [];");

        $installer = $this->createInstaller();
        $installer->setProjectPath(vfsStream::url('project'));

        $this->assertNull($installer($this->event->reveal()));

        foreach ($this->expectedAssets as $asset) {
            $path = vfsStream::url('project/public/' . $asset);
            $this->assertFileNotExists($path, sprintf('File %s discovered, when it should not exist', $path));
        }
    }

    public function testInstallerCopiesAssetsToDocumentRootBasedOnConfiguration()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(sprintf('<' . "?php\nreturn %s;", var_export($this->getValidConfig(), true)));

        $installer = $this->createInstaller();
        $installer->setProjectPath(vfsStream::url('project'));

        $this->assertNull($installer($this->event->reveal()));

        foreach ($this->expectedAssets as $asset) {
            $path = vfsStream::url('project/public/' . $asset);
            $this->assertFileExists($path, sprintf('File %s not present, when it should exist', $path));
        }
    }

    public function testInstallerUpdatesPublicGitIgnoreFileWithEntryForEachAssetDirectoryItCopies()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(sprintf('<' . "?php\nreturn %s;", var_export($this->getValidConfig(), true)));

        $installer = $this->createInstaller();
        $installer->setProjectPath(vfsStream::url('project'));

        $this->assertNull($installer($this->event->reveal()));

        $gitIgnoreFile = vfsStream::url('project/public/.gitignore');
        $this->assertFileExists($gitIgnoreFile, 'public/.gitignore was not created');
        $contents = file_get_contents($gitIgnoreFile);
        $this->assertContains("\nzf-apigility", $contents, 'public/.gitignore is missing the zf-apigility/ entry');
        $this->assertContains("\nzf-barbaz/", $contents, 'public/.gitignore is missing the zf-barbaz/ entry');
        $this->assertContains("\nzf-foobar/", $contents, 'public/.gitignore is missing the zf-foobar/ entry');
    }

    public function testInstallerDoesNotAddDuplicateEntriesToGitignore()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('public/.gitignore')
            ->at($this->filesystem)
            ->setContent("zf-apigility/\nzf-bar-baz/\nzf-foobar/\n");
        $gitIgnoreFile = vfsStream::url('project/public/.gitignore');
        $this->assertFileExists($gitIgnoreFile, 'public/.gitignore was not created; cannot continue test');

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(sprintf('<' . "?php\nreturn %s;", var_export($this->getValidConfig(), true)));

        $installer = $this->createInstaller();
        $installer->setProjectPath(vfsStream::url('project'));

        $this->assertNull($installer($this->event->reveal()));

        $gitIgnoreContents = file_get_contents($gitIgnoreFile);
        $gitIgnoreContents = explode("\n", $gitIgnoreContents);
        $this->assertEquals(array_unique($gitIgnoreContents), $gitIgnoreContents);
    }

    public function problematicConfiguration()
    {
        return [
            'class'        => [__DIR__ . '/TestAsset/problematic-configs/class.config.php'],
            'clone'        => [__DIR__ . '/TestAsset/problematic-configs/clone.config.php'],
            'double-colon' => [__DIR__ . '/TestAsset/problematic-configs/double-colon.config.php'],
            'eval'         => [__DIR__ . '/TestAsset/problematic-configs/eval.config.php'],
            'exit'         => [__DIR__ . '/TestAsset/problematic-configs/exit.config.php'],
            'extends'      => [__DIR__ . '/TestAsset/problematic-configs/extends.config.php'],
            'interface'    => [__DIR__ . '/TestAsset/problematic-configs/interface.config.php'],
            'new'          => [__DIR__ . '/TestAsset/problematic-configs/new.config.php'],
            'trait'        => [__DIR__ . '/TestAsset/problematic-configs/trait.config.php'],
        ];
    }

    /**
     * @dataProvider problematicConfiguration
     * @param string $configFile
     */
    public function testInstallerSkipsConfigFilesUsingClassConstantValues($configFile)
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(file_get_contents($configFile));

        $installer = $this->createInstaller();
        $installer->setProjectPath(vfsStream::url('project'));

        $this->io
            ->writeError(
                Argument::containingString('Unable to check for asset configuration in')
            )
            ->shouldBeCalled();

        $this->assertNull($installer($this->event->reveal()));

        foreach ($this->expectedAssets as $asset) {
            $path = vfsStream::url('project/public/' . $asset);
            $this->assertFileNotExists($path, sprintf('File %s discovered, when it should not exist', $path));
        }
    }
}
