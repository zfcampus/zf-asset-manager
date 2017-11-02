<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
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

    public function testWorkflowWhenPerformingComposerOperations()
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->activate($this->composer->reveal(), $this->io->reveal()));

        $installEvent = $this->prophesize(PackageEvent::class)->reveal();
        $this->assertNull($plugin->onPostPackageInstall($installEvent));
        $this->assertAttributeCount(1, 'installers', $plugin);

        $uninstallEvent = $this->prophesize(PackageEvent::class)->reveal();
        $this->assertNull($plugin->onPrePackageUninstall($uninstallEvent));
        $this->assertAttributeCount(1, 'uninstallers', $plugin);

        $updateEvent = $this->mockUpdateEvent();
        $this->assertNull($plugin->onPostPackageUpdate($updateEvent));
        $this->assertAttributeCount(2, 'installers', $plugin);
        $this->assertAttributeCount(2, 'uninstallers', $plugin);
    }

    public function testOnPostAutoloadDumpTriggersUninstallersFollowedByInstallers()
    {
        $spy = (object) ['operations' => []];

        $uninstaller1 = function () use ($spy) {
            $spy->operations[] = 'uninstaller1';
        };
        $uninstaller2 = function () use ($spy) {
            $spy->operations[] = 'uninstaller2';
        };
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

        $r = new ReflectionProperty($plugin, 'uninstallers');
        $r->setAccessible(true);
        $r->setValue($plugin, [$uninstaller1, $uninstaller2]);

        $expected = [
            'uninstaller1',
            'uninstaller2',
            'installer1',
            'installer2',
        ];

        $this->assertNull($plugin->onPostAutoloadDump());

        $this->assertSame($expected, $spy->operations);
    }

    private function mockUpdateEvent()
    {
        $initialPackage = $this->prophesize(PackageInterface::class);
        $targetPackage = $this->prophesize(PackageInterface::class);

        $operation = $this->prophesize(UpdateOperation::class);
        $operation->getInitialPackage()->will([$initialPackage, 'reveal']);
        $operation->getTargetPackage()->will([$targetPackage, 'reveal']);

        $event = $this->prophesize(PackageEvent::class);
        $event->getOperation()->will([$operation, 'reveal']);

        return $event->reveal();
    }
}
