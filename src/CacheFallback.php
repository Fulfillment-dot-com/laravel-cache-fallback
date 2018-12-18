<?php

namespace Fingo\LaravelCacheFallback;

use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\Writer;

/**
 * Class CacheFallback
 * @package Fingo\LaravelCacheFallback
 */
class CacheFallback extends CacheManager
{
    /**
     * Resolve the given store.
     *
     * @param  string $name
     * @return \Illuminate\Contracts\Cache\Repository
     * @throws Exception
     */
    protected function resolve($name)
    {
        try {
            return parent::resolve($name);
        } catch (Exception $e) {
			$logger = app()->make(Writer::class);
			$logger->critical('Cache driver failed to resolve, using fallback', [
				'driver' => $name,
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
				]);
            if ($newDriver = $this->nextDriver($name)) {
                return $this->resolve($newDriver);
            }
            throw $e;
        }
    }

    /**
     * Get next driver name based on fallback order
     *
     * @param $driverName
     * @return string|null
     */
    private function nextDriver($driverName)
    {
        $driverOrder = config('cache_fallback.fallback_order');
        if (in_array($driverName, $driverOrder, true) && last($driverOrder) !== $driverName) {
            $nextKey = array_search($driverName, $driverOrder, true) + 1;
            return $driverOrder[$nextKey];
        }
        return null;
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array $config
     * @return \Illuminate\Cache\RedisStore
     */
    protected function createRedisDriver(array $config)
    {
        $redisDriver = parent::createRedisDriver($config);
        $redisDriver->get('test');
        return $redisDriver;
    }

    /**
     * Create an instance of the database cache driver.
     *
     * @param  array $config
     * @return \Illuminate\Cache\DatabaseStore
     */
    protected function createDatabaseDriver(array $config)
    {
        $databaseDriver = parent::createDatabaseDriver($config);
        $databaseDriver->get('test');
        return $databaseDriver;
    }
}
