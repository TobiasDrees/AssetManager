<?php

namespace AssetManager\Cache;

use RuntimeException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function unlink;

class FilesystemCache implements CacheInterface
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    public function has(string $key): bool
    {
        return file_exists($this->dir . '/' . $key);
    }

    public function get(string $key): ?string
    {
        $path = $this->dir . '/' . $key;

        if (! file_exists($path)) {
            throw new RuntimeException('There is no cached value for ' . $key);
        }

        return file_get_contents($path);
    }

    public function set(string $key, string $value): void
    {
        if (! is_dir($this->dir) && false === @mkdir($this->dir, 0777, true)) {
            throw new RuntimeException('Unable to create directory ' . $this->dir);
        }

        $path = $this->dir . '/' . $key;

        if (false === @file_put_contents($path, $value)) {
            throw new RuntimeException('Unable to write file ' . $path);
        }
    }

    // @todo muss angepasst werden. So wie wie FilePath
    public function remove(string $key): void
    {
        $path = $this->dir . '/' . $key;

        if (file_exists($path) && false === @unlink($path)) {
            throw new RuntimeException('Unable to remove file ' . $path);
        }
    }
}
