<?php

namespace AssetManager\Factory\Resource;

use EmptyIterator;
use Iterator;
use RecursiveDirectoryIterator;

use function filemtime;
use function implode;
use function is_dir;
use function substr;

use const DIRECTORY_SEPARATOR;

class DirectoryResource implements IteratorResourceInterface
{
    private string $path;
    private ?string $pattern;

    public function __construct(string $path, ?string $pattern = null)
    {
        if (DIRECTORY_SEPARATOR !== substr($path, -1)) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->path = $path;
        $this->pattern = $pattern;
    }

    public function isFresh(int $timestamp): bool
    {
        if (! is_dir($this->path) || filemtime($this->path) > $timestamp) {
            return false;
        }

        foreach ($this as $resource) {
            if (! $resource->isFresh($timestamp)) {
                return false;
            }
        }

        return true;
    }

    public function getContent(): string
    {
        $content = [];
        foreach ($this as $resource) {
            $content[] = $resource->getContent();
        }

        return implode("\n", $content);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function getIterator(): Iterator
    {
        return is_dir($this->path)
            ? new DirectoryResourceIterator($this->getInnerIterator())
            : new EmptyIterator();
    }

    protected function getInnerIterator(): DirectoryResourceFilterIterator
    {
        return new DirectoryResourceFilterIterator(
            new RecursiveDirectoryIterator(
                $this->path,
                RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            $this->pattern
        );
    }
}
