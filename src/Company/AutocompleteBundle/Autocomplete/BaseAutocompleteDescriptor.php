<?php

namespace Company\AutocompleteBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;
use PropertyManager\Entity\Person;

class BaseAutocompleteDescriptor implements AutocompleteDescriptorInterface
{
    protected string $class;
    protected string $property;
    /** @var array<string> */
    protected array $searchFields;

    /**
     * @param array<string>|string|null $searchFields
     */
    public function __construct(string $class = null, string $property = null, $searchFields = null)
    {
        $this->class = $class;
        $this->property = $property;

        if (null !== $searchFields) {
            $this->searchFields = is_string($searchFields)
                ? array_map('trim', explode(',', $searchFields))
                : (array) $searchFields;
        } else {
            $this->searchFields = (array) $property;
        }
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getDisplayMethod(): ?\Closure
    {
        return null;
    }

    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    public function buildQuery(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->distinct(true);
    }

    public function updateCreatedEntity(object $entity): void
    {
        // No implementation
    }

    /**
     * @param Person $entity
     */
    public function getAdditionalRecordValues(object $entity): array
    {
        return [];
    }
}
