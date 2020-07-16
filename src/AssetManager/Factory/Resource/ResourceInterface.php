<?php

namespace AssetManager\Factory\Resource;

interface ResourceInterface
{
    public function isFresh(int $timestamp): bool;

    public function getContent(): string;

    public function __toString(): string;
}
