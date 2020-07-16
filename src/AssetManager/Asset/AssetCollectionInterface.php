<?php

namespace AssetManager\Asset;

use Traversable;

interface AssetCollectionInterface extends AssetInterface, Traversable
{
    public function all(): array;

    public function add(AssetInterface $asset);

    public function removeLeaf(AssetInterface $leaf, bool $graceful = false): bool;

    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, bool $graceful = false): bool;
}
