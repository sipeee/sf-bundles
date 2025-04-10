<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

interface FlushEventInterface
{
    public function createLifecycleEntityEvent(object $entity): LifecycleEventInterface;

    public function getEntityManager(): EntityManagerInterface;

    public function getClassMetadata(string $className): ClassMetadata;

    public function getEntityClass(object $entity): string;

    public function getEntityChangeSet(object $entity): array;

    public function isFieldChanged(object $entity, string $fieldName): bool;

    public function getEntityOriginalData(object $entity): array;

    public function getEntityOriginal(object $entity): ?object;

    public function isEntityLoaded(?object $entity): bool;

    public function isCollectionLoaded(Collection $collection): bool;

    public function recomputeSingleEntityChangeSet(object $entity): void;

    public function computeChangeSet(object $entity): void;

    public function persist(object $entity): void;

    public function remove(object $entity): void;

    public function isScheduledForInsert(object $entity): bool;

    public function isScheduledForUpdate(object $entity): bool;

    public function isScheduledForDelete(object $entity): bool;

    public function isEntityScheduled(object $entity): bool;

    public function isEntityManaged(object $entity): bool;

    public function contains(object $entity): bool;

    public function getScheduledEntityInsertions(?string $className = null): array;

    public function getScheduledEntityUpdates(?string $className = null): array;

    public function getScheduledEntityDeletions(?string $className = null): array;

    public function getManagedEntities(?string $className = null): array;

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getScheduledEntityInsertionEvents(?string $className = null): array;

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getScheduledEntityUpdateEvents(?string $className = null): array;

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getScheduledEntityDeletionEvents(?string $className = null): array;

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getManagedEntityEvents(?string $className = null): array;
}
