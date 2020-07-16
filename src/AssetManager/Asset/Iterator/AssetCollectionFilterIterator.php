<?php

namespace AssetManager\Asset\Iterator;

use RecursiveFilterIterator;

use function in_array;

class AssetCollectionFilterIterator extends RecursiveFilterIterator
{
    private array $visited;
    private array $sources;

    public function __construct(AssetCollectionIterator $iterator, array $visited = [], array $sources = [])
    {
        parent::__construct($iterator);

        $this->visited = $visited;
        $this->sources = $sources;
    }

    public function accept(): bool
    {
        $asset = $this->getInnerIterator()->current(true);
        $duplicate = false;

        // check strict equality
        if (in_array($asset, $this->visited, true)) {
            $duplicate = true;
        } else {
            $this->visited[] = $asset;
        }

        // check source
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();
        if ($sourceRoot && $sourcePath) {
            $source = $sourceRoot . '/' . $sourcePath;
            if (in_array($source, $this->sources)) {
                $duplicate = true;
            } else {
                $this->sources[] = $source;
            }
        }

        return ! $duplicate;
    }

    public function getChildren(): self
    {
        return new self($this->getInnerIterator()->getChildren(), $this->visited, $this->sources);
    }
}
