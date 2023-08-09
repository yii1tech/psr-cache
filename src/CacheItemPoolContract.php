<?php

namespace yii1tech\psr\cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * CacheItemPoolContract extends {@see \Psr\Cache\CacheItemPoolInterface}, adding extra features to it.
 *
 * @see \yii1tech\psr\cache\CacheItemContract
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface CacheItemPoolContract extends CacheItemPoolInterface
{
    /**
     * {@inheritdoc}
     *
     * @return \yii1tech\psr\cache\CacheItemContract the corresponding Cache Item.
     */
    public function getItem($key);

    /**
     * {@inheritdoc}
     *
     * @return \Traversable|array<string, \yii1tech\psr\cache\CacheItemContract> collection of Cache Items keyed by the cache keys of each item.
     */
    public function getItems(array $keys = []);

    /**
     * Fetches a value from the pool or computes it via given callback if not found.
     * Usage example:
     *
     * ```php
     * $value = $pool->get('example-cache-key', function (CacheItemContract $item) {
     *     $item->expiresAfter(3600);
     *
     *     // ...
     *
     *     return $computedValue; // heavy computations result
     * });
     * ```
     *
     * @template T
     *
     * @param string $key the key of the item to retrieve from the cache.
     * @param (callable(CacheItemContract,bool):T)|(callable(CacheItemContract,bool):T) $callback callback, which computes value to be cached.
     * @return T cached value or callback result.
     */
    public function get(string $key, callable $callback);

    /**
     * Deletes cached entries, associated with given tags.
     *
     * @param string[] $tags tags, which associated with items should be deleted.
     * @return bool whether cache entries have been successfully deleted or not.
     */
    public function invalidateTags(array $tags): bool;
}