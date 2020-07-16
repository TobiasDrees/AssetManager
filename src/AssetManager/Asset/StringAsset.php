<?php

namespace AssetManager\Asset;

use AssetManager\Filter\FilterInterface;

class StringAsset extends BaseAsset
{
    private string $string;
    private int $lastModified;

    public function __construct(
        string $content,
        array $filters = [],
        ?string $sourceRoot = null,
        ?string $sourcePath = null
    ) {
        $this->string = $content;

        parent::__construct($filters, $sourceRoot, $sourcePath);
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        $this->doLoad($this->string, $additionalFilter);
    }

    public function setLastModified(int $lastModified)
    {
        $this->lastModified = $lastModified;
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }
}
