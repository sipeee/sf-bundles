<?php

namespace Company\DocumentBundle\Service;

use Company\DocumentBundle\Presentation\DocumentFormData;
use Company\DocumentBundle\Utility\UnitOfWorkUtility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EntityFileFieldCollector
{
    public const CREATABLE = 0;
    public const UPDATABLE = 1;
    public const REMOVABLE = 2;

    private DocumentConfiguration $config;
    private MetadataService $metadataService;

    /** @var array[]|Collection */
    private Collection $entityFileFields;

    public function __construct(
        DocumentConfiguration $config,
        MetadataService $metadataService
    ) {
        $this->config = $config;
        $this->metadataService = $metadataService;

        $this->entityFileFields = new ArrayCollection();
    }

    public function collectEntityFileFields(UnitOfWork $unitOfWork): void
    {
        foreach (UnitOfWorkUtility::getManagedEntitiesFrom($unitOfWork) as $document) {
            $this->addEntityFileFields($document, self::UPDATABLE);
        }
        foreach ($unitOfWork->getScheduledEntityInsertions() as $document) {
            $this->addEntityFileFields($document, self::CREATABLE);
        }
        foreach ($unitOfWork->getScheduledEntityDeletions() as $document) {
            $this->addEntityFileFields($document, self::REMOVABLE);
        }
    }

    /**
     * @return array[]|\Iterator
     */
    public function getCollectedEntityFileFields(): \Iterator
    {
        return $this->entityFileFields->getIterator();
    }

    public function clear(): void
    {
        $this->entityFileFields->clear();
    }

    private function addEntityFileFields(object $entity, int $type): void
    {
        $uploadedFields = [];
        $removableFields = [];
        foreach ($this->config->getConfigByEntity($entity) as $fileNameField => $fieldConfig) {
            if (self::REMOVABLE !== $type && $this->isUploadedFileField($entity, $fieldConfig)) {
                $uploadedFields[] = $fileNameField;
            }
            if (self::CREATABLE !== $type && $this->isRemovableFileField($entity, $fieldConfig, self::REMOVABLE === $type)) {
                $removableFields[] = $fileNameField;
            }
        }

        if (!empty($uploadedFields) || !empty($removableFields)) {
            $this->entityFileFields[] = [
                'type' => $type,
                'identifier' => $this->metadataService->getIdentifierOf($entity),
                'entity' => $entity,
                'uploadedFields' => $uploadedFields,
                'removableFields' => $removableFields,
            ];
        }
    }

    private function isUploadedFileField(object $document, array $fieldConfig): bool
    {
        /** @var DocumentFormData $formData */
        $formData = $this->metadataService->getFormData($document, $fieldConfig);

        if ($formData->isRemovable()) {
            return false;
        }

        $file = $formData->getFile();
        try {
            if ($file instanceof UploadedFile && $file->getRealPath()) {
                return true;
            }
        } catch (\Throwable $exception) {
        }

        return false;
    }

    private function isRemovableFileField(object $document, array $fieldConfig, bool $forced): bool
    {
        $formData = $this->metadataService->getFormData($document, $fieldConfig);
        $hasToRemove = $forced || $formData->isRemovable();
        if (!$hasToRemove) {
            return false;
        }

        $variants = $this->metadataService->getVariants($document, $fieldConfig);
        $originalVariant = $variants[$fieldConfig['original_variant']];

        return null !== $originalVariant->getFile();
    }
}
