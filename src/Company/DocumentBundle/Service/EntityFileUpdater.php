<?php

namespace Company\DocumentBundle\Service;

class EntityFileUpdater
{
    private DocumentConfiguration $documentConfiguration;
    private EntityFileFieldCollector $entityFileFieldCollector;
    private FileUploadManager $fileUploadManager;

    public function __construct(
        DocumentConfiguration $documentConfiguration,
        EntityFileFieldCollector $entityFileFieldCollector,
        FileUploadManager $fileUploadManager
    ) {
        $this->entityFileFieldCollector = $entityFileFieldCollector;
        $this->fileUploadManager = $fileUploadManager;
        $this->documentConfiguration = $documentConfiguration;
    }

    public function manageCollectedDocumentFiles(): void
    {
        foreach ($this->entityFileFieldCollector->getCollectedEntityFileFields() as $fileInfo) {
            $this->manageDocumentFileFields($fileInfo);
        }
    }

    private function manageDocumentFileFields(array $fileInfo): void
    {
        $fileUploadManager = $this->fileUploadManager;
        $entity = $fileInfo['entity'];
        $identifier = $fileInfo['identifier'];

        if (EntityFileFieldCollector::REMOVABLE !== $fileInfo['type']) {
            foreach ($fileInfo['uploadedFields'] as $fieldName) {
                $fieldConfig = $this->documentConfiguration->getConfigByEntityAndField($entity, $fieldName);
                $fileUploadManager->removeFilesOfEntityField($entity, $identifier, $fieldConfig);
                $fileUploadManager->createVariantsOfEntityField($entity, $fieldConfig);
            }
        }

        foreach ($fileInfo['removableFields'] as $fieldName) {
            $fieldConfig = $this->documentConfiguration->getConfigByEntityAndField($entity, $fieldName);
            $fileUploadManager->removeFilesOfEntityField($entity, $identifier, $fieldConfig);
        }
    }
}
