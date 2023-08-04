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

function getCategoriesCount()
{
    /** @var CacheItemPoolInterface $pool */
    $pool = Yii::app()->getComponent(CacheItemPoolInterface::class);
    
    $item = $pool->getItem('categories-count');
    if ($item->isHit()) {
        return $item->get();
    }
    
    $value = Category::model()->count();
    
    $item->set($value)
        ->expiresAfter(DateInterval::createFromDateString('1 hour'));
    
    $pool->save($item);
    
    return $value;
}
```


### Extended interface <span id="extended-interface"></span>


### Using cache tags <span id="using-cache-tags"></span>

