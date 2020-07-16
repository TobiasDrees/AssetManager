<?php

namespace AssetManager\Cache;

use Laminas\Cache\Storage\StorageInterface;

class LaminasCacheAdapter implements CacheInterface
{
    protected StorageInterface $laminasCache;

    public function __construct(StorageInterface $laminasCache)
    {
        $this->laminasCache = $laminasCache;
    }

    public function has(string $key): bool
    {
        return $this->laminasCache->hasItem($key);
    }

    public function get(string $key): ?string
    {
        return $this->laminasCache->getItem($key);
    }

    // @todo return?
    public function set(string $key, string $value): void
    {
        return $this->laminasCache->setItem($key, $value);
    }

    // @todo return?
    public function remove(string $key): void
    {
        return $this->laminasCache->removeItem($key);
    }
}
