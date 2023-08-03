<?php

namespace yii1tech\psr\cache;

use CApplicationComponent;
use Yii;

/**
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CacheItemPool extends CApplicationComponent
{
    /**
     * @var \ICache|array|string
     */
    private $_cache = 'cache';

    /**
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
     * @param \ICache|array|string $cache cache component instance, application component ID or array configuration.
     * @return static self reference.
     */
    public function setCache($cache): self
    {
        $this->_cache = $cache;

        return $this;
    }


}