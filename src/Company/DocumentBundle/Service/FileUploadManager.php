<?php

namespace Company\DocumentBundle\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadManager
{
    public const DIRECTORY_PERMISSION = 0775;
    public const FILE_PERMISSION = 0664;

    private PathCalculator $pathCalculator;
    private MetadataService $metadataService;
    private DocumentTransformerManager $transformer;

    public function __construct(
        PathCalculator $pathCalculator,
        MetadataService $metadataService,
        DocumentTransformerManager $transformer
    ) {
        $this->pathCalculator = $pathCalculator;
        $this->metadataService = $metadataService;
        $this->transformer = $transformer;
    }

    public function removeFilesOfEntityField(object $entity, string $identifier, array $fieldConfig): void
    {
        $dir = empty($identifier)
            ? $this->pathCalculator->calculateFilePath($fieldConfig, $entity)
            : PathCalculator::calculateFilePathByIdentifier($fieldConfig, $identifier);

        self::removeDir($dir, $fieldConfig['directory_depth']);
    }

    public function createVariantsOfEntityField(object $entity, array $fieldConfig)
    {
        $metadataService = $this->metadataService;
        $transformer = $this->transformer;
        $formData = $metadataService->getFormData($entity, $fieldConfig);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $formData->getFile();
        $originalFile = $this->moveFileToVariantDir($entity, $fieldConfig, $fieldConfig['original_variant'], $uploadedFile);

        $transformer->transform($originalFile, $fieldConfig['variants'][$fieldConfig['original_variant']]['transformers']);

        foreach ($fieldConfig['variants'] as $name => $variantConfig) {
            if ($fieldConfig['original_variant'] === $name || !$transformer->hasTransformation($originalFile, $variantConfig['transformers'])) {
                continue;
            }

            $file = $this->copyFileToVariantDir($entity, $fieldConfig, $name, $originalFile);

            $transformer->transform($file, $variantConfig['transformers']);
        }
    }

    private function moveFileToVariantDir(object $entity, array $entityFileConfig, string $variantName, File $file): File
    {
        [$fileDir, $fileName] = $this->initFilePlacement($entityFileConfig, $entity, $variantName);

        $file = $file->move($fileDir, $fileName);

        chmod($file->getRealPath(), self::FILE_PERMISSION);

        return $file;
    }

    private function copyFileToVariantDir(object $entity, array $entityFileConfig, string $variantName, File $file): File
    {
        [$fileDir, $fileName] = $this->initFilePlacement($entityFileConfig, $entity, $variantName);

        $filePath = $fileDir.DIRECTORY_SEPARATOR.$fileName;

        copy($file->getRealPath(), $filePath);

        $file = new File($filePath);

        chmod($file->getRealPath(), self::FILE_PERMISSION);

        return new File($filePath);
    }

    private function initFilePlacement(array $entityFileConfig, object $entity, string $variantName): array
    {
        $filePath = $this->pathCalculator->calculateFilePath($entityFileConfig, $entity, $variantName);
        $fileDir = dirname($filePath);
        $fileName = basename($filePath);
        self::createDirectory($fileDir);

        return [$fileDir, $fileName];
    }

    private static function removeDir($dir, int $depth): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($dir);

        $dirParts = explode(DIRECTORY_SEPARATOR, $dir);
        array_pop($dirParts);

        while (0 < $depth && self::isDirectoryEmpty($dir = implode(DIRECTORY_SEPARATOR, $dirParts))) {
            rmdir($dir);
            array_pop($dirParts);
            --$depth;
        }
    }

    private static function createDirectory(string $dirPath): void
    {
        if (is_dir($dirPath)) {
            return;
        }

        mkdir($dirPath, self::DIRECTORY_PERMISSION, true);
    }

    private static function isDirectoryEmpty(string $dir): bool
    {
        $iterator = new \FilesystemIterator($dir);

        return !$iterator->valid();
    }
}
