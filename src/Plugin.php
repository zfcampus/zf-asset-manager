<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZF\AssetManager;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * Array of installers to run following a dump-autoload operation.
     *
     * @var callable[]
     */
    private $installers = [];

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Provide composer event listeners.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump'    => 'onPostAutoloadDump',
            'post-package-install'  => 'onPostPackageInstall',
            'post-package-update'   => 'onPostPackageUpdate',
            'pre-package-uninstall' => 'onPrePackageUninstall',
            'pre-package-update'    => 'onPrePackageUpdate',
        ];
    }

    /**
     * Activate the plugin
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Execute all installers.
     */
    public function onPostAutoloadDump()
    {
        while (0 < count($this->installers)) {
            $installer = array_shift($this->installers);
            $installer();
        }
    }

    /**
     * Memoize an installer for the package being installed.
     *
     * @param PackageEvent $event
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        $installer = new AssetInstaller($this->composer, $this->io);
        $this->installers[] = function () use ($event) {
            $installer = new AssetInstaller($this->composer, $this->io);
            $installer($event);
        };
    }

    /**
     * Installs assets for a package being updated.
     *
     * Memoizes an install operation to run post-autoload-dump.
     *
     * @param PackageEvent $event
     */
    public function onPostPackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();
        $targetPackage = $operation->getTargetPackage();

        // Install new assets; delay until post-autoload-update
        $this->installers[] = function () use ($event, $operation, $targetPackage) {
            $installer = new AssetInstaller($this->composer, $this->io);
            $installer($this->createPackageEventWithOperation(
                $event,
                new InstallOperation($targetPackage, $operation->getReason())
            ));
        };
    }

    /**
     * Uninstall assets provided by the package, if any.
     *
     * @param PackageEvent $event
     */
    public function onPrePackageUninstall(PackageEvent $event)
    {
        $uninstall = new AssetUninstaller($this->composer, $this->io);
        $uninstall($event);
    }

    /**
     * Removes previously installed assets for a package being updated.
     *
     * @param PackageEvent $event
     */
    public function onPrePackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();
        $initialPackage = $operation->getInitialPackage();

        // Uninstall any previously installed assets
        $uninstall = new AssetUninstaller($this->composer, $this->io);
        $uninstall($this->createPackageEventWithOperation(
            $event,
            new UninstallOperation($initialPackage, $operation->getReason())
        ));
    }

    /**
     * Creates and returns a new PackageEvent with the given operation.
     *
     * @param PackageEvent $event
     * @param OperationInterface $operation
     * @return PackageEvent
     */
    private function createPackageEventWithOperation(PackageEvent $event, OperationInterface $operation)
    {
        return new PackageEvent(
            $event->getName(),
            $this->composer,
            $this->io,
            $event->isDevMode(),
            $event->getPolicy(),
            $event->getPool(),
            $event->getInstalledRepo(),
            $event->getRequest(),
            $event->getOperations(),
            $operation
        );
    }
}
