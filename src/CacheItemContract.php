<?php

namespace yii1tech\psr\cache;

use Psr\Cache\CacheItemInterface;

/**
 * CacheItemContract extends {@see \Psr\Cache\CacheItemInterface}, adding extra features.
 *
 * It allows managing cache dependencies.
 *
 * @see \yii1tech\psr\cache\CacheItemPoolContract
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface CacheItemContract extends CacheItemInterface
{
    /**
     * Sets dependency of the cached item. If the dependency changes, the item is labelled invalid.
     *
     * @param \ICacheDependency|null $dependency dependency of the cached item.
     * @return static self reference.
     */
    public function depends(?\ICacheDependency $dependency);
}