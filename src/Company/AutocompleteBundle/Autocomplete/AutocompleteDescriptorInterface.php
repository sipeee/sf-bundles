<?php

namespace Company\AutocompleteBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface AutocompleteDescriptorInterface.
 */
interface AutocompleteDescriptorInterface
{
    public function getClass(): string;

    public function getProperty(): string;

    public function getDisplayMethod(): ?\Closure;

    /**
     * @return array<string>
     */
    public function getSearchFields(): array;

    public function buildQuery(QueryBuilder $queryBuilder): void;

    public function updateCreatedEntity(object $entity): void;

    public function getAdditionalRecordValues(object $entity): array;
}
