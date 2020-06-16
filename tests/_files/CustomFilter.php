<?php
use AssetManager\Asset\AssetInterface;
class CustomFilter implements AssetManager\Filter\FilterInterface
{
    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $asset->setContent('called');
    }
}
