<?php

namespace AssetManager\Filter;

use ArrayIterator;
use AssetManager\Asset\AssetInterface;
use Traversable;

use function count;
use function in_array;

class FilterCollection implements FilterInterface, \IteratorAggregate, \Countable
{
    private array $filters = [];

    public function __construct(array $filters = [])
    {
        foreach ($filters as $filter) {
            $this->ensure($filter);
        }
    }

    /**
     * Checks that the current collection contains the supplied filter.
     *
     * If the supplied filter is another filter collection, each of its
     * filters will be checked.
     */
    public function ensure(FilterInterface $filter)
    {
        if ($filter instanceof Traversable) {
            foreach ($filter as $f) {
                $this->ensure($f);
            }
        } elseif (! in_array($filter, $this->filters, true)) {
            $this->filters[] = $filter;
        }
    }

    public function all(): array
    {
        return $this->filters;
    }

    public function clear()
    {
        $this->filters = [];
    }

    public function filterLoad(AssetInterface $asset)
    {
        foreach ($this->filters as $filter) {
            $filter->filterLoad($asset);
        }
    }

    public function filterDump(AssetInterface $asset)
    {
        foreach ($this->filters as $filter) {
            $filter->filterDump($asset);
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->filters);
    }

    public function count()
    {
        return count($this->filters);
    }
}
