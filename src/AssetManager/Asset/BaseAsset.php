<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AssetManager\Asset;

use AssetManager\Filter\FilterCollection;
use AssetManager\Filter\FilterInterface;

use function in_array;
use function sprintf;

abstract class BaseAsset implements AssetInterface
{
    private $filters;
    private $sourceRoot;
    private $sourcePath;
    private $sourceDir;
    private $targetPath;
    private $content;
    private $loaded;
    private $vars;
    private $values;

    /**
     * Constructor.
     *
     * @param array $filters Filters for the asset
     * @param string $sourceRoot The root directory
     * @param string $sourcePath The asset path
     * @param array $vars
     */
    public function __construct($filters = array(), $sourceRoot = null, $sourcePath = null, array $vars = array())
    {
        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->sourcePath = $sourcePath;
        if ($sourcePath && $sourceRoot) {
            $this->sourceDir = dirname("$sourceRoot/$sourcePath");
        }
        $this->vars = $vars;
        $this->values = array();
        $this->loaded = false;
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
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
    }

    /**
     * Encapsulates asset loading logic.
     *
     * @param string $content The asset content
     * @param FilterInterface $additionalFilter An additional filter
     */
    protected function doLoad($content, FilterInterface $additionalFilter = null)
    {
        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $asset->setContent($content);

        $filter->filterLoad($asset);
        $this->content = $asset->getContent();

        $this->loaded = true;
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        if (! $this->loaded) {
            $this->load();
        }

        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $filter->filterDump($asset);

        return $asset->getContent();
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
        return $this->sourcePath;
    }

    public function getSourceDirectory(): ?string
    {
        return $this->sourceDir;
    }

    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    public function setTargetPath(string $targetPath)
    {
        if ($this->vars) {
            foreach ($this->vars as $var) {
                if (false === strpos($targetPath, $var)) {
                    throw new \RuntimeException(
                        sprintf(
                            'The asset target path "%s" must contain the variable "{%s}".',
                            $targetPath,
                            $var
                        )
                    );
                }
            }
        }

        $this->targetPath = $targetPath;
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function setValues(array $values)
    {
        foreach ($values as $var => $v) {
            if (! in_array($var, $this->vars, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The asset with source path "%s" has no variable named "%s".',
                        $this->sourcePath,
                        $var
                    )
                );
            }
        }

        $this->values = $values;
        $this->loaded = false;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
