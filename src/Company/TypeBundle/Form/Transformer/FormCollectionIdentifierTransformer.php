<?php

namespace Company\TypeBundle\Form\Transformer;

use Biplane\EnumBundle\Enumeration\EnumInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FormCollectionIdentifierTransformer implements DataTransformerInterface
{
    private ManagerRegistry $managerRegistry;
    private PropertyAccessorInterface $accessor;

    /** @var array|string[] */
    private array $uniqueFields;
    /** @var array|string[] */
    private array $allFields;

    /** @var array|\Traversable */
    private $originalValue;

    /**
     * @param array|string[] $uniqueFields
     * @param array|string[] $allFields
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        PropertyAccessorInterface $accessor,
        array $uniqueFields,
        array $allFields
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->accessor = $accessor;
        $this->uniqueFields = $uniqueFields;
        $this->allFields = $allFields;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (self::isTraversable($value)) {
            $this->originalValue = $value;

            $value = $this->cloneCollection($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!self::isTraversable($value)) {
            return $value;
        }

        foreach ($value as $key => $object) {
            $index = $this->getObjectIndexInCollection($object, $this->originalValue);
            if (null !== $index) {
                $valueItem = $this->originalValue[$index];
                unset($this->originalValue[$index]);
            } else {
                $valueItem = $this->createEmptyCopy($object);
            }

            $value[$key] = $this->copyFieldsOfObject($object, $valueItem);
        }

        $this->originalValue = $value;

        return $value;
    }

    private function getObjectIndexInCollection($object, $collection)
    {
        foreach ($collection as $key => $record) {
            if ($this->areUniqueFieldsEquals($object, $record)) {
                return $key;
            }
        }

        return null;
    }

    private function cloneCollection($collection)
    {
        $result = self::cloneData($collection);

        foreach ($collection as $key => $value) {
            $data = self::createEmptyCopy($value);

            $result[$key] = $this->copyFieldsOfObject($value, $data);
        }

        return $result;
    }

    private function copyFieldsOfObject($sourceObject, $targetObject)
    {
        foreach ($this->allFields as $field) {
            $fieldValue = $this->getValueOfObject($sourceObject, $field);

            $targetObject = $this->setValueOfObject($targetObject, $field, $fieldValue);
        }

        return $targetObject;
    }

    private function areUniqueFieldsEquals($object1, $object2): bool
    {
        foreach ($this->uniqueFields as $uniqueField) {
            $value1 = $this->getValueOfObject($object1, $uniqueField);
            $value2 = $this->getValueOfObject($object2, $uniqueField);

            if (!self::areValuesEquals($value1, $value2)) {
                return false;
            }
        }

        return true;
    }

    private function getValueOfObject($object, $field)
    {
        if (is_array($object)) {
            return $object[$field];
        }

        if (!is_object($object)) {
            throw new \RuntimeException('Value of parameter collection items should be an array or object.');
        }

        $accessor = $this->accessor;
        if ($accessor->isReadable($object, $field)) {
            return $accessor->getValue($object, $field);
        }

        $metadata = $this->getMetadataOf($object);
        if ($metadata->hasField($field)) {
            return $metadata->getFieldValue($object, $field);
        }

        throw new \RuntimeException(sprintf('Field "%s" of class "%s" does not exists.', $field, $metadata->getName()));
    }

    private function setValueOfObject($object, $field, $value)
    {
        if (is_array($object)) {
            $object[$field] = $value;

            return $object;
        }

        if (!is_object($object)) {
            throw new \RuntimeException('Value of parameter collection items should be an array or object.');
        }

        $accessor = $this->accessor;
        if ($accessor->isWritable($object, $field)) {
            $accessor->setValue($object, $field, $value);

            return $object;
        }

        $metadata = $this->getMetadataOf($object);
        if ($metadata->hasField($field)) {
            $metadata->setFieldValue($object, $field, $value);

            return $object;
        }

        throw new \RuntimeException(sprintf('Field of class "%s" does not exists.', $field));
    }

    private function getMetadataOf(object $object): ClassMetadata
    {
        $className = get_class($object);

        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($className);

        return $manager->getClassMetadata($className);
    }

    private static function isTraversable($value): bool
    {
        return is_array($value) || $value instanceof \Traversable;
    }

    private static function areValuesEquals($value1, $value2)
    {
        if (null === $value1 || null === $value2) {
            return false;
        }

        if ($value1 instanceof EnumInterface) {
            $value1 = $value1->getValue();
        }

        if ($value2 instanceof EnumInterface) {
            $value2 = $value2->getValue();
        }

        return $value1 === $value2;
    }

    private static function cloneData($value)
    {
        return is_object($value)
            ? clone $value
            : $value;
    }

    private static function createEmptyCopy($object)
    {
        if (is_array($object)) {
            return [];
        }

        if (is_object($object)) {
            $class = get_class($object);

            return new $class();
        }

        return null;
    }
}
