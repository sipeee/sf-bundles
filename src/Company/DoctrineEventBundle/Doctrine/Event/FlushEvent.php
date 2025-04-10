<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Proxy;

abstract class FlushEvent implements FlushEventInterface
{
    private EntityManagerInterface $entityManager;

    private function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function createByDoctrineLifecycleEvent(LifecycleEventArgs $event): FlushEventInterface
    {
        return new OnFlushEvent($event->getEntityManager());
    }

    public static function createByDoctrinePreFlushEvent(PreFlushEventArgs $event): FlushEventInterface
    {
        return new PreFlushEvent($event->getEntityManager());
    }

    public static function createByDoctrineOnFlushEvent(OnFlushEventArgs $event): FlushEventInterface
    {
        return new OnFlushEvent($event->getEntityManager());
    }

    public static function createByDoctrinePostFlushEvent(PostFlushEventArgs $event): FlushEventInterface
    {
        return new PostFlushEvent($event->getEntityManager());
    }

    public function createLifecycleEntityEvent(object $entity): LifecycleEventInterface
    {
        return LifecycleEvent::create($entity, $this);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->getEntityManager()->getClassMetadata($className);
    }

    public function getEntityClass(object $entity): string
    {
        return $this->getClassMetadata(get_class($entity))->getName();
    }

    public function isFieldChanged(object $entity, string $fieldName): bool
    {
        $changeSet = $this->getEntityChangeSet($entity);

        return isset($changeSet[$fieldName]);
    }

    public function getEntityOriginal(object $entity): ?object
    {
        $data = $this->getEntityOriginalData($entity);
        if (empty($data)) {
            return null;
        }

        $metadata = $this->getClassMetadata(get_class($entity));
        $class = $this->getEntityClass($entity);

        $entity = new $class();
        foreach ($data as $field => $value) {
            $metadata->setFieldValue($entity, $field, $value);
        }

        return $entity;
    }

    public function isEntityLoaded(?object $entity): bool
    {
        return null !== $entity && self::isEntityLoadedStatically($entity);
    }

    public function isCollectionLoaded(Collection $collection): bool
    {
        return !$collection instanceof PersistentCollection || $collection->isInitialized();
    }

    public function persist(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    public function isScheduledForInsert(object $entity): bool
    {
        return $this->getUnitOfWork()->isScheduledForInsert($entity);
    }

    public function isScheduledForUpdate(object $entity): bool
    {
        return $this->getUnitOfWork()->isScheduledForUpdate($entity);
    }

    public function isScheduledForDelete(object $entity): bool
    {
        return $this->getUnitOfWork()->isScheduledForDelete($entity);
    }

    public function isEntityScheduled(object $entity): bool
    {
        return $this->getUnitOfWork()->isEntityScheduled($entity);
    }

    public function isEntityManaged(object $entity): bool
    {
        return self::isEntityLoadedStatically($entity) && $this->getUnitOfWork()->isInIdentityMap($entity) && !$this->isScheduledForDelete($entity);
    }

    public function contains(object $entity): bool
    {
        return $this->getEntityManager()->contains($entity);
    }

    public function getScheduledEntityInsertions(?string $className = null): array
    {
        if (null !== $className) {
            return $this->filterEntitiesByClassName($className, $this->getUnitOfWork()->getScheduledEntityInsertions());
        }

        return $this->getUnitOfWork()->getScheduledEntityInsertions();
    }

    public function getScheduledEntityDeletions(?string $className = null): array
    {
        if (null !== $className) {
            return $this->filterEntitiesByClassName($className, $this->getUnitOfWork()->getScheduledEntityDeletions());
        }

        return $this->getUnitOfWork()->getScheduledEntityDeletions();
    }

    public function getManagedEntities(?string $className = null): array
    {
        $managedEntityMap = $this->getUnitOfWork()->getIdentityMap();
        if (null !== $className) {
            $metadata = $this->getClassMetadata($className);

            return $this->filterManagedEntities($this->filterEntitiesByClassName(
                $className,
                $managedEntityMap[$metadata->rootEntityName] ?? []
            ));
        }
        $managedEntities = [];
        foreach ($managedEntityMap as $entities) {
            $managedEntities += $this->filterManagedEntities($entities);
        }

        return $managedEntities;
    }

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getScheduledEntityInsertionEvents(?string $className = null): array
    {
        return $this->createEventsFromEntities($this->getScheduledEntityInsertions($className));
    }

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getScheduledEntityUpdateEvents(?string $className = null): array
    {
        return $this->createEventsFromEntities($this->getScheduledEntityUpdates($className));
    }

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getScheduledEntityDeletionEvents(?string $className = null): array
    {
        return $this->createEventsFromEntities($this->getScheduledEntityDeletions($className));
    }

    /**
     * @return array<LifecycleEventInterface>
     */
    public function getManagedEntityEvents(?string $className = null): array
    {
        return $this->createEventsFromEntities($this->getManagedEntities($className));
    }

    /**
     * @param array<object> $entities
     *
     * @return array<LifecycleEventInterface>
     */
    protected function createEventsFromEntities(array $entities): array
    {
        $events = [];
        foreach ($entities as $hash => $entity) {
            $events[$hash] = LifecycleEvent::create($entity, $this);
        }

        return $events;
    }

    /**
     * @return array<$className>
     */
    protected function filterManagedEntities(array $entities): array
    {
        $uow = $this->getUnitOfWork();

        $filteredEntities = [];
        foreach ($entities as $hash => $entity) {
            if ( !$uow->isScheduledForDelete($entity)) {
                $filteredEntities[$hash] = $entity;
            }
        }

        return $filteredEntities;
    }

    /**
     * @return array<$className>
     */
    protected function filterEntitiesByClassName(string $className, array $entities): array
    {
        $filteredEntities = [];
        foreach ($entities as $hash => $entity) {
            $metadata = $this->getClassMetadata(get_class($entity));
            if (is_a($metadata->getName(), $className, true)) {
                $filteredEntities[$hash] = $entity;
            }
        }

        return $filteredEntities;
    }

    protected function getUnitOfWork(): UnitOfWork
    {
        return $this->getEntityManager()->getUnitOfWork();
    }

    private function isEntityLoadedStatically(object $entity): bool
    {
        return !$entity instanceof Proxy || $entity->__isInitialized();
    }
}
