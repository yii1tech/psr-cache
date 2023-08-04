<?php

namespace yii1tech\psr\cache;

use CApplicationComponent;
use Psr\Cache\CacheItemInterface;
use Yii;

/**
 * CacheItemPool allows wrapping standard Yii cache component into a PSR compatible cache pool.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             'class' => \CMemCache::class,
 *             'servers' => [
 *                 // ...
 *             ],
 *         ],
 *         \Psr\Cache\CacheItemPoolInterface::class => [
 *             'class' => \yii1tech\psr\cache\CacheItemPool::class,
 *             'cache' => 'cache',
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CacheItemPool extends CApplicationComponent implements CacheItemPoolContract
{
    /**
     * @var bool whether to automatically commit all deferred items on object destruction.
     */
    public $autocommit = true;

    /**
     * @var \ICache|array|string wrapped Yii cache component.
     */
    private $_cache = 'cache';

    /**
     * @var \Psr\Cache\CacheItemInterface[] deferred cache items.
     */
    private $_deferredItems = [];

    /**
     * Destructor.
     * Commits deferred cache items, if {@see $autocommit} is enabled.
     */
    public function __destruct()
    {
        if ($this->autocommit) {
            $this->commit();
        }
    }

    /**
     * Returns wrapped Yii cache component instance.
     *
     * @return \ICache Yii cache component instance.
     */
    public function getCache()
    {
        if (!is_object($this->_cache)) {
            if (is_string($this->_cache)) {
                $this->_cache = Yii::app()->getComponent($this->_cache);
            } else {
                $this->_cache = Yii::createComponent($this->_cache);
            }
        }

        return $this->_cache;
    }

    /**
     * Sets the Yii cache component to be used for cache items storage.
     *
     * @param \ICache|array|string $cache cache component instance, application component ID or array configuration.
     * @return static self reference.
     */
    public function setCache($cache): self
    {
        $this->_cache = $cache;

        return $this;
    }

    /**
     * @return \Psr\Cache\CacheItemInterface[] deferred cache items.
     */
    public function getDeferredItems(): array
    {
        return $this->_deferredItems;
    }

    /**
     * Instantiates cache item.
     *
     * @param string $key cache item key.
     * @param mixed $value cache item value.
     * @return \Psr\Cache\CacheItemInterface cache item instance.
     */
    protected function createCacheItem($key, $value): CacheItemInterface
    {
        $item = new CacheItem();
        $item->setKey($key);
        $item->set($value);

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key): CacheItemInterface
    {
        if (isset($this->_deferredItems[$key])) {
            return $this->_deferredItems[$key];
        }

        $value = $this->getCache()->get($key);

        return $this->createCacheItem($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($this->getCache()->mget($keys) as $key => $value) {
            $items[$key] = $this->createCacheItem($key, $value);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        return $this->getCache()->get($key) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->getCache()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key): bool
    {
        return $this->getCache()->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $cache = $this->getCache();

        $result = true;

        foreach ($keys as $key) {
            if (!$cache->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }

        return $this->getCache()->set(
            $item->getKey(),
            $item->get(),
            $item->getExpire(),
            $item->getDependency()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->_deferredItems[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $result = true;

        foreach ($this->_deferredItems as $key => $item) {
            if ($this->save($item)) {
                unset($this->_deferredItems[$key]);
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, callable $callback)
    {
        $item = $this->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $value = call_user_func($callback, $item);

        $item->set($value);

        $this->save($item);

        return $value;
    }
}