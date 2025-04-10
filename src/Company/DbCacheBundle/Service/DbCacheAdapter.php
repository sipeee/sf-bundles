<?php

namespace Company\DbCacheBundle\Service;

use Company\DbCacheBundle\Entity\CacheEntry;
use Company\Library\Psr6Cache\CacheAdapterInterface;
use Company\Library\Psr6Cache\CacheItem;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\Proxy;
use Psr\Cache\CacheItemInterface;

class DbCacheAdapter implements CacheAdapterInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<CacheItemInterface>
     */
    public function getItems(array $keys): array
    {
        $caches = $this->loadEntitiesByKeys($keys);

        $items = [];
        foreach ($caches as $cache) {
            $items[$cache->getKey()] = self::createItemByCacheEntry($cache);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem(string $key): bool
    {
        $cache = $this->getRepository()->find($key);

        return null !== $cache;
    }

    public function clear(): bool
    {
        try {
            $this->getEntityManager()->clear(CacheEntry::class);
            $this->getEntityManager()->createQueryBuilder()
                ->delete(CacheEntry::class, 'c')
                ->getQuery()->execute();
        } catch (MappingException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        $entityManager = $this->getEntityManager();
        foreach ($keys as $key) {
            $cacheEntry = $this->getEntityManager()->getReference(CacheEntry::class, $key);
            if (self::isCacheEntryLoaded($cacheEntry)) {
                $entityManager->detach($cacheEntry);
            }
        }

        $count = $this->deleteByKeys($keys);

        return $count < count($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function saveItems(array $items): bool
    {
        $entityManager = $this->getEntityManager();

        $this->loadEntitiesByKeys(array_keys($items));

        $cacheEntries = [];
        foreach ($items as $item) {
            $cacheEntry = $this->createOrUpdateCacheEntryByItem($item);
            $cacheEntries[] = $cacheEntry;
        }

        $entityManager->flush($cacheEntries);

        return true;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<CacheEntry>
     */
    private function loadEntitiesByKeys(array $keys): array
    {
        $loadedCacheEntries = $this->loadCacheEntriesFromDoctrine($keys);
        if (empty($keys)) {
            return $loadedCacheEntries;
        }

        $cacheEntries = $this->queryCacheEntriesByKeys($keys);

        /* @var  $caches */
        return array_merge($loadedCacheEntries, $cacheEntries);
    }

    private function deleteByKeys(array $keys): int
    {
        return $this->getRepository()->createQueryBuilder('c')
            ->delete(CacheEntry::class, 'c')
            ->where('c.key IN (:keys)')
            ->setParameter('keys', $keys)
            ->getQuery()->execute();
    }

    private function createOrUpdateCacheEntryByItem(CacheItemInterface $item): CacheEntry
    {
        $entityManager = $this->getEntityManager();
        /** @var CacheEntry|null $entity */
        $entity = $entityManager->getReference(CacheEntry::class, $item->getKey());
        if (!self::isCacheEntryLoaded($entity)) {
            $entity = new CacheEntry();
            $entity->setKey($item->getKey());
            $entityManager->persist($entity);
        }
        $entity->setContent($item->get());
        $entity->setExpiredAt($item->getExpiry());

        return $entity;
    }

    /**
     * @param array<string> $keys
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @return array<CacheEntry>
     */
    private function loadCacheEntriesFromDoctrine(array &$keys): array
    {
        $entityManager = $this->getEntityManager();
        $cacheEntries = [];
        foreach ($keys as $key) {
            $cacheEntry = $entityManager->getReference(CacheEntry::class, $key);
            if (self::isCacheEntryLoaded($cacheEntry)) {
                $cacheEntries[] = $cacheEntry;
            }
        }

        return $cacheEntries;
    }

    /**
     * @param array<string> $keys
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @return array<CacheEntry>
     */
    private function queryCacheEntriesByKeys(array $keys): array
    {
        return $this->getRepository()
            ->createQueryBuilder('c', 'c.key')
            ->andWhere('c.key IN (:keys)')
            ->setParameter('keys', $keys)
            ->getQuery()->getResult();
    }

    private function getRepository(): \Doctrine\Persistence\ObjectRepository
    {
        return $this->doctrine->getRepository(CacheEntry::class);
    }

    private function getEntityManager(): ?EntityManager
    {
        return $this->doctrine->getManagerForClass(CacheEntry::class);
    }

    private static function createItemByCacheEntry(CacheEntry $cache): CacheItem
    {
        $item = new CacheItem($cache->getKey(), $cache->getContent(), $cache->isHit());
        $item->expiresAt($cache->getExpiredAt());

        return $item;
    }

    private static function isCacheEntryLoaded(CacheEntry $cacheEntry): bool
    {
        return !$cacheEntry instanceof Proxy || $cacheEntry->__isInitialized();
    }
}
