<?php

namespace AssetManager\Asset;

use AssetManager\Filter\FilterInterface;
use AssetManager\Util\VarUtils;
use RecursiveIteratorIterator;

use function glob;
use function is_file;

class GlobAsset extends AssetCollection
{
    private array $globs;
    private bool $initialized;

    public function __construct(array $globs, array $filters = [], ?string $root = null, array $vars = [])
    {
        $this->globs = (array) $globs;
        $this->initialized = false;

        parent::__construct([], $filters, $root, $vars);
    }

    public function all(): array
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return parent::all();
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        parent::load($additionalFilter);
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return parent::dump($additionalFilter);
    }

    public function getLastModified(): ?int
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return parent::getLastModified();
    }

    public function getIterator(): RecursiveIteratorIterator
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return parent::getIterator();
    }

    public function setValues(array $values): void
    {
        parent::setValues($values);
        $this->initialized = false;
    }

    /**
     * Initializes the collection based on the glob(s) passed in.
     */
    private function initialize()
    {
        foreach ($this->globs as $glob) {
            $glob = VarUtils::resolve($glob, $this->getVars(), $this->getValues());

            if (false !== $paths = glob($glob)) {
                foreach ($paths as $path) {
                    if (is_file($path)) {
                        $asset = new FileAsset($path, [], $this->getSourceRoot(), null, $this->getVars());
                        $asset->setValues($this->getValues());
                        $this->add($asset);
                    }
                }
            }
        }

        $this->initialized = true;
    }
}
