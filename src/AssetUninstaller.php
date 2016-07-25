<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZF\AssetManager;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use DirectoryIterator;

class AssetUninstaller
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var array .gitignore rules
     */
    private $gitignore;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var string Base path for project; default is current working dir.
     */
    private $projectPath;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->projectPath = getcwd();
    }

    /**
     * Allow overriding the project path (primarily for testing).
     *
     * @param string $path
     */
    public function setProjectPath($path)
    {
        $this->projectPath = $path;
    }

    /**
     * @param PackageEvent $event
     */
    public function __invoke(PackageEvent $event)
    {
        $publicPath = sprintf('%s/public', $this->projectPath);
        if (! is_dir($publicPath)) {
            // No public path in the project; nothing to remove
            return;
        }

        $gitignoreFile = sprintf('%s/.gitignore', $publicPath);
        if (! file_exists($gitignoreFile)) {
            // No .gitignore rules; nothing to remove
            return;
        }

        $package = $event->getOperation()->getPackage();
        $installer = $this->composer->getInstallationManager();
        $packagePath = $installer->getInstallPath($package);

        $packageConfigPath = sprintf('%s/config/module.config.php', $packagePath);
        if (! file_exists($packageConfigPath)) {
            // No module configuration defined; nothing to remove
            return;
        }

        $packageConfig = include $packageConfigPath;
        if (! is_array($packageConfig)
            || ! isset($packageConfig['asset_manager']['resolver_configs']['paths'])
            || ! is_array($packageConfig['asset_manager']['resolver_configs']['paths'])
        ) {
            // No assets defined; nothing to remove
            return;
        }

        $this->gitignore = $this->fetchIgnoreRules($gitignoreFile);

        $paths = $packageConfig['asset_manager']['resolver_configs']['paths'];

        foreach ($paths as $path) {
            $this->removeAssets($path, $publicPath);
        }

        file_put_contents($gitignoreFile, implode("\n", $this->gitignore));
    }

    /**
     * Discover and remove assets from the public path.
     *
     * @param string $path Path containing asset directories
     * @param string $publicPath Public directory/document root of project
     */
    private function removeAssets($path, $publicPath)
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (new DirectoryIterator($path) as $file) {
            if (! $file->isDir()) {
                // Not a directory; continue
                continue;
            }

            $assetPath = $file->getBaseName();
            if (in_array($assetPath, ['.', '..'])) {
                // Dot directory; continue
                continue;
            }

            $gitignoreEntry = sprintf('%s/', $assetPath);
            if (! in_array($gitignoreEntry, $this->gitignore)) {
                // Not in the public gitignore rules; continue
                continue;
            }

            $pathToRemove = sprintf('%s/%s', $publicPath, $assetPath);
            if (! is_dir($pathToRemove)) {
                // Asset directory does not exist; continue
                continue;
            }

            $this->remove($pathToRemove);
            unset($this->gitignore[array_search($gitignoreEntry, $this->gitignore, true)]);
        }
    }

    /**
     * Recursively remove a tree
     *
     * @param string $tree Filesystem tree to recursively delete
     */
    private function remove($tree)
    {
        $files = array_diff(scandir($tree), ['.', '..']);

        foreach ($files as $file) {
            $path = sprintf('%s/%s', $tree, $file);
            if (is_dir($path)) {
                $this->remove($path);
                continue;
            }

            unlink($path);
        }

        return rmdir($tree);
    }

    /**
     * Retrieve and parse gitignore rules.
     *
     * @param string $file Filename of .gitignore file
     * @return array Array of lines from the file
     */
    private function fetchIgnoreRules($file)
    {
        $text = file_get_contents($file);
        return preg_split("/(\r\n|\r|\n)/", $text);
    }
}
