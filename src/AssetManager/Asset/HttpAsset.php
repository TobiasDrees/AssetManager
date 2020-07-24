<?php

namespace AssetManager\Asset;

use AssetManager\Filter\FilterInterface;
use AssetManager\Util\VarUtils;
use InvalidArgumentException;
use RuntimeException;

use function explode;
use function file_get_contents;
use function sprintf;
use function stream_context_create;
use function stripos;
use function strpos;
use function strtotime;
use function trim;

class HttpAsset extends BaseAsset
{
    private string $sourceUrl;
    private bool $ignoreErrors;

    public function __construct(string $sourceUrl, array $filters = [], bool $ignoreErrors = false, array $vars = [])
    {
        if (0 === strpos($sourceUrl, '//')) {
            $sourceUrl = 'http:' . $sourceUrl;
        } elseif (false === strpos($sourceUrl, '://')) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid URL.', $sourceUrl));
        }

        $this->sourceUrl = $sourceUrl;
        $this->ignoreErrors = $ignoreErrors;

        [$scheme, $url] = explode('://', $sourceUrl, 2);
        [$host, $path] = explode('/', $url, 2);

        parent::__construct($filters, $scheme . '://' . $host, $path, $vars);
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        $content = @file_get_contents(
            VarUtils::resolve($this->sourceUrl, $this->getVars(), $this->getValues())
        );

        if (false === $content && ! $this->ignoreErrors) {
            throw new RuntimeException(sprintf('Unable to load asset from URL "%s"', $this->sourceUrl));
        }

        $this->doLoad($content, $additionalFilter);
    }

    public function getLastModified(): ?int
    {
        if (
            false !== @file_get_contents(
                $this->sourceUrl,
                false,
                stream_context_create(
                    [
                        'http' => [
                            'method' => 'HEAD'
                        ]
                    ]
                )
            )
        ) {
            foreach ($http_response_header as $header) {
                if (0 === stripos($header, 'Last-Modified: ')) {
                    [, $mtime] = explode(':', $header, 2);

                    return strtotime(trim($mtime));
                }
            }
        }

        return null;
    }
}
