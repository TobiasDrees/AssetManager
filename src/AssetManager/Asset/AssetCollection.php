<?php

namespace AssetManager\Asset;

use AssetManager\Asset\Iterator\AssetCollectionFilterIterator;
use AssetManager\Asset\Iterator\AssetCollectionIterator;
use AssetManager\Filter\FilterCollection;
use AssetManager\Filter\FilterInterface;
use InvalidArgumentException;
use IteratorAggregate;
use RecursiveIteratorIterator;
use SplObjectStorage;

use function array_flip;
use function array_intersect_key;
use function count;
use function implode;
use function in_array;

class AssetCollection implements IteratorAggregate, AssetCollectionInterface
{
    private array $assets;
    private FilterCollection $filters;
    private ?string $sourceRoot;
    private $targetPath;
    private $content;
    private $clones;
    private array $vars;
    private $values;

    public function __construct(array $assets = [], array $filters = [], ?string $sourceRoot = null, array $vars = [])
    {
        $this->assets = [];
        foreach ($assets as $asset) {
            $this->add($asset);
        }

        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->clones = new SplObjectStorage();
        $this->vars = $vars;
        $this->values = [];
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
        $this->clones = new SplObjectStorage();
    }

    public function all(): array
    {
        return $this->assets;
    }

    public function add(AssetInterface $asset)
    {
        $this->assets[] = $asset;
    }

    public function removeLeaf(AssetInterface $needle, bool $graceful = false): bool
    {
        foreach ($this->assets as $i => $asset) {
            $clone = isset($this->clones[$asset]) ? $this->clones[$asset] : null;
            if (in_array($needle, array($asset, $clone), true)) {
                unset($this->clones[$asset], $this->assets[$i]);

                return true;
            }

            if ($asset instanceof AssetCollectionInterface && $asset->removeLeaf($needle, true)) {
                return true;
            }
        }

        if ($graceful) {
            return false;
        }

        throw new InvalidArgumentException('Leaf not found.');
    }

    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, bool $graceful = false): bool
    {
        foreach ($this->assets as $i => $asset) {
            $clone = isset($this->clones[$asset]) ? $this->clones[$asset] : null;
            if (in_array($needle, [$asset, $clone], true)) {
                unset($this->clones[$asset]);
                $this->assets[$i] = $replacement;

                return true;
            }

            if ($asset instanceof AssetCollectionInterface && $asset->replaceLeaf($needle, $replacement, true)) {
                return true;
            }
        }

        if ($graceful) {
            return false;
        }

        throw new InvalidArgumentException('Leaf not found.');
    }

    public function ensureFilter(FilterInterface $filter): void
    {
        $this->filters->ensure($filter);
    }

    public function getFilters(): array
    {
        return $this->filters->all();
    }

    public function clearFilters(): void
    {
        $this->filters->clear();
        $this->clones = new SplObjectStorage();
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        // loop through leaves and load each asset
        $parts = [];
        foreach ($this as $asset) {
            $asset->load($additionalFilter);
            $parts[] = $asset->getContent();
        }

        $this->content = implode("\n", $parts);
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        // loop through leaves and dump each asset
        $parts = [];
        foreach ($this as $asset) {
            $parts[] = $asset->dump($additionalFilter);
        }
        return implode("\n", $parts);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getSourceRoot(): ?string
    {
        return $this->sourceRoot;
    }

    public function getSourcePath(): ?string
    {
        return null;
    }

    public function getSourceDirectory(): ?string
    {
        return null;
    }

    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    public function setTargetPath(string $targetPath)
    {
        $this->targetPath = $targetPath;
    }

    /**
     * Returns the highest last-modified value of all assets in the current collection.
     *
     * @return integer|null A UNIX timestamp
     */
    public function getLastModified(): ?int
    {
        if (! count($this->assets)) {
            return null;
        }

        $mtime = 0;
        foreach ($this as $asset) {
            $assetMtime = $asset->getLastModified();
            if ($assetMtime > $mtime) {
                $mtime = $assetMtime;
            }
        }

        return $mtime;
    }

    /**
     * Returns an iterator for looping recursively over unique leaves.
     */
    public function getIterator()
    {
        return new RecursiveIteratorIterator(
            new AssetCollectionFilterIterator(
                new AssetCollectionIterator($this, $this->clones)
            )
        );
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;

        foreach ($this as $asset) {
            $asset->setValues(array_intersect_key($values, array_flip($asset->getVars())));
        }
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
