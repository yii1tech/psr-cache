<?php

namespace yii1tech\psr\cache;

use CCache;
use Psr\Cache\CacheItemPoolInterface;
use Yii;

/**
 * Cache uses PSR-6 compatible cache pool for cache data storage.
 *
 * This class can be used in case you with to utilize 3rd party PSR-6 cache library into your Yii application.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             'class' => \yii1tech\psr\cache\Cache::class,
 *             'psrCachePool' => function () {
 *                 return new ExamplePsrCachePool();
 *             },
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
class Cache extends CCache
{
    /**
     * @var \Psr\Cache\CacheItemPoolInterface|\Closure|array|null related PSR cache item pool.
     */
    private $_psrCachePool;

    /**
     * @return \Psr\Cache\CacheItemPoolInterface related PSR cache pool.
     */
    public function getPsrCachePool(): CacheItemPoolInterface
    {
        return $this->_psrCachePool;
    }

    /**
     * Sets related PSR cache pool.
     *
     * @param \Psr\Cache\CacheItemPoolInterface|\Closure|array|null $psrCachePool related PSR cache pool.
     * @return static self reference.
     */
    public function setPsrCachePool($psrCachePool): self
    {
        if (!is_object($psrCachePool)) {
            $this->_psrCachePool = Yii::createComponent($psrCachePool);
        } elseif ($psrCachePool instanceof \Closure) {
            $this->_psrCachePool = call_user_func($psrCachePool);
        } else {
            $this->_psrCachePool = $psrCachePool;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($key)
    {
        $item = $this->getPsrCachePool()->getItem($key);
        if (!$item->isHit()) {
            return false;
        }

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues($keys)
    {
        if (empty($keys)) {
            return [];
        }

        $result = [];
        foreach ($this->getPsrCachePool()->getItems($keys) as $item) {
            /** @var \Psr\Cache\CacheItemInterface $item */
            $result[$item->getKey()] = $item->isHit() ? $item->get() : false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value, $expire)
    {
        $pool = $this->getPsrCachePool();

        $item = $pool->getItem($key)
            ->set($value)
            ->expiresAfter(\DateInterval::createFromDateString("{$expire} seconds"));

        return $pool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function addValue($key, $value, $expire)
    {
        $pool = $this->getPsrCachePool();

        $item = $pool->getItem($key);
        if ($item->isHit()) {
            return false;
        }

        $item->set($value)
            ->expiresAfter(\DateInterval::createFromDateString("{$expire} seconds"));

        return $pool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValue($key)
    {
        return $this->getPsrCachePool()->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function flushValues()
    {
        return $this->getPsrCachePool()->clear();
    }
}