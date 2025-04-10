<?php

namespace Company\Library\Psr6Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    private CacheAdapterInterface $adapter;
    /** @var array<CacheItemInterface> */
    private array $loadedItems = [];
    /** @var array<CacheItemInterface> */
    private array $deferredItems = [];

    public function __construct(CacheAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function __destruct()
    {
        $this->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key): CacheItemInterface
    {
        $items = $this->getItems([$key]);

        return $items[$key];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<CacheItemInterface>
     */
    public function getItems(array $keys = []): array
    {
        $items = $this->getItemsFromCache($keys);

        if (empty($keys)) {
            return $items;
        }

        $queriedItems = $this->adapter->getItems($keys);

        foreach ($keys as $key) {
            if (!isset($queriedItems[$key])) {
                $queriedItems[$key] = self::createEmptyItem($key);
            }

            $this->loadedItems[$key] = $queriedItems[$key];
        }

        return array_merge($items, $queriedItems);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->loadedItems = $this->deferredItems = [];

        return $this->adapter->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key): bool
    {
        return isset($this->loadedItems[$key]) || $this->adapter->hasItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key): bool
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        if (empty($keys)) {
            return true;
        }

        $keys = array_unique($keys);

        foreach ($keys as $key) {
            unset($this->loadedItems[$key], $this->deferredItems[$key]);
        }

        return $this->adapter->deleteItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->saveItems([$item->getKey() => $item]);
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItems[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): bool
    {
        return $this->saveItems($this->deferredItems);
    }

    /**
     * @param array<CacheItemInterface> $items
     */
    private function saveItems(array $items): bool
    {
        if (empty($items)) {
            return true;
        }

        $success = $this->adapter->saveItems($items);

        if ($success) {
            foreach ($items as $key => $item) {
                $this->loadedItems[$key] = self::createRefreshedItem($item);
                unset($this->deferredItems[$key]);
            }
        }

        return $success;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<CacheItemInterface>
     */
    private function getItemsFromCache(array &$keys): array
    {
        $items = [];
        $keys = array_unique($keys);
        foreach ($keys as $i => $key) {
            if (isset($this->loadedItems[$key])) {
                $items[$key] = $this->loadedItems[$key];
                unset($keys[$i]);
            }
        }

        return $items;
    }

    private function createRefreshedItem(CacheItemInterface $item): CacheItem
    {
        $newItem = new CacheItem(
            $item->getKey(),
            $item->get(),
            null === $item->getExpiry() || new \DateTime() < $item->getExpiry()
        );
        $newItem->expiresAt($item->getExpiry());

        return $newItem;
    }

    private static function createEmptyItem(string $key): CacheItem
    {
        return new CacheItem(
            $key,
            null,
            false
        );
    }
}
