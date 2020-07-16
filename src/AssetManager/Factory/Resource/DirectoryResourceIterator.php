<?php

namespace AssetManager\Factory\Resource;

use RecursiveIteratorIterator;

class DirectoryResourceIterator extends RecursiveIteratorIterator
{
    public function current(): FileResource
    {
        return new FileResource(
            parent::current()->getPathname()
        );
    }
}
