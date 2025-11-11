<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

/**
 * Declares a cache region to store heavy operations
 */
trait UsesCache
{
    /**
     * Cache key
     */
    private string $usesCacheKey;

    /**
     * Cache value
     */
    private mixed $usesCacheValue;

    /**
     * Gets value from cache
     *
     * @param  string  $key  Cache key
     * @param  callable():mixed  $function  Function to compute cache value
     */
    private function fromCache(string $key, callable $function): mixed
    {
        if ($this->usesCacheKey === $key) {
            return $this->usesCacheValue;
        }
        $this->usesCacheKey = $key;
        $this->usesCacheValue = $function();

        return $this->usesCacheValue;
    }
}
