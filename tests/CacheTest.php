<?php

namespace yii1tech\psr\cache\test;

use yii1tech\psr\cache\Cache;
use yii1tech\psr\cache\CacheItemPool;

class CacheTest extends TestCase
{
    public function testSetupPool(): void
    {
        $cache = new Cache();

        $pool = new CacheItemPool();
        $cache->setPsrCachePool($pool);

        $this->assertSame($pool, $cache->getPsrCachePool());

        $cache->setPsrCachePool(function () {
            return new CacheItemPool();
        });
        $this->assertTrue($cache->getPsrCachePool() instanceof CacheItemPool);

        $cache->setPsrCachePool([
            'class' => CacheItemPool::class,
        ]);
        $this->assertTrue($cache->getPsrCachePool() instanceof CacheItemPool);
    }

    public function testGet(): void
    {
        $cache = new Cache();
        $cache->setPsrCachePool(new CacheItemPool());

        $key = 'test';
        $value = 'test-value';

        $this->assertEmpty($cache->get($key));

        $this->assertTrue($cache->set($key, $value));
        $this->assertSame($value, $cache->get($key));
    }

    /**
     * @depends testGet
     */
    public function testGetBatch(): void
    {
        $cache = new Cache();
        $cache->setPsrCachePool(new CacheItemPool());

        $key = 'test';
        $value = 'test-value';

        $values = $cache->mget([$key]);

        $this->assertArrayHasKey($key, $values);
        $this->assertEmpty($values[$key]);

        $cache->set($key, $value);

        $values = $cache->mget([$key]);

        $this->assertArrayHasKey($key, $values);
        $this->assertSame($value, $values[$key]);
    }

    /**
     * @depends testGet
     */
    public function testAdd(): void
    {
        $cache = new Cache();
        $cache->setPsrCachePool(new CacheItemPool());

        $key = 'test';
        $value = 'test-value';

        $this->assertTrue($cache->add($key, $value));
        $this->assertSame($value, $cache->get($key));

        $this->assertFalse($cache->add($key, 'another-value'));
        $this->assertSame($value, $cache->get($key));
    }

    /**
     * @depends testGet
     */
    public function testDelete(): void
    {
        $cache = new Cache();
        $cache->setPsrCachePool(new CacheItemPool());

        $key = 'test';
        $value = 'test-value';

        $cache->set($key, $value);

        $this->assertTrue($cache->delete($key));
        $this->assertEmpty($cache->get($key));
    }

    /**
     * @depends testGet
     */
    public function testFlush(): void
    {
        $cache = new Cache();
        $cache->setPsrCachePool(new CacheItemPool());

        $key = 'test';
        $value = 'test-value';

        $cache->set($key, $value);

        $this->assertTrue($cache->flush());
        $this->assertEmpty($cache->get($key));
    }
}