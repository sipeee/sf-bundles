<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

class LifecycleEvent implements LifecycleEventInterface
{
    private FlushEventInterface $flushEvent;
    private object $entity;

    private function __construct(object $entity, FlushEventInterface $flushEvent)
    {
        $this->flushEvent = $flushEvent;
        $this->entity = $entity;
    }

    public static function create(object $entity, FlushEventInterface $event): LifecycleEventInterface
    {
        return new self($entity, $event);
    }

    public static function createByDoctrineLifecycleEvent(LifecycleEventArgs $event): LifecycleEventInterface
    {
        return new self($event->getEntity(), FlushEvent::createByDoctrineLifecycleEvent($event));
    }

    public function createLifecycleEntityEvent(object $entity): LifecycleEventInterface
    {
        return LifecycleEvent::create($entity, $this->flushEvent);
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->flushEvent->getEntityManager();
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->flushEvent->getClassMetadata(get_class($this->entity));
    }

    public function getEntityClass(): string
    {
        return $this->flushEvent->getEntityClass($this->entity);
    }

    public function getEntityChangeSet(): array
    {
        return $this->flushEvent->getEntityChangeSet($this->entity);
    }

    public function isFieldChanged(string $fieldName): bool
    {
        return $this->flushEvent->isFieldChanged($this->entity, $fieldName);
    }

    public function getEntityOriginalData(): array
    {
        return $this->flushEvent->getEntityOriginalData($this->entity);
    }

    public function getEntityOriginal(): ?object
    {
        return $this->flushEvent->getEntityOriginal($this->entity);
    }

    public function isEntityLoaded(): bool
    {
        return $this->flushEvent->isEntityLoaded($this->entity);
    }

    public function isAssociationLoaded(string $field): bool
    {
        if (!$this->flushEvent->isEntityLoaded($this->entity)) {
            return false;
        }

        $metadata = $this->getClassMetadata();
        if (!in_array($field, $metadata->getAssociationNames(), true)) {
            throw new \InvalidArgumentException(sprintf('The field "%s" is not an association of the entity "%s". Available associations: %s', $field, $metadata->getName(), implode(', ', $metadata->getAssociationNames())));
        }

        $associationValue = $metadata->getFieldValue($this->entity, $field);

        return $associationValue instanceof Collection
            ? $this->flushEvent->isCollectionLoaded($associationValue)
            : $this->flushEvent->isEntityLoaded($associationValue);
    }

    public function recomputeSingleEntityChangeSet(): void
    {
        $this->flushEvent->recomputeSingleEntityChangeSet($this->entity);
    }

    public function computeChangeSet(): void
    {
        $this->flushEvent->computeChangeSet($this->entity);
    }

    public function persist(): void
    {
        $this->flushEvent->persist($this->entity);
    }

    public function isScheduledForInsert(): bool
    {
        return $this->flushEvent->isScheduledForInsert($this->entity);
    }

    public function isScheduledForUpdate(): bool
    {
        return $this->flushEvent->isScheduledForUpdate($this->entity);
    }

    public function isScheduledForDelete(): bool
    {
        return $this->flushEvent->isScheduledForDelete($this->entity);
    }

    public function isEntityScheduled(): bool
    {
        return $this->flushEvent->isEntityScheduled($this->entity);
    }

    public function isEntityManaged(): bool
    {
        return $this->flushEvent->isEntityManaged($this->entity);
    }

    public function contains(): bool
    {
        return $this->flushEvent->contains($this->entity);
    }
}
