<?php

namespace AssetManager\Cache;

/**
 * @todo Kann nicht PSR/Cache Interface genutzt werden?
 */
interface CacheInterface
{
    public function has(string $key): bool;

    public function get(string $key): ?string;

    public function set(string $key, string $value): void;

    // @todo muss bool
    public function remove(string $key): bool;
}
