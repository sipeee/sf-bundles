<?php

namespace Company\DocumentBundle\Utility;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\UnitOfWork;

class UnitOfWorkUtility
{
    /**
     * @return Collection|object[]
     */
    public static function getManagedEntitiesFrom(UnitOfWork $unitOfWork): Collection
    {
        $managedEntities = new ArrayCollection();
        foreach ($unitOfWork->getIdentityMap() as $class => $entities) {
            foreach ($entities as $entity) {
                if ($unitOfWork->isInIdentityMap($entity) && !$unitOfWork->isScheduledForDelete($entity)) {
                    $managedEntities[] = $entity;
                }
            }
        }

        return $managedEntities;
    }

    /**
     * @return Collection|object[]
     */
    public static function getManagedAndCreatableEntitiesFrom(UnitOfWork $unitOfWork): Collection
    {
        $entities = self::getManagedEntitiesFrom($unitOfWork);

        self::addEntitiesToCollection($unitOfWork->getScheduledEntityInsertions(), $entities);

        return $entities;
    }

    private static function addEntitiesToCollection(array $entities, Collection $collection): void
    {
        foreach ($entities as $entity) {
            $collection[] = $entity;
        }
    }
}
