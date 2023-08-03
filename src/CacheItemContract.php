<?php

namespace yii1tech\psr\cache;

use Psr\Cache\CacheItemInterface;

/**
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