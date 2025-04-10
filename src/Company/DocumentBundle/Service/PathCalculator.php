<?php

namespace Company\DocumentBundle\Service;

use Symfony\Component\HttpFoundation\File\File;

class PathCalculator
{
    private MetadataService $metadataService;

    public function __construct(MetadataService $metadataService)
    {
        $this->metadataService = $metadataService;
    }

    public static function calculateFilePathByIdentifier(array $config, string $identifier): string
    {
        return self::calculatePathByIds($config, $identifier, DIRECTORY_SEPARATOR, $config['root_path']);
    }

    public function calculateFilePath(array $config, object $entity, ?string $variant = null): string
    {
        return $this->calculatePath($config, $entity, DIRECTORY_SEPARATOR, $config['root_path'], $variant);
    }

    public function calculateWebPath(array $config, object $entity, ?string $variant, File $file): string
    {
        if (null !== $config['url_method']) {
            return call_user_func($config['url_method'], $entity, $config, $variant, $file);
        } else {
            return $this->calculatePath($config, $entity, '/', $config['web_path'], $variant);
        }
    }

    private function calculatePath(array $config, object $entity, string $separator, string $rootPath, ?string $variant): string
    {
        $metadataService = $this->metadataService;

        $ids = $metadataService->getIdentifierOf($entity);

        $path = self::calculatePathByIds($config, $ids, $separator, $rootPath);

        if (null !== $variant) {
            $filename = $metadataService->getFileName($entity, $config);

            $path .= sprintf(
                '%s%s%s%s',
                $separator,
                $variant,
                $separator,
                $filename
            );
        }

        return $path;
    }

    private static function calculatePathByIds(array $config, string $ids, string $separator, string $rootPath): string
    {
        $sha1Path = self::getSha1Path($ids, $config['directory_depth'], $separator);

        return sprintf(
            '%s%s%s%s',
            $rootPath,
            $separator,
            $sha1Path,
            $ids
        );
    }

    private static function getSha1Path(string $ids, int $directory_depth, string $separator): string
    {
        $sha1 = substr(sha1($ids), 0, $directory_depth);

        return !empty($sha1)
            ? implode($separator, str_split($sha1)).$separator
            : '';
    }
}
