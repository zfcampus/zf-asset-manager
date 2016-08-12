<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZF\AssetManager;

use Composer\Composer;
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
            'post-package-install'  => 'onPostPackageInstall',
            'post-package-update'  => 'onPostPackageUpdate',
            'pre-package-uninstall' => 'onPrePackageUninstall',
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
     * Install assets provided by the package, if any.
     *
     * @param PackageEvent $event
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        $installer = new AssetInstaller($this->composer, $this->io);
        $installer($event);
    }

    /**
     * Updates assets provided by the package, if any.
     *
     * Uninstalls any previously installed assets for the package, and then
     * runs an install for the package.
     *
     * @param PackageEvent $event
     */
    public function onPostPackageUpdate(PackageEvent $event)
    {
        // Uninstall any previously installed packages
        $uninstall = new AssetUninstaller($this->composer, $this->io);
        $uninstall($event);

        // Install packages
        $installer = new AssetInstaller($this->composer, $this->io);
        $installer($event);
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
}
