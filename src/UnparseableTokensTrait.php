<?php
/**
 * @see       https://github.com/zendframework/zf-asset-manager for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zf-asset-manager/blob/master/LICENSE.md New BSD License
 */

namespace ZF\AssetManager;

trait UnparseableTokensTrait
{
    /**
     * Tokens that, when they occur in a config file, make it impossible for us
     * to include it in order to aggregate configuration.
     *
     * @var int[]
     */
    private $unparseableTokens = [
        T_EVAL,
        T_EXIT,
    ];

    /**
     * @param string $packageConfigPath
     * @return bool
     */
    private function configFileNeedsParsing($packageConfigPath)
    {
        $contents = file_get_contents($packageConfigPath);
        if (preg_match('/[\'"]asset_manager[\'"]\s*\=\>/s', $contents)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $packageConfigPath
     * @return bool
     */
    private function isParseableContent($packageConfigPath)
    {
        $contents = file_get_contents($packageConfigPath);
        $tokens = token_get_all($contents);
        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            if (in_array($token[0], $this->unparseableTokens, true)) {
                return false;
            }
        }
        return true;
    }
}
