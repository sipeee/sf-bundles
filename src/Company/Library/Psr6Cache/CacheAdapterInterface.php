<?php

namespace Company\Library\Psr6Cache;

use Psr\Cache\CacheItemInterface;

interface CacheAdapterInterface
{
    /**
     * @param array<string> $keys
     *
     * @return array<CacheItemInterface>
     */
    public function getItems(array $keys): array;

    public function hasItem(string $key): bool;

    public function clear(): bool;

    /**
     * @param array<string> $keys
     */
    public function deleteItems(array $keys): bool;

    /**
     * @param array<CacheItemInterface> $items
     */
    public function saveItems(array $items): bool;
}
