<?php

namespace AssetManager\Asset;

use AssetManager\Filter\FilterInterface;
use AssetManager\Util\VarUtils;
use InvalidArgumentException;
use RuntimeException;

use function basename;
use function dirname;
use function file_get_contents;
use function filemtime;
use function is_file;
use function sprintf;
use function strlen;
use function strpos;
use function substr;

class FileAsset extends BaseAsset
{
    private string $source;

    public function __construct(
        string $source,
        array $filters = [],
        ?string $sourceRoot = null,
        ?string $sourcePath = null,
        array $vars = []
    ) {
        if (null === $sourceRoot) {
            $sourceRoot = dirname($source);
            if (null === $sourcePath) {
                $sourcePath = basename($source);
            }
        } elseif (null === $sourcePath) {
            if (0 !== strpos($source, $sourceRoot)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The source "%s" is not in the root directory "%s"',
                        $source,
                        $sourceRoot
                    )
                );
            }

            $sourcePath = substr($source, strlen($sourceRoot) + 1);
        }

        $this->source = $source;

        parent::__construct($filters, $sourceRoot, $sourcePath, $vars);
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        $source = VarUtils::resolve($this->source, $this->getVars(), $this->getValues());

        if (! is_file($source)) {
            throw new RuntimeException(sprintf('The source file "%s" does not exist.', $source));
        }

        $this->doLoad(file_get_contents($source), $additionalFilter);
    }

    public function getLastModified(): int
    {
        $source = VarUtils::resolve($this->source, $this->getVars(), $this->getValues());

        if (! is_file($source)) {
            throw new RuntimeException(sprintf('The source file "%s" does not exist.', $source));
        }

        return filemtime($source);
    }
}
