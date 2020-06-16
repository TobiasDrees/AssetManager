<?php

namespace AssetManager\Factory\Worker;

use AssetManager\Asset\AssetInterface;
use AssetManager\Factory\AssetFactory;

/**
 * Assets are passed through factory workers before leaving the factory.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface WorkerInterface
{
    /**
     * Processes an asset.
     *
     * @param AssetInterface $asset   An asset
     * @param AssetFactory   $factory The factory
     *
     * @return AssetInterface|null May optionally return a replacement asset
     */
    public function process(AssetInterface $asset, AssetFactory $factory);
}
