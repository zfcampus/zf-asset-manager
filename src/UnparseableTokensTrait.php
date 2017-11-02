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
        T_CLASS,
        T_CLONE,
        T_DOUBLE_COLON,
        T_EVAL,
        T_EXIT,
        T_EXTENDS,
        T_INTERFACE,
        T_NEW,
        T_TRAIT,
    ];

    /**
     * @var string $packageConfigPath
     * @return bool
     */
    private function isParseableContent($packageConfigPath)
    {
        $contents = file_get_contents($packageConfigPath);
        $tokens = token_get_all($contents);
        foreach ($tokens as $token) {
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
