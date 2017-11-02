<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Installer\InstallationManager;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use ZF\AssetManager\AssetUninstaller;

class AssetUninstallerTest extends TestCase
{
    protected $installedAssets = [
        'public/zf-apigility/css/styles.css',
        'public/zf-apigility/img/favicon.ico',
        'public/zf-apigility/js/scripts.js',
        'public/zf-barbaz/css/styles.css',
        'public/zf-barbaz/img/favicon.ico',
        'public/zf-barbaz/js/scripts.js',
        'public/zf-foobar/images/favicon.ico',
        'public/zf-foobar/scripts/scripts.js',
        'public/zf-foobar/styles/styles.css',
    ];

    protected $structure = [
        'public' => [
            'zf-apigility' => [
                'css' => [
                    'styles.css' => '',
                ],
                'img' => [
                    'favicon.ico' => '',
                ],
                'js' => [
                    'scripts.js' => '',
                ],
            ],
            'zf-barbaz' => [
                'css' => [
                    'styles.css' => '',
                ],
                'img' => [
                    'favicon.ico' => '',
                ],
                'js' => [
                    'scripts.js' => '',
                ],
            ],
            'zf-foobar' => [
                'images' => [
                    'favicon.ico' => '',
                ],
                'scripts' => [
                    'scripts.js' => '',
                ],
                'styles' => [
                    'styles.css' => '',
                ],
            ],
        ],
    ];

    public function setUp()
    {
        // Create virtual filesystem
        $this->filesystem = vfsStream::setup('project');
    }

    public function createAssets()
    {
        vfsStream::create($this->structure);
    }

    public function createUninstaller()
    {
        vfsStream::newFile('public/.gitignore')->at($this->filesystem);

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

        $operation = $this->prophesize(UninstallOperation::class);
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

        return new AssetUninstaller(
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

    public function testUninstallerAbortsIfNoPublicSubdirIsPresentInProjectRoot()
    {
        $composer = $this->prophesize(Composer::class);
        $composer->getInstallationManager()->shouldNotBeCalled();

        $uninstaller = new AssetUninstaller(
            $composer->reveal(),
            $this->prophesize(IOInterface::class)->reveal()
        );
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $event = $this->prophesize(PackageEvent::class);
        $event->getOperation()->shouldNotBeCalled();

        $this->assertNull($uninstaller($event->reveal()));
    }

    public function testUninstallerAbortsIfNoPublicGitignoreFileFound()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        $composer = $this->prophesize(Composer::class);
        $composer->getInstallationManager()->shouldNotBeCalled();

        $uninstaller = new AssetUninstaller(
            $composer->reveal(),
            $this->prophesize(IOInterface::class)->reveal()
        );
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $event = $this->prophesize(PackageEvent::class);
        $event->getOperation()->shouldNotBeCalled();

        $this->assertNull($uninstaller($event->reveal()));
    }

    public function testUninstallerAbortsIfPackageDoesNotHaveConfiguration()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);
        $this->createAssets();

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = vfsStream::url('project/', $asset);
            $this->assertFileExists($path, sprintf('Expected file "%s"; file not found!', $path));
        }
    }

    public function testUninstallerAbortsIfConfigurationDoesNotContainAssetInformation()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);
        $this->createAssets();

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent('<' . "?php\nreturn [];");

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = vfsStream::url('project/', $asset);
            $this->assertFileExists($path, sprintf('Expected file "%s"; file not found!', $path));
        }
    }

    public function testUninstallerAbortsIfConfiguredAssetsAreNotPresentInDocroot()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(sprintf('<' . "?php\nreturn %s;", var_export($this->getValidConfig(), true)));

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        // Seeding the .gitignore happens after createUninstaller, as that
        // seeds an empty file by default.
        $gitignore = "\nzf-apigility/\nzf-barbaz/\nzf-foobar/";
        file_put_contents(
            vfsStream::url('project/public/.gitignore'),
            $gitignore
        );

        $this->assertNull($uninstaller($this->event->reveal()));

        $test = file_get_contents(vfsStream::url('project/public/.gitignore'));
        $this->assertEquals($gitignore, $test);
    }

    public function testUninstallerRemovesAssetsFromDocumentRootBasedOnConfiguration()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);
        $this->createAssets();

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(sprintf('<' . "?php\nreturn %s;", var_export($this->getValidConfig(), true)));

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        // Seeding the .gitignore happens after createUninstaller, as that
        // seeds an empty file by default.
        $gitignore = "\nzf-apigility/\nzf-barbaz/\nzf-foobar/";
        file_put_contents(
            vfsStream::url('project/public/.gitignore'),
            $gitignore
        );

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = sprintf('%s/%s', vfsStream::url('project'), $asset);
            $this->assertFileNotExists($path, sprintf('File "%s" exists when it should have been removed', $path));
        }

        $test = file_get_contents(vfsStream::url('project/public/.gitignore'));
        $this->assertRegexp('/^\s*$/s', $test);
    }

    public function testUninstallerDoesNotRemoveAssetsFromDocumentRootIfGitignoreEntryIsMissing()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);
        $this->createAssets();

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(sprintf('<' . "?php\nreturn %s;", var_export($this->getValidConfig(), true)));

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        // Seeding the .gitignore happens after createUninstaller, as that
        // seeds an empty file by default.
        $gitignore = "\nzf-barbaz/\nzf-foobar/";
        file_put_contents(
            vfsStream::url('project/public/.gitignore'),
            $gitignore
        );

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = sprintf('%s/%s', vfsStream::url('project'), $asset);

            switch (true) {
                case preg_match('#/zf-apigility/#', $asset):
                    $this->assertFileExists($path, sprintf('Expected file "%s"; not found', $path));
                    break;
                case preg_match('#/zf-barbaz/#', $asset):
                    // fall-through
                case preg_match('#/zf-foobar/#', $asset):
                    // fall-through
                default:
                    $this->assertFileNotExists(
                        $path,
                        sprintf('File "%s" exists when it should have been removed', $path)
                    );
                    break;
            }
        }

        $test = file_get_contents(vfsStream::url('project/public/.gitignore'));
        $this->assertEmpty($test);
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
    public function testUninstallerSkipsConfigFilesUsingProblematicConstructs($configFile)
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(file_get_contents($configFile));

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $this->io
            ->writeError(
                Argument::containingString('Unable to check for asset configuration in')
            )
            ->shouldBeCalled();

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = vfsStream::url('project/', $asset);
            $this->assertFileExists($path, sprintf('Expected file "%s"; file not found!', $path));
        }
    }

    public function configFilesWithoutAssetManagerConfiguration()
    {
        return [
            'class'        => [__DIR__ . '/TestAsset/no-asset-manager-configs/class.config.php'],
            'clone'        => [__DIR__ . '/TestAsset/no-asset-manager-configs/clone.config.php'],
            'double-colon' => [__DIR__ . '/TestAsset/no-asset-manager-configs/double-colon.config.php'],
            'eval'         => [__DIR__ . '/TestAsset/no-asset-manager-configs/eval.config.php'],
            'exit'         => [__DIR__ . '/TestAsset/no-asset-manager-configs/exit.config.php'],
            'extends'      => [__DIR__ . '/TestAsset/no-asset-manager-configs/extends.config.php'],
            'interface'    => [__DIR__ . '/TestAsset/no-asset-manager-configs/interface.config.php'],
            'new'          => [__DIR__ . '/TestAsset/no-asset-manager-configs/new.config.php'],
            'trait'        => [__DIR__ . '/TestAsset/no-asset-manager-configs/trait.config.php'],
        ];
    }

    /**
     * @dataProvider configFilesWithoutAssetManagerConfiguration
     * @param string $configFile
     */
    public function testUninstallerSkipsConfigFilesThatDoNotContainAssetManagerString($configFile)
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent(file_get_contents($configFile));

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $this->io
            ->writeError(Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = vfsStream::url('project/', $asset);
            $this->assertFileExists($path, sprintf('Expected file "%s"; file not found!', $path));
        }
    }

    public function testInstallerAllowsConfigurationContainingClassPseudoConstant()
    {
        vfsStream::newDirectory('public')->at($this->filesystem);

        vfsStream::newFile('vendor/org/package/config/module.config.php')
            ->at($this->filesystem)
            ->setContent("<?php\nreturn [\n    'some-key' => AssetUninstaller::class,\n    'asset-manager' => []];");

        $uninstaller = $this->createUninstaller();
        $uninstaller->setProjectPath(vfsStream::url('project'));

        $this->io
            ->writeError(Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull($uninstaller($this->event->reveal()));

        foreach ($this->installedAssets as $asset) {
            $path = vfsStream::url('project/', $asset);
            $this->assertFileExists($path, sprintf('Expected file "%s"; file not found!', $path));
        }
    }
}
