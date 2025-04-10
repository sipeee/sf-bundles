<?php

namespace Company\DocumentBundle\Service;

use Company\DocumentBundle\Presentation\DocumentFormData;
use Company\DocumentBundle\Presentation\DocumentVariant;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class MetadataService
{
    private ManagerRegistry $managerRegistry;
    private PropertyAccessorInterface $accessor;

    public function __construct(ManagerRegistry $managerRegistry, PropertyAccessorInterface $accessor)
    {
        $this->managerRegistry = $managerRegistry;
        $this->accessor = $accessor;
    }

    public function getIdentifierOf(object $entity): string
    {
        $metadata = $this->getMetadataOf($entity);

        $ids = $metadata->getIdentifierValues($entity);

        return implode('-', $ids);
    }

    public function getFileName(object $entity, array $config): ?string
    {
        return $this->getFieldValue($entity, $config['filename_field']);
    }

    public function setFileName(object $entity, array $config, ?string $value): self
    {
        return $this->setFieldValue($entity, $config['filename_field'], $value);
    }

    /**
     * @return Collection|DocumentVariant[]|null
     */
    public function getVariants(object $entity, array $config): ?Collection
    {
        return $this->getFieldValue($entity, $config['variant_field']);
    }

    /**
     * @param Collection|DocumentVariant[]|null $value
     */
    public function setVariants(object $entity, array $config, ?Collection $value): self
    {
        return $this->setFieldValue($entity, $config['variant_field'], $value);
    }

    public function getTitle(object $entity, array $config): ?string
    {
        return $this->getFieldValue($entity, $config['title_field']);
    }

    public function setTitle(object $entity, array $config, ?string $value): self
    {
        return $this->setFieldValue($entity, $config['title_field'], $value);
    }

    public function getFormData(object $entity, array $config): ?DocumentFormData
    {
        return $this->getFieldValue($entity, $config['form_data_field']);
    }

    public function setFormData(object $entity, array $config, ?DocumentFormData $value): self
    {
        return $this->setFieldValue($entity, $config['form_data_field'], $value);
    }

    public function getMetadataOf(object $entity): ClassMetadata
    {
        $className = get_class($entity);

        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($className);

        return $manager->getClassMetadata($className);
    }

    private function getFieldValue(object $entity, ?string $field)
    {
        if (null === $field) {
            return null;
        }

        $metadata = $this->getMetadataOf($entity);

        return $metadata->hasField($field)
            ? $metadata->getFieldValue($entity, $field)
            : $this->accessor->getValue($entity, $field);
    }

    private function setFieldValue(object $entity, ?string $field, $value): MetadataService
    {
        if (null === $field) {
            return $this;
        }

        $metadata = $this->getMetadataOf($entity);

        if ($metadata->hasField($field)) {
            $metadata->setFieldValue($entity, $field, $value);
        } else {
            $this->accessor->setValue($entity, $field, $value);
        }

        return $this;
    }
}
