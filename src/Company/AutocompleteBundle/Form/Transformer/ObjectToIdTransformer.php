<?php

namespace Company\AutocompleteBundle\Form\Transformer;

use Company\AutocompleteBundle\Autocomplete\Manager as AutocompleteManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ObjectToIdTransformer implements DataTransformerInterface
{
    private AutocompleteManager $manager;
    private PropertyAccessorInterface $propertyAccessor;

    private string $descriptorId;
    private bool $isCreationAllowed;
    private bool $multiple;

    public function __construct(
        AutocompleteManager $manager,
        string $descriptorId,
        bool $multiple,
        string $isCreationAllowed,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->manager = $manager;
        $this->descriptorId = $descriptorId;
        $this->isCreationAllowed = $isCreationAllowed;
        $this->multiple = $multiple;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?object
    {
        if (empty($value)) {
            return $this->multiple
                ? $this->getEntities([])
                : null;
        }

        $values = json_decode($value, true);

        if (!is_array($values)) {
            $values = (array) $values;
        }

        if (($this->multiple)) {
            return $this->getEntities($values);
        } else {
            $values = $this->getEntities($values);

            return !$values->isEmpty()
                ? $values->first()
                : null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entityOrCollection): ?string
    {
        if (null === $entityOrCollection) {
            return $this->multiple
                ? json_encode([])
                : null;
        }

        $entities = $this->normalizeEntityValues($entityOrCollection);

        $choices = $this->getChoices($entities);

        return json_encode($choices);
    }

    private function getChoices($entities): array
    {
        return $this->manager->hydrateEntitiesToSearchResult($entities, $this->descriptorId, $this->isCreationAllowed);
    }

    private function getEntities(array $ids): Collection
    {
        $collection = new ArrayCollection();

        $ids = $this->normalizeInput($ids);

        if (empty($ids)) {
            return $collection;
        }

        $entities = $this->findExistingEntities($ids);
        if ($this->isCreationAllowed) {
            $creatableEntities = $this->createableEntities($ids);
            $entities = array_merge($entities, $creatableEntities);
        }

        foreach ($entities as $entity) {
            $collection->add($entity);
        }

        return $collection;
    }

    /**
     * @param array|object $entities
     */
    private function normalizeEntityValues($entities): array
    {
        $class = $this->manager->getEntityClass($this->descriptorId);

        if ($this->multiple) {
            if (is_object($entities) && is_a($entities, $class)) {
                throw new \InvalidArgumentException('A multiple selection must be passed a collection not a single value. Make sure that form option "multiple=false" is set for many-to-one relation and "multiple=true" is set for many-to-many or one-to-many relations.');
            } elseif ($entities instanceof \Traversable) {
                return iterator_to_array($entities);
            } elseif (is_array($entities)) {
                return $entities;
            }
            throw new \InvalidArgumentException('A multiple selection must be passed a collection not a single value. Make sure that form option "multiple=false" is set for many-to-one relation and "multiple=true" is set for many-to-many or one-to-many relations.');
        } else {
            if (is_object($entities) && is_a($entities, $class)) {
                return [$entities];
            } elseif (is_object($entities) && $entities instanceof \Traversable) {
                throw new \InvalidArgumentException('A single selection must be passed must not a collection. Make sure that form option "multiple=false" is set for many-to-one relation and "multiple=true" is set for many-to-many or one-to-many relations.');
            } elseif (is_array($entities)) {
                throw new \InvalidArgumentException('A single selection must be passed must not an array. Make sure that form option "multiple=false" is set for many-to-one relation and "multiple=true" is set for many-to-many or one-to-many relations.');
            }

            return [$entities];
        }
    }

    /**
     * @return array
     */
    private function normalizeInput(array $ids)
    {
        $existingIds = [];
        $creatableIds = [];

        foreach ($ids as $key => $props) {
            if (is_scalar($props)) {
                $props = [
                    'id' => $props,
                    'text' => $props,
                ];
            }

            if ($this->isCreationAllowed && 'create' === ($props['id'] ?? '')) {
                $creatableIds[$props['text'] ?? $props['id']] = 'create';
            } elseif (preg_match('/^\\d+$/', ''.($props['id'] ?? ''))) {
                $existingIds[$props['id']] = $props['text'] ?? $props['id'];
            }
        }

        $result = [];

        foreach ($existingIds as $id => $value) {
            $result[] = [
                'id' => $id,
                'text' => $value,
            ];
        }

        foreach ($creatableIds as $value => $id) {
            $result[] = [
                'id' => $id,
                'text' => $value,
            ];
        }

        return $result;
    }

    private function findExistingEntities(array $ids = [])
    {
        $idValues = [];

        foreach ($ids as $id) {
            if (preg_match('/^\\d+$/', ''.$id['id'])) {
                $idValues[] = (int) ($id['id']);
            }
        }

        if (empty($idValues)) {
            return [];
        }

        $qb = $this->manager->createQueryBuilder($this->descriptorId);

        $this->manager->addIdConditionToQuery($qb, $this->descriptorId, $ids);

        $entities = $qb->getQuery()->getResult();

        if (count($entities) !== count($idValues)) {
            throw new TransformationFailedException(sprintf('form.error.bad.selection'));
        }

        return $entities;
    }

    private function createableEntities(array $ids = [])
    {
        $result = [];

        foreach ($ids as $id) {
            if ('create' !== $id['id']) {
                continue;
            }

            $result[] = $this->manager->createEntity($this->descriptorId, $id['value']);
        }

        return $result;
    }
}
