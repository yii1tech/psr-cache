<?php

namespace yii1tech\psr\cache\test;

use CDummyCache;
use DateInterval;
use ICache;
use yii1tech\cache\tagged\ArrayCache;
use yii1tech\psr\cache\CacheItemContract;
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

    public function testSave(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->save($item));

        $this->assertTrue($pool->hasItem($key));

        $item = $pool->getItem($key);
        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());
    }

    /**
     * @depends testSave
     */
    public function testGetBatch(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->save($item));

        $items = $pool->getItems([$key]);
        $this->assertArrayHasKey($key, $items);

        $item = $items[$key];

        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());
    }

    /**
     * @depends testSave
     */
    public function testDelete(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->save($item));

        $this->assertTrue($pool->deleteItem($key));

        $this->assertFalse($pool->hasItem($key));

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
    }

    /**
     * @depends testDelete
     */
    public function testDeleteBatch(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->save($item));

        $this->assertTrue($pool->deleteItems([$key]));

        $this->assertFalse($pool->hasItem($key));

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
    }

    /**
     * @depends testSave
     */
    public function testClear(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->save($item));

        $this->assertTrue($pool->clear());

        $this->assertFalse($pool->hasItem($key));

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
    }

    /**
     * @depends testSave
     */
    public function testSaveDeferred(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->saveDeferred($item));

        $this->assertEmpty($pool->getCache()->get($key));

        $deferredItems = $pool->getDeferredItems();
        $this->assertArrayHasKey($key, $deferredItems);

        $pool->commit();
        $this->assertSame($value, $pool->getCache()->get($key));

        $this->assertEmpty($pool->getDeferredItems());
    }

    /**
     * @depends testSaveDeferred
     */
    public function testAutoCommit(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

        $this->assertTrue($pool->saveDeferred($item));

        $cache = $pool->getCache();

        $pool->autocommit = true;
        unset($pool);

        $this->assertSame($value, $cache->get($key));
    }

    /**
     * @depends testSave
     */
    public function testSaveWithDependency(): void
    {
        $pool = new CacheItemPool();

        $GLOBALS['test_state'] = 1;

        $key = 'test';
        $value = 'test-value';

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));
        $item->depends(new \CExpressionDependency('$GLOBALS["test_state"] == 1'));

        $this->assertTrue($pool->save($item));

        $this->assertTrue($pool->hasItem($key));

        $item = $pool->getItem($key);
        $this->assertTrue($item->isHit());

        $GLOBALS['test_state'] = 2;

        $this->assertFalse($pool->hasItem($key));

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
    }

    /**
     * @depends testSave
     */
    public function testGetCallback(): void
    {
        $pool = new CacheItemPool();

        $key = 'test';

        $value = $pool->get($key, function (CacheItemContract $item) {
            $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

            return 'test-value';
        });

        $this->assertSame('test-value', $value);

        $item = $pool->getItem($key);
        $this->assertTrue($item->isHit());
        $this->assertSame('test-value', $item->get());

        $value = $pool->get($key, function (CacheItemContract $item) {
            $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

            return 'new-value';
        });

        $this->assertSame('test-value', $value);
        $this->assertSame('test-value', $pool->getItem($key)->get());
    }

    /**
     * @depends testSave
     */
    public function testSaveWithTags(): void
    {
        $pool = new CacheItemPool();
        $pool->setCache(new ArrayCache());

        $item = $pool->getItem('test-1');
        $item->set('test-value-1');
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));
        $item->tag('tag-1');

        $this->assertTrue($pool->save($item));

        $item = $pool->getItem('test-2');
        $item->set('test-value-2');
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));
        $item->tag('tag-2');

        $this->assertTrue($pool->save($item));

        $this->assertTrue($pool->invalidateTags(['tag-1']));

        $this->assertFalse($pool->hasItem('test-1'));
        $this->assertTrue($pool->hasItem('test-2'));
    }
}