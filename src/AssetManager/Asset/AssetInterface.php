<?php

namespace AssetManager\Asset;

use AssetManager\Filter\FilterInterface;

interface AssetInterface
{
    public function ensureFilter(FilterInterface $filter): void;

    public function getFilters(): array;

    public function clearFilters(): void;

    public function load(?FilterInterface $additionalFilter = null): void;

    public function dump(?FilterInterface $additionalFilter = null): string;

    public function getContent(): string;

    public function setContent(string $content): void;

    public function getSourceRoot(): ?string;

    public function getSourcePath(): ?string;

    public function getSourceDirectory(): ?string;

    public function getTargetPath(): ?string;

    public function setTargetPath(string $targetPath);

    public function getLastModified(): ?int;

    public function getVars(): array;

    public function setValues(array $values);

    public function getValues(): array;
}
