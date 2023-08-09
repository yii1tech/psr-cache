<p align="center">
    <a href="https://github.com/yii1tech" target="_blank">
        <img src="https://avatars.githubusercontent.com/u/134691944" height="100px">
    </a>
    <h1 align="center">Yii1 PSR-6 Cache Extension</h1>
    <br>
</p>

This extension allows integration with PSR-6 compatible cache for Yii1.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://img.shields.io/packagist/v/yii1tech/psr-cache.svg)](https://packagist.org/packages/yii1tech/psr-cache)
[![Total Downloads](https://img.shields.io/packagist/dt/yii1tech/psr-cache.svg)](https://packagist.org/packages/yii1tech/psr-cache)
[![Build Status](https://github.com/yii1tech/psr-cache/workflows/build/badge.svg)](https://github.com/yii1tech/psr-cache/actions)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii1tech/psr-cache
```

or add

```json
"yii1tech/psr-cache": "*"
```

to the "require" section of your composer.json.


Usage
-----

This extension allows integration with [PSR-6](https://www.php-fig.org/psr/psr-6/) compatible cache for Yii1.
It provides several instruments for that. Please choose the one suitable for your particular needs.


### Wrap PSR cache pool into Yii cache <span id="wrap-psr-cache-pool-into-yii-cache"></span>

The most common use case for PSR caceh involvement into Yii application is usage of 3rd party cache library.
This can be achieved using `\yii1tech\psr\cache\Cache` as Yii cache component.

Application configuration example:

```php
<?php

return [
    'components' => [
        'cache' => [
            'class' => \yii1tech\psr\cache\Cache::class,
            'psrCachePool' => function () {
                // ...
                return new ExamplePsrCachePool(); // instantiate 3rd party cache library
            },
        ],
        // ...
    ],
    // ...
];
```


### Wrap Yii cache into PSR cache pool <span id="wrap-yii-cache-into-psr-cache-pool"></span>

There is another use case related to PSR cache besides bootstrapping eternal cache storage.
Sometimes 3rd party libraries may require PSR cache pool instance to be passed to them in order to function.
`\Psr\Cache\CacheItemPoolInterface` allows wrapping standard Yii cache component into a PSR compatible cache pool.

Application configuration example:

```php
<?php

return [
    'components' => [
        'cache' => [
            'class' => \CMemCache::class,
            'servers' => [
                // ...
            ],
        ],
        \Psr\Cache\CacheItemPoolInterface::class => [
            'class' => \yii1tech\psr\cache\CacheItemPool::class,
            'cache' => 'cache',
        ],
        // ...
    ],
    // ...
];
```

Usage example:

```php
<?php

use Psr\Cache\CacheItemPoolInterface;

function getCachedValue()
{
    /** @var CacheItemPoolInterface $pool */
    $pool = Yii::app()->getComponent(CacheItemPoolInterface::class);
    
    $item = $pool->getItem('example-cache-id');
    
    if ($item->isHit()) { 
        return $item->get(); // cache exist - return cached value
    }
    
    $value = Yii::app()->db->createCommand('SELECT ...')->query(); // some heave SQL query.
    
    $item->set($value) // set value to be cached
        ->expiresAfter(DateInterval::createFromDateString('1 hour')); // set expiration
    
    $pool->save($item); // put value into cache
    
    return $value;
}
```


### Extended interface <span id="extended-interface"></span>

This extension introduces 2 interfaces, which extend the ones from PSR-6:

- `\yii1tech\psr\cache\CacheItemPoolContract`
- `\yii1tech\psr\cache\CacheItemContract`

These interfaces could be used to utilize additional functionality, which is omitted at PSR.
In particular these allow usage of Yii cache dependency feature. For example:

```php
<?php

use yii1tech\psr\cache\CacheItemPoolContract;

function getValueCachedWithDependency()
{
    /** @var CacheItemPoolContract $pool */
    $pool = Yii::app()->getComponent(CacheItemPoolContract::class);
    
    $item = $pool->getItem('example-cache-id');
    
    if ($item->isHit()) {
        return $item->get(); // cache exist - return cached value
    }
    
    $value = Yii::app()->db->createCommand('SELECT ...')->query(); // some heave SQL query.
    
    $item->set($value) // set value to be cached
        ->expiresAfter(DateInterval::createFromDateString('1 hour')) // set expiration
        ->depends(new CDbCacheDependency('SELECT MAX(id) FROM `items`')); // set cache dependency
    
    $pool->save($item); // put value into cache
    
    return $value;
}
```

In addition, `\yii1tech\psr\cache\CacheItemPoolContract` declares method `get()`, which can be used to simplify usage
of the cache pool, making it similar to [Symfony](https://symfony.com/doc/7.0/cache.html).
For example:

```php
<?php

use yii1tech\psr\cache\CacheItemContract;
use yii1tech\psr\cache\CacheItemPoolContract;

function getCachedValue()
{
    /** @var CacheItemPoolContract $pool */
    $pool = Yii::app()->getComponent(CacheItemPoolContract::class);
    
    return $pool->get('example-cache-id', function (CacheItemContract $item) {
        // enters here, only if cache is missing
        $item->expiresAfter(DateInterval::createFromDateString('1 hour')); // use callback argument to configure cache item: set expiration and so on
        
        $value = Yii::app()->db->createCommand('SELECT ...')->query(); // some heave SQL query.
        
        return $value; // returned value automatically saved ot cache
    });
}
```


### Using cache tags <span id="using-cache-tags"></span>

This extension allows setup of tags per each particular cache items via `\yii1tech\psr\cache\CacheItemContract::tag()` method.

**Heads up!** This package does not directly implement cache tags feature - it does rely on wrapped Yii cache component to support it instead.
All tags associated with the cache items are passed as 5th argument to `\ICache::set()` method assuming its particular implementation will
handle them. Thus cache item tags saving will **silently fail** in related cache component does not provide support for it.

You may use [yii1tech/tagged-cache](https://github.com/yii1tech/tagged-cache) extension to get a tag aware cache Yii component.

Application configuration example:

```php
<?php

return [
    'components' => [
        'cache' => [
            'class' => \yii1tech\cache\tagged\MemCache::class, // use tag aware cache component
            'servers' => [
                // ...
            ],
        ],
        \Psr\Cache\CacheItemPoolInterface::class => [
            'class' => \yii1tech\psr\cache\CacheItemPool::class,
            'cache' => 'cache',
        ],
        // ...
    ],
    // ...
];
```

Tag specification example:

```php
<?php

use yii1tech\psr\cache\CacheItemContract;
use yii1tech\psr\cache\CacheItemPoolContract;

function getCachedValue()
{
    /** @var CacheItemPoolContract $pool */
    $pool = Yii::app()->getComponent(CacheItemPoolContract::class);
    
    return $pool->get('example-cache-id', function (CacheItemContract $item) {
        $item->expiresAfter(DateInterval::createFromDateString('1 hour'));
        $item->tag(['database', 'example']); // specify the list of tags for the item
        
        $value = Yii::app()->db->createCommand('SELECT ...')->query(); // some heave SQL query.
        
        return $value;
    });
}
```

In order to clear items associated with particular tag use `\yii1tech\psr\cache\CacheItemPoolContract::invalidateTags()`.
For example:

```php
<?php

use yii1tech\psr\cache\CacheItemPoolContract;

/** @var CacheItemPoolInterface $pool */
$pool = Yii::app()->getComponent(CacheItemPoolInterface::class);

$pool->invalidateTags(['database']); // clear only items tagged as "database"
```
