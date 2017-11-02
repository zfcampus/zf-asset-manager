<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\CompositeRepository;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;
use ZF\AssetManager\Plugin;

class PluginTest extends TestCase
{
    public function setUp()
    {
        // Create virtual filesystem
        $this->filesystem = vfsStream::setup('project');

        $this->composer = $this->prophesize(Composer::class);
        $this->io = $this->prophesize(IOInterface::class);
    }

    public function testSubscribesToExpectedEvents()
    {
        $this->assertEquals([
            'post-autoload-dump'    => 'onPostAutoloadDump',
            'post-package-install'  => 'onPostPackageInstall',
            'post-package-update'   => 'onPostPackageUpdate',
            'pre-package-uninstall' => 'onPrePackageUninstall',
        ], Plugin::getSubscribedEvents());
    }

    public function testPostPackageInstallShouldMemoizeInstallerCallback()
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->activate($this->composer->reveal(), $this->io->reveal()));

        $installEvent = $this->prophesize(PackageEvent::class)->reveal();
        $this->assertNull($plugin->onPostPackageInstall($installEvent));
        $this->assertAttributeCount(1, 'installers', $plugin);
    }

    public function testPrePackageUninstallShouldTriggerAssetUninstaller()
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->activate($this->composer->reveal(), $this->io->reveal()));

        $uninstallEvent = $this->mockUninstallEvent();
        $this->assertNull($plugin->onPrePackageUninstall($uninstallEvent));
    }

    public function testUpdateOperationShouldMemoizeInstallOperationAndInvokeAssetUninstaller()
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->activate($this->composer->reveal(), $this->io->reveal()));

        $updateEvent = $this->mockUpdateEvent();
        $this->assertNull($plugin->onPostPackageUpdate($updateEvent));
        $this->assertAttributeCount(1, 'installers', $plugin);
    }

    public function testOnPostAutoloadDumpTriggersInstallers()
    {
        $spy = (object) ['operations' => []];

        $installer1 = function () use ($spy) {
            $spy->operations[] = 'installer1';
        };
        $installer2 = function () use ($spy) {
            $spy->operations[] = 'installer2';
        };

        $plugin = new Plugin();

        $r = new ReflectionProperty($plugin, 'installers');
        $r->setAccessible(true);
        $r->setValue($plugin, [$installer1, $installer2]);

        $expected = [
            'installer1',
            'installer2',
        ];

        $this->assertNull($plugin->onPostAutoloadDump());

        $this->assertSame($expected, $spy->operations);
    }

    private function mockUninstallEvent()
    {
        vfsStream::newFile('public/.gitignore')->at($this->filesystem);

        $event = $this->prophesize(PackageEvent::class);
        $event->getOperation()->shouldNotBeCalled();

        return $event->reveal();
    }

    private function mockUpdateEvent()
    {
        $initialPackage = $this->prophesize(PackageInterface::class);
        $targetPackage = $this->prophesize(PackageInterface::class);

        $operation = $this->prophesize(UpdateOperation::class);
        $operation->getInitialPackage()->will([$initialPackage, 'reveal']);
        $operation->getTargetPackage()->will([$targetPackage, 'reveal']);
        $operation->getReason()->willReturn('update');

        $policy = $this->prophesize(PolicyInterface::class);
        $pool = $this->prophesize(Pool::class);
        $repo = $this->prophesize(CompositeRepository::class);
        $request = $this->prophesize(Request::class);

        $event = $this->prophesize(PackageEvent::class);
        $event
            ->getOperation()
            ->will([$operation, 'reveal'])
            ->shouldBeCalled();

        $event->getName()->willReturn('post-package-update');
        $event->isDevMode()->willReturn(true);
        $event->getPolicy()->will([$policy, 'reveal']);
        $event->getPool()->will([$pool, 'reveal']);
        $event->getInstalledRepo()->will([$repo, 'reveal']);
        $event->getRequest()->will([$request, 'reveal']);
        $event->getOperations()->willReturn([]);

        return $event->reveal();
    }
}
