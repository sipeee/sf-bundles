<?php

namespace Company\DocumentBundle\Service;

use Company\DocumentBundle\Presentation\DocumentFormData;
use Company\DocumentBundle\Utility\StringUtility;
use Company\DocumentBundle\Utility\UnitOfWorkUtility;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EntityDataUpdater
{
    private EntityFileFieldCollector $entityFileFieldCollector;
    private DocumentConfiguration $documentConfiguration;
    private MetadataService $metadataService;

    public function __construct(
        EntityFileFieldCollector $uploadedEntityFieldCollector,
        DocumentConfiguration $documentConfiguration,
        MetadataService $metadataService
    ) {
        $this->entityFileFieldCollector = $uploadedEntityFieldCollector;
        $this->documentConfiguration = $documentConfiguration;
        $this->metadataService = $metadataService;
    }

    public function update(UnitOfWork $unitOfWork)
    {
        foreach ($this->entityFileFieldCollector->getCollectedEntityFileFields() as $fieldInfo) {
            $this->updateEntityFileNames($unitOfWork, $fieldInfo);
        }

        foreach (UnitOfWorkUtility::getManagedAndCreatableEntitiesFrom($unitOfWork) as $entity) {
            foreach ($this->documentConfiguration->getConfigByEntity($entity) as $fieldConfig) {
                $this->updateEntityTitle($unitOfWork, $entity, $fieldConfig);
            }
        }
    }

    private function updateEntityFileNames(UnitOfWork $unitOfWork, array $fieldInfo): void
    {
        $documentConfiguration = $this->documentConfiguration;
        $metadataService = $this->metadataService;

        if (EntityFileFieldCollector::REMOVABLE === $fieldInfo['type']) {
            return;
        }

        $entity = $fieldInfo['entity'];
        foreach ($fieldInfo['uploadedFields'] as $fileNameField) {
            $fieldConfig = $documentConfiguration->getConfigByEntityAndField($entity, $fileNameField);
            $this->updateVariantFileName($entity, $fieldConfig);
        }
        foreach ($fieldInfo['removableFields'] as $fileNameField) {
            $fieldConfig = $documentConfiguration->getConfigByEntityAndField($entity, $fileNameField);
            $this->removeVariantFileName($entity, $fieldConfig);
        }

        if (EntityFileFieldCollector::REMOVABLE !== $fieldInfo['type']) {
            $unitOfWork->recomputeSingleEntityChangeSet($metadataService->getMetadataOf($entity), $entity);
        }
    }

    private function updateEntityTitle(UnitOfWork $unitOfWork, object $entity, array $fieldConfig)
    {
        $metadataService = $this->metadataService;
        $formData = $metadataService->getFormData($entity, $fieldConfig);
        $title = $metadataService->getTitle($entity, $fieldConfig);

        if ($formData->getTitle() !== $title) {
            $metadataService->setTitle($entity, $fieldConfig, $formData->getTitle());

            $unitOfWork->recomputeSingleEntityChangeSet($metadataService->getMetadataOf($entity), $entity);
        }
    }

    private function updateVariantFileName($entity, array $fieldConfig): void
    {
        $metadataService = $this->metadataService;

        $formData = $metadataService->getFormData($entity, $fieldConfig);

        if ($formData->getFile() instanceof UploadedFile) {
            $fileName = $this->getCorrectedFileUploadName($formData, $entity);

            $metadataService->setFileName($entity, $fieldConfig, $fileName);
        }
    }

    private function removeVariantFileName($entity, array $fieldConfig)
    {
        $this->metadataService->setFileName($entity, $fieldConfig, null);
    }

    private function getCorrectedFileUploadName(DocumentFormData $formData, object $entity): string
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $formData->getFile();
        $fileName = $this->getCorrectedFileUploadBaseName($uploadedFile, $entity);

        $extension = $formData->getExtension();
        if (!empty($extension)) {
            $fileName .= '.'.$extension;
        }

        return $fileName;
    }

    private function getCorrectedFileUploadBaseName(UploadedFile $file, object $entity): string
    {
        $fileName = preg_replace('/\\.[A-Za-z]+$/', '', $file->getClientOriginalName());
        $fileName = StringUtility::slugify($fileName);
        if (empty($fileName)) {
            $fileName = $this->metadataService->getIdentifierOf($entity);
        }

        return $fileName;
    }
}
