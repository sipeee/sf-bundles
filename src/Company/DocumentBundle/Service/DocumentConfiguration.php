<?php

namespace Company\DocumentBundle\Service;

use Company\DocumentBundle\Service\DocumentTransformer\OuterFrameImageTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DocumentConfiguration
{
    private Collection $config;

    public function __construct(array $config)
    {
        $this->config = self::getFullConfig($config);
    }

    public function getConfigByEntity(object $entity): Collection
    {
        $configs = new ArrayCollection();
        foreach ($this->config as $class => $config) {
            if (self::isEntityMatchToConfig($entity, $config)) {
                $configs[$config['filename_field']] = $config;
            }
        }

        return $configs;
    }

    public function getConfigByEntityAndField(object $entity, string $filenameField): array
    {
        foreach ($this->config as $class => $config) {
            if (self::isEntityAndFieldMatchToConfig($entity, $config['filename_field'], $config)) {
                return $config;
            }
        }

        return [];
    }

    private static function getFullConfig(array $config): ?Collection
    {
        $indexedConfigs = new ArrayCollection();
        $rootPath = rtrim(realpath($config['root_path']), DIRECTORY_SEPARATOR);
        $webPath = rtrim($config['web_path'], '/');

        foreach ($config['document_fields'] as $fieldConfig) {
            $fieldConfig['root_path'] = $rootPath.DIRECTORY_SEPARATOR.$fieldConfig['directory_name'];
            $fieldConfig['web_path'] = $webPath.'/'.$fieldConfig['directory_name'];
            $fieldConfig['variants'] = self::addDefaultVariants($fieldConfig['variants'], $fieldConfig['original_variant'], $fieldConfig['thumbnail_variant']);

            $indexedConfigs[] = $fieldConfig;
        }

        return $indexedConfigs;
    }

    private static function isEntityAndFieldMatchToConfig(object $entity, string $field, array $config): bool
    {
        return self::isEntityMatchToConfig($entity, $config) && $config['filename_field'] === $field;
    }

    private static function isEntityMatchToConfig(object $entity, array $config): bool
    {
        return is_a($entity, $config['entity_class']);
    }

    private static function addDefaultVariants(array $variants, string $originalVariantName, string $thumbnailVariantName): array
    {
        return array_merge(self::getDefaultVariants($originalVariantName, $thumbnailVariantName), $variants);
    }

    private static function getDefaultVariants(string $originalVariantName, string $thumbnailVariantName): array
    {
        return [
            $originalVariantName => [
                'transformers' => [],
            ],
            $thumbnailVariantName => [
                'transformers' => [
                    [
                        'class' => OuterFrameImageTransformer::class,
                        'parameters' => [
                            'width' => 100,
                            'height' => 100,
                        ],
                    ],
                ],
            ],
        ];
    }
}
