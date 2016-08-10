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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AssetInstaller
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
            return;
        }

        $package = $event->getOperation()->getPackage();
        $installer = $this->composer->getInstallationManager();
        $packagePath = $installer->getInstallPath($package);

        $packageConfigPath = sprintf('%s/config/module.config.php', $packagePath);
        if (! file_exists($packageConfigPath)) {
            return;
        }

        $packageConfig = include $packageConfigPath;
        if (! is_array($packageConfig)
            || ! isset($packageConfig['asset_manager']['resolver_configs']['paths'])
            || ! is_array($packageConfig['asset_manager']['resolver_configs']['paths'])
        ) {
            return;
        }

        $paths = $packageConfig['asset_manager']['resolver_configs']['paths'];

        foreach ($paths as $path) {
            $this->copyAssets($path, $publicPath);
        }
    }

    /**
     * Descend into asset directories and recursively copy to the project path.
     *
     * @param string $path Path containing asset directories
     * @param string $publicPath Public directory/document root of project
     */
    private function copyAssets($path, $publicPath)
    {
        if (! is_dir($path)) {
            return;
        }

        $gitignoreFile = sprintf('%s/.gitignore', $publicPath);

        foreach (new DirectoryIterator($path) as $file) {
            if (! $file->isDir()) {
                continue;
            }

            $assetPath = $file->getBaseName();
            if (in_array($assetPath, ['.', '..'])) {
                continue;
            }

            $this->copy($file->getRealPath(), $publicPath);
            $this->updateGitignore($gitignoreFile, $assetPath);
        }
    }

    /**
     * Recursively copyfiles from the source to the destination.
     *
     * @param string $source Path containing source files.
     * @param string $destination Path to which to copy files.
     */
    private function copy($source, $destination)
    {
        $trimLength = strlen(dirname($source)) + 1;
        $rdi        = new RecursiveDirectoryIterator($source);
        $rii        = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            $sourceFile      = $file->getRealPath();
            $destinationFile = sprintf('%s/%s', $destination, substr($sourceFile, $trimLength));
            $destinationPath = dirname($destinationFile);
            if (! is_dir($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            copy($sourceFile, $destinationFile);
        }
    }

    /**
     * Append a path to the public directory's .gitignore
     *
     * @param string $gitignoreFile
     * @param string $path
     */
    private function updateGitignore($gitignoreFile, $path)
    {
        $gitignoreContents = file_exists($gitignoreFile)
            ? file_get_contents($gitignoreFile)
            : '';

        if (false === $gitignoreContents) {
            return;
        }

        $path = sprintf("%s/", $path);
        $lines = preg_split("/(\r\n?|\n)/", $gitignoreContents);
        if (false !== array_search($path, $lines)) {
            return;
        }

        $lines[] = $path;

        file_put_contents($gitignoreFile, implode("\n", $lines));
    }
}
