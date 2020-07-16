<?php

namespace AssetManager\Cache;

use AssetManager\Exception\RuntimeException;
use Laminas\Stdlib\ErrorHandler;

use function file_exists;
use function file_get_contents;
use function mkdir;
use function pathInfo;
use function umask;

class FilePathCache implements CacheInterface
{
    protected string $dir;
    protected string $filename;
    protected ?string $cachedFile;

    public function __construct(string $dir, string $filename)
    {
        $this->dir = $dir;
        $this->filename = $filename;
        $this->cachedFile = null;
    }

    public function has(string $key): bool
    {
        return file_exists($this->cachedFile());
    }

    public function get(string $key): ?string
    {
        $path = $this->cachedFile();

        if (! file_exists($path)) {
            throw new RuntimeException('There is no cached value for ' . $this->filename);
        }

        return file_get_contents($path);
    }

    public function set(string $key, string $value): void
    {
        $pathInfo = pathInfo($this->cachedFile());
        $cacheDir = $pathInfo['dirname'];
        $fileName = $pathInfo['basename'];

        ErrorHandler::start();

        if (! is_dir($cacheDir)) {
            $umask = umask(0);
            mkdir($cacheDir, 0777, true);
            umask($umask);

            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd

        ErrorHandler::stop();

        if (! is_writable($cacheDir)) {
            throw new RuntimeException('Unable to write file ' . $this->cachedFile());
        }

        // Use "rename" to achieve atomic writes
        $tmpFilePath = $cacheDir . '/AssetManagerFilePathCache_' . $fileName;

        if (@file_put_contents($tmpFilePath, $value, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write file ' . $this->cachedFile());
        }

        rename($tmpFilePath, $this->cachedFile());
    }

    // @todo Return?
    public function remove(string $key): bool
    {
        ErrorHandler::start(\E_WARNING);

        $success = unlink($this->cachedFile());

        ErrorHandler::stop();

        if (false === $success) {
            throw new RuntimeException(sprintf('Could not remove key "%s"', $this->cachedFile()));
        }

        return $success;
    }

    protected function cachedFile(): string
    {
        if (null === $this->cachedFile) {
            $this->cachedFile = rtrim($this->dir, '/') . '/' . ltrim($this->filename, '/');
        }

        return $this->cachedFile;
    }
}
