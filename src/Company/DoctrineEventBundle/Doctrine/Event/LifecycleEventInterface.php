<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

interface LifecycleEventInterface
{
    public function createLifecycleEntityEvent(object $entity): LifecycleEventInterface;

    public function getEntity(): object;

    public function getEntityManager(): EntityManagerInterface;

    public function getClassMetadata(): ClassMetadata;

    public function getEntityClass(): string;

    public function getEntityChangeSet(): array;

    public function isFieldChanged(string $fieldName): bool;

    public function getEntityOriginalData(): array;

    public function getEntityOriginal(): ?object;

    public function isEntityLoaded(): bool;

    public function isAssociationLoaded(string $field): bool;

    public function recomputeSingleEntityChangeSet(): void;

    public function computeChangeSet(): void;

    public function persist(): void;

    public function isScheduledForInsert(): bool;

    public function isScheduledForUpdate(): bool;

    public function isScheduledForDelete(): bool;

    public function isEntityScheduled(): bool;

    public function isEntityManaged(): bool;

    public function contains(): bool;
}
