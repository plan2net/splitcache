# splitcache
Split TYPO3 cache-entries to multiple backends based on lifetime


Sample config f. cache_pages. 
```
['SYS']['caching']['cacheConfigurations']['cache_pages'] = 
	[
        'backend' => \Plan2net\Splitcache\Cache\Backend\SplitcacheBackend::class,
        'options' => [
            'defaultLifetime' => 3600,
            'levels' => [
                0 => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class,
                    'options' => [
                        'defaultLifetime' => 3600,
                        'database' => 5,
                        'maxLifetime' => 1800,
                        'hostname'=>'redishost',
                        'port'=> 6379
                    ]
                ],
                1 => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class,
                    'options' => [
                        'defaultLifetime' => 3600,
                        'database' => 10,
                        'maxLifetime' => 3600,
                        'hostname'=>'redishost',
                        'port'=> 6379
                    ]
                ],
                2 => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'defaultLifetime' => 3600,
                        'maxLifetime' => 86400
                    ]
                ],
                3 => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
                    'options' => [
                        'defaultLifetime' => 3600,
                        'maxLifetime' => \TYPO3\CMS\Core\Cache\Backend\AbstractBackend::UNLIMITED_LIFETIME
                    ]
                ]

            ]
        ]
	]
```
