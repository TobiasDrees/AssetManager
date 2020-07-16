<?php

namespace AssetManager\Factory\Resource;

use RecursiveDirectoryIterator;
use RecursiveFilterIterator;

use function preg_match;

class DirectoryResourceFilterIterator extends RecursiveFilterIterator
{
    protected ?string $pattern;

    public function __construct(RecursiveDirectoryIterator $iterator, ?string $pattern = null)
    {
        parent::__construct($iterator);

        $this->pattern = $pattern;
    }

    public function accept(): bool
    {
        $file = $this->current();
        $name = $file->getBasename();

        if ($file->isDir()) {
            return '.' !== $name[0];
        }

        return null === $this->pattern || 0 < preg_match($this->pattern, $name);
    }

    public function getChildren(): self
    {
        return new self(
            new RecursiveDirectoryIterator(
                $this->current()->getPathname(),
                RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            $this->pattern
        );
    }
}
