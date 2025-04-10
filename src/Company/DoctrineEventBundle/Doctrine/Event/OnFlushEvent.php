<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

class OnFlushEvent extends FlushEvent
{
    public function getEntityChangeSet(object $entity): array
    {
        return $this->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
    }

    public function getEntityOriginalData(object $entity): array
    {
        if (!$this->isEntityManaged($entity) && !$this->isScheduledForDelete($entity)) {
            return [];
        }

        $metadata = $this->getClassMetadata(get_class($entity));
        $changeSet = $this->getEntityChangeSet($entity);
        $fields = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());

        $data = [];
        foreach ($fields as $fieldName) {
            $data[$fieldName] = $changeSet[$fieldName][0] ?? $metadata->getFieldValue($entity, $fieldName);
        }

        return $data;
    }

    public function getScheduledEntityUpdates(?string $className = null): array
    {
        if (null !== $className) {
            return $this->filterEntitiesByClassName($className, $this->getUnitOfWork()->getScheduledEntityUpdates());
        }

        return $this->getUnitOfWork()->getScheduledEntityUpdates();
    }

    public function recomputeSingleEntityChangeSet(object $entity): void
    {
        if ($this->isEntityManaged($entity)) {
            $this->getUnitOfWork()->recomputeSingleEntityChangeSet($this->getClassMetadata(get_class($entity)), $entity);
        }
    }

    public function computeChangeSet(object $entity): void
    {
        if ($this->isEntityManaged($entity)) {
            $this->computeChangeSetInternally($entity);
        }
    }

    public function persist(object $entity): void
    {
        parent::persist($entity);

        if ($this->isScheduledForInsert($entity)) {
            $this->computeChangeSetInternally($entity);
        }
    }

    private function computeChangeSetInternally(object $entity): void
    {
        $this->getUnitOfWork()->computeChangeSet($this->getClassMetadata(get_class($entity)), $entity);
    }
}
