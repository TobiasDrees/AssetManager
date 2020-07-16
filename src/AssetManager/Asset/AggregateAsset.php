<?php

namespace AssetManager\Asset;

use AssetManager\Exception;
use AssetManager\Filter\FilterInterface;

use function max;
use function sprintf;

class AggregateAsset extends BaseAsset
{
    private ?int $lastModified;

    public ?string $mimetype;

    public function __construct(
        array $content = [],
        array $filters = [],
        ?string $sourceRoot = null,
        ?string $sourcePath = null
    ) {
        parent::__construct($filters, $sourceRoot, $sourcePath);
        $this->processContent($content);
    }

    public function load(?FilterInterface $additionalFilter = null)
    {
        $this->doLoad($this->getContent(), $additionalFilter);
    }

    public function setLastModified(?int $lastModified)
    {
        $this->lastModified = $lastModified;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    private function processContent(array $content)
    {
        $this->mimetype = null;
        foreach ($content as $asset) {
            if (null === $this->mimetype) {
                $this->mimetype = $asset->mimetype;
            }

            if ($asset->mimetype !== $this->mimetype) {
                throw new Exception\RuntimeException(
                    sprintf(
                        'Asset "%s" doesn\'t have the expected mime-type "%s".',
                        $asset->getTargetPath(),
                        $this->mimetype
                    )
                );
            }

            $this->setLastModified(
                max(
                    $asset->getLastModified(),
                    $this->getLastModified()
                )
            );
            $this->setContent(
                $this->getContent() . $asset->dump()
            );
        }
    }
}
