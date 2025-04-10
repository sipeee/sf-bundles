<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

class PreFlushEvent extends FlushEvent
{
    public function getEntityChangeSet(object $entity): array
    {
        $originalData = $this->getEntityOriginalData($entity);
        $metadata = $this->getClassMetadata(get_class($entity));
        $changeSet = [];

        foreach ($originalData as $fieldName => $originalValue) {
            $value = $metadata->getFieldValue($entity, $fieldName);
            if ($originalValue !== $value) {
                $changeSet[$fieldName] = [$originalValue, $value];
            }
        }

        return $changeSet;
    }

    public function getEntityOriginalData(object $entity): array
    {
        if (!$this->isEntityManaged($entity) && !$this->isScheduledForDelete($entity)) {
            return [];
        }

        $originalData = $this->getUnitOfWork()->getOriginalEntityData($entity);
        if (empty($originalData)) {
            return [];
        }
        $metadata = $this->getClassMetadata(get_class($entity));

        $data = [];
        $fieldNames = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());
        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $originalData)) {
                $data[$fieldName] = $originalData[$fieldName];
            }
        }

        return $data;
    }

    public function getScheduledEntityUpdates(?string $className = null): array
    {
        $scheduledEntityUpdates = [];
        foreach ($this->getManagedEntities($className) as $hash => $entity) {
            $changeSet = $this->getEntityChangeSet($entity);
            if (!empty($changeSet)) {
                $scheduledEntityUpdates[$hash] = $entity;
            }
        }

        return $scheduledEntityUpdates;
    }

    public function recomputeSingleEntityChangeSet(object $entity): void
    {
        self::throwCalculationException('recomputeSingleEntityChangeSet');
    }

    public function computeChangeSet(object $entity): void
    {
        self::throwCalculationException('recomputeSingleEntityChangeSet');
    }

    private static function throwCalculationException(string $methodName)
    {
        throw new \BadMethodCallException(sprintf('The method "%s" is not available in the PreFlush and PostFlush events. Calculations are happened in the OnFlush event', $methodName));
    }
}
