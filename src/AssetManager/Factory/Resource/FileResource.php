<?php

namespace AssetManager\Factory\Resource;

use function file_exists;
use function file_get_contents;
use function filemtime;

class FileResource implements ResourceInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isFresh(int $timestamp): bool
    {
        return file_exists($this->path) && filemtime($this->path) <= $timestamp;
    }

    public function getContent(): string
    {
        return file_exists($this->path) ? file_get_contents($this->path) : '';
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
