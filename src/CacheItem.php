<?php

namespace yii1tech\psr\cache;

use CComponent;

/**
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CacheItem extends CComponent implements CacheItemContract
{
    /**
     * @var string cache item key (ID).
     */
    private $_key;

    /**
     * @var mixed cache item value.
     */
    private $_value;

    /**
     * @var int|null cache item expire.
     */
    private $_expire;

    /**
     * @var \ICacheDependency|null dependency of the cache item.
     */
    private $_dependency;

    /**
     * Sets the key for the current cache item.
     *
     * @param string $key the key string for this cache item.
     * @return static self reference.
     */
    public function setKey(string $key): self
    {
        $this->_key = $key;

        return $this;
    }

    /**
     * @return int|null cache item expiration in seconds.
     */
    public function getExpire()
    {
        return $this->_expire;
    }

    /**
     * @return \ICacheDependency|null dependency of the cache item.
     */
    public function getDependency(): ?\ICacheDependency
    {
        return $this->_dependency;
    }

    /**
     * @param \ICacheDependency|null $dependency dependency of the cache item.
     * @return static self reference.
     */
    public function setDependency(?\ICacheDependency $dependency): self
    {
        $this->_dependency = $dependency;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->_key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if ($this->_value === false) {
            return null;
        }

        return $this->_value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->_value !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value): self
    {
        $this->_value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration): self
    {
        if ($expiration === null) {
            $this->_expire = null;
        } else {
            $this->_expire = $expiration->getTimestamp() - time();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time): self
    {
        if ($time === null) {
            $this->_expire = null;
        } elseif ($time instanceof \DateInterval) {
            $timestamp = (new \DateTime())->add($time)->getTimestamp();
            $this->_expire = $timestamp - time();
        } else {
            $this->_expire = (int) $time;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function depends(?\ICacheDependency $dependency): self
    {
        return $this->setDependency($dependency);
    }
}