<?php

namespace yii1tech\psr\cache\test;

use CDummyCache;
use ICache;
use yii1tech\psr\cache\CacheItemPool;

class CacheItemPoolTest extends TestCase
{
    public function testSetupCache(): void
    {
        $pool = new CacheItemPool();

        $cache = new CDummyCache();

        $pool->setCache($cache);
        $this->assertSame($cache, $pool->getCache());

        $pool->setCache([
            'class' => CDummyCache::class,
        ]);
        $cache = $pool->getCache();
        $this->assertTrue($cache instanceof CDummyCache);
    }

    public function testGetDefaultCache(): void
    {
        $pool = new CacheItemPool();

        $cache = $pool->getCache();
        $this->assertTrue($cache instanceof ICache);
    }
}