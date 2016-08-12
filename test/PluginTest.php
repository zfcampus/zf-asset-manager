<?php
/**
 * @link      http://github.com/zfcampus/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZFTest\AssetManager;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\AssetManager\Plugin;

class PluginTest extends TestCase
{
    public function testSubscribesToExpectedEvents()
    {
        $this->assertEquals([
            'post-package-install' => 'onPostPackageInstall',
            'post-package-update' => 'onPostPackageUpdate',
            'pre-package-uninstall' => 'onPrePackageUninstall',
        ], Plugin::getSubscribedEvents());
    }
}
