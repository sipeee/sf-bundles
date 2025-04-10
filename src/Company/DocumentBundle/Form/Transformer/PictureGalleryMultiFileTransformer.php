<?php

namespace Company\DocumentBundle\Form\Transformer;

use Company\DocumentBundle\Presentation\DocumentFormData;
use Company\DocumentBundle\Service\DocumentConfiguration;
use Company\DocumentBundle\Service\MetadataService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureGalleryMultiFileTransformer implements DataTransformerInterface
{
    private DocumentConfiguration $documentConfiguration;
    private MetadataService $metadataService;

    private string $entityClass;
    private bool $allowAdd;
    private string $filenameField;
    private ?array $formEntities = null;

    public function __construct(
        DocumentConfiguration $documentConfiguration,
        MetadataService $metadataService,
        string $entityClass,
        string $filenameField,
        bool $allowAdd
    ) {
        $this->metadataService = $metadataService;
        $this->entityClass = $entityClass;
        $this->filenameField = $filenameField;
        $this->allowAdd = $allowAdd;
        $this->documentConfiguration = $documentConfiguration;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function transform($value)
    {
        if (null === $value) {
            $value = new ArrayCollection();
        }

        $value = (array) $this->convertToFormEntities($value);

        return ($this->allowAdd)
            ? [
                'files' => $value,
                'multiFileLastOffset' => (string) $this->getLastIndexOf($value),
                'multiFiles' => [
                    0 => null,
                ],
            ]
            : [
                'files' => $value,
            ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function reverseTransform($value)
    {
        /** @var array|DocumentFormData[] $formDatas */
        $formDatas = $value['files'];
        if ($this->allowAdd) {
            /** @var int $offset */
            $offset = $value['multiFileLastOffset'];
            /** @var array|array[]|(null|UploadedFile)[][] $multiFiles */
            $multiFiles = (array) ($value['multiFiles']);
            foreach ($multiFiles as $uploadedFiles) {
                foreach ((array) $uploadedFiles as $uploadedFile) {
                    ++$offset;

                    if (!($uploadedFile instanceof UploadedFile) || !\array_key_exists($offset, $formDatas)) {
                        continue;
                    }

                    if (empty($formDatas[$offset])) {
                        $formDatas[$offset] = new DocumentFormData();
                    }

                    if (null === ($formDatas[$offset])->getFile()) {
                        ($formDatas[$offset])->setFile($uploadedFile);
                    }
                }
            }
        }

        return $this->convertBackFormDatasToEntities($formDatas);
    }

    /**
     * @return array|DocumentFormData[]
     */
    private function convertToFormEntities(Collection $entities): array
    {
        $documentConfiguration = $this->documentConfiguration;
        $metadataService = $this->metadataService;
        $this->formEntities = [];
        $resultFormDatas = [];
        foreach ($entities as $entity) {
            $fieldConfig = $documentConfiguration->getConfigByEntityAndField($entity, $this->filenameField);
            $formData = $metadataService->getFormData($entity, $fieldConfig);
            $identifier = $metadataService->getIdentifierOf($entity);
            if (!empty($identifier)) {
                $this->formEntities[$identifier] = $entity;
            }

            $resultFormDatas[] = $formData;
        }

        return $resultFormDatas;
    }

    /**
     * @param array|DocumentFormData[] $formDatas
     */
    private function convertBackFormDatasToEntities(array $formDatas): Collection
    {
        $documentConfiguration = $this->documentConfiguration;
        $metadataService = $this->metadataService;
        $entityClass = $this->entityClass;
        $entities = new ArrayCollection();
        foreach ($formDatas as $formData) {
            if (null === $formData) {
                continue;
            }

            $identifier = $formData->getIdentifier();
            if (!empty($identifier) && isset($this->formEntities[$identifier])) {
                $entity = $this->formEntities[$identifier];
            } else {
                $entity = new $entityClass();
            }
            $fieldConfig = $documentConfiguration->getConfigByEntityAndField($entity, $this->filenameField);
            $metadataService->setFormData($entity, $fieldConfig, $formData);

            $entities[] = $entity;
        }

        return $entities;
    }

    private function getLastIndexOf(array $values): int
    {
        $keys = array_keys($values);

        return !empty($keys)
            ? end($keys)
            : -1;
    }
}
