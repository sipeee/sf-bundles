<?php

namespace Company\DocumentBundle\Service;

use Company\DocumentBundle\Presentation\DocumentFormData;
use Company\DocumentBundle\Presentation\DocumentVariant;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class EntityFileLoader
{
    private DocumentConfiguration $config;
    private PathCalculator $fileDataCalculator;
    private MetadataService $metadataService;

    public function __construct(
        DocumentConfiguration $config,
        PathCalculator $pathCalculator,
        MetadataService $metadataService
    ) {
        $this->config = $config;
        $this->fileDataCalculator = $pathCalculator;
        $this->metadataService = $metadataService;
    }

    public function loadDocumentsOfEntity(object $entity)
    {
        $config = $this->config->getConfigByEntity($entity);

        foreach ($config as $fieldConfig) {
            $this->loadDocumentVariant($entity, $fieldConfig);
            $this->loadDocumentData($entity, $fieldConfig);
        }
    }

    private function loadDocumentVariant(object $entity, array $fieldConfig): void
    {
        $variantCollection = new ArrayCollection();
        foreach ($fieldConfig['variants'] as $variant => $_) {
            $possibleVariants = ($fieldConfig['original_variant'] !== $variant) ? [$variant, $fieldConfig['original_variant']] : [$fieldConfig['original_variant']];
            foreach ($possibleVariants as $possibleVariant) {
                $documentFilePath = $this->fileDataCalculator->calculateFilePath($fieldConfig, $entity, $possibleVariant);

                try {
                    $documentFile = new File($documentFilePath);
                    $documentFileUrl = $this->fileDataCalculator->calculateWebPath($fieldConfig, $entity, $possibleVariant, $documentFile);

                    break;
                } catch (FileNotFoundException $e) {
                    $documentFile = null;
                    $documentFileUrl = null;
                }
            }

            $variantCollection[$variant] = new DocumentVariant($documentFile, $documentFileUrl);
        }

        $this->metadataService->setVariants($entity, $fieldConfig, $variantCollection);
    }

    private function loadDocumentData(object $entity, array $fieldConfig)
    {
        $data = new DocumentFormData();

        $metadataService = $this->metadataService;
        $variantCollection = $metadataService->getVariants($entity, $fieldConfig);

        $original = $variantCollection[$fieldConfig['original_variant']];
        $thumbnail = $variantCollection[$fieldConfig['thumbnail_variant']];

        $data->setFile(
            (null !== $thumbnail->getFile())
                ? $thumbnail->getFile()
                : $original->getFile()
        );
        $data->setUrl($thumbnail->getUrl());
        $data->setOriginalFile($original->getFile());
        $data->setOriginalUrl($original->getUrl());
        $data->setTitle($metadataService->getTitle($entity, $fieldConfig));
        $data->setRemovable(false);
        $data->setIdentifier($metadataService->getIdentifierOf($entity));

        $this->metadataService->setFormData($entity, $fieldConfig, $data);
    }
}
