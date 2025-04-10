<?php

namespace Company\AutocompleteBundle\Autocomplete;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class Manager.
 */
class Manager
{
    private PropertyAccessorInterface $propertyAccessor;
    private ManagerRegistry $doctrine;

    private ContainerInterface $autocompleteDescriptors;

    /**
     * DescriptorContainer constructor.
     */
    public function __construct(ManagerRegistry $doctrine, PropertyAccessorInterface $propertyAccessor, ContainerInterface $autocompleteDescriptors)
    {
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;

        $this->autocompleteDescriptors = $autocompleteDescriptors;
    }

    public function getEntityClass(string $descriptorId): string
    {
        return $this->getAutocompleteDescriptor($descriptorId)->getClass();
    }

    public function hasAutocompleteDescriptor(string $descriptorId): bool
    {
        return $this->autocompleteDescriptors->has($descriptorId);
    }

    public function createQueryBuilder(string $descriptorId): QueryBuilder
    {
        $descriptor = $this->getAutocompleteDescriptor($descriptorId);

        $queryBuilder = $this
            ->doctrine
            ->getRepository($descriptor->getClass())
            ->createQueryBuilder('o');

        $descriptor->buildQuery($queryBuilder);

        return $queryBuilder;
    }

    public function addKeywordConditionToQuery(QueryBuilder $queryBuilder, string $descriptorId, ?string $value): void
    {
        $searchFields = $this->getSearchFields($queryBuilder, $descriptorId);

        $expressions = [];
        foreach ($searchFields as $key => $searchField) {
            $expressions[] = 'LOWER('.$searchField.') LIKE LOWER(:keywordcondition)';
        }

        foreach ($searchFields as $i => $searchField) {
            $queryBuilder
                ->addSelect(sprintf('CASE WHEN %s LIKE :value THEN 2 WHEN LOWER(%s) LIKE LOWER(:value) THEN 1 ELSE 0 END AS HIDDEN hidden_%d', $searchField, $searchField, $i))
                ->addOrderBy(sprintf('hidden_%d', $i), 'DESC')
                ->addOrderBy($searchField, 'ASC')
                ->setParameter('value', $value.'%');
        }

        $queryBuilder
            ->andWhere('('.implode(') OR (', $expressions).')')
                ->setParameter('keywordcondition', '%'.$value.'%');
    }

    public function addIdConditionToQuery(QueryBuilder $queryBuilder, string $descriptorId, array $ids): void
    {
        $identifier = $this->getIdentifierField($queryBuilder, $descriptorId);

        $queryBuilder
            ->andWhere($identifier.' IN (:ids)')
                ->setParameter('ids', $ids);
    }

    public function createEntity(string $descriptorId, ?string $value): object
    {
        $descriptor = $this->getAutocompleteDescriptor($descriptorId);

        $class = $descriptor->getClass();
        $metadata = $this->getClassMetadata($class);
        $property = $descriptor->getProperty();
        $entity = new $class();

        $metadata->setFieldValue($entity, $property, $value);

        $this->getEntityManager($class)->persist($entity);

        $descriptor->updateCreatedEntity($entity);

        return $entity;
    }

    /**
     * @param array<object> $entities
     */
    public function hydrateEntitiesToSearchResult(array $entities, string $descriptorId, bool $isCreationAllowed = false): array
    {
        $descriptor = $this->getAutocompleteDescriptor($descriptorId);

        $accessor = $this->propertyAccessor;
        $idField = $this->getIdentifier($descriptorId);
        $property = $descriptor->getProperty();
        $displayMethod = $descriptor->getDisplayMethod();

        $result = [];

        foreach ($entities as $entity) {
            $id = $accessor->getValue($entity, $idField);
            if (!$isCreationAllowed && null === $id) {
                continue;
            }

            $result[] = array_merge($descriptor->getAdditionalRecordValues($entity), [
                'id' => $id,
                'text' => null !== $displayMethod
                    ? $displayMethod($entity)
                    : $accessor->getValue($entity, $property),
            ]);
        }

        return $result;
    }

    private function getIdentifierField(QueryBuilder $queryBuilder, string $descriptorId): string
    {
        $identifier = $this->getIdentifier($descriptorId);

        return $this->normalizeQueryField($queryBuilder, $identifier);
    }

    private function getIdentifier(string $descriptorId): string
    {
        $descriptor = $this->getAutocompleteDescriptor($descriptorId);
        $metadata = $this->getClassMetadata($descriptor->getClass());

        $identifier = $metadata->getIdentifier();
        $identifier = reset($identifier);

        return $identifier;
    }

    private function normalizeQueryField(QueryBuilder $queryBuilder, string $field): string
    {
        if (false !== strpos($field, '.')) {
            return $field;
        }

        $ra = $queryBuilder->getRootAliases();
        $ra = reset($ra);

        return $ra.'.'.$field;
    }

    /**
     * @return array<string>
     */
    private function getSearchFields(QueryBuilder $queryBuilder, string $descriptorId): array
    {
        $descriptor = $this->getAutocompleteDescriptor($descriptorId);
        $searchFields = $descriptor->getSearchFields();

        if (is_string($searchFields)) {
            $searchFields = array_map('trim', explode(',', $searchFields));
        }

        $searchFields = (array) $searchFields;

        $result = [];
        foreach ($searchFields as $searchField) {
            $result[] = $this->normalizeQueryField($queryBuilder, $searchField);
        }

        return $result;
    }

    public function getAutocompleteDescriptor(string $descriptorId): AutocompleteDescriptorInterface
    {
        return $this->autocompleteDescriptors->get($descriptorId);
    }

    private function getClassMetadata(string $class): ClassMetadataInfo
    {
        return $this->getEntityManager($class)->getClassMetadata($class);
    }

    private function getEntityManager(string $class): EntityManager
    {
        return $this->doctrine->getManagerForClass($class);
    }
}
