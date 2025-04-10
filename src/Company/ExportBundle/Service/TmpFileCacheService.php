<?php

namespace Company\ExportBundle\Service;

use Company\ExportBundle\Utility\StringUtility;
use Symfony\Component\Filesystem\Filesystem;

class TmpFileCacheService extends Filesystem
{
    private const TEMP_DIR = 'temp';

    private Filesystem $filesystem;
    private string $rootDir;

    public function __construct(Filesystem $filesystem, string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->filesystem = $filesystem;
    }

    public function createTmpPathInCache(string $filename): string
    {
        $filename = $this->createTmpFilenameInCache($filename);

        return $this->getAbsolutePathInCache($filename);
    }

    private function createTmpFilenameInCache(string $filename): string
    {
        //Create temp directory in cache
        $tempCacheDir = $this->getUploadDir().'/'.self::TEMP_DIR;
        $this->filesystem->mkdir($tempCacheDir);

        $fileExtension = $this->getFileExtension($filename);

        //Create filename that not exists
        do {
            $filename = StringUtility::getUniqueStr().'.'.$fileExtension;
        } while (file_exists($tempCacheDir.'/'.$filename));

        return $filename;
    }

    private function getAbsolutePathInCache(string $filename): string
    {
        $tempCacheDir = $this->getUploadDir().'/'.self::TEMP_DIR;

        return $tempCacheDir.'/'.$filename;
    }

    private function getFileExtension($filename): string
    {
        $tmp = explode('.', $filename);

        return strtolower($tmp[count($tmp) - 1]);
    }

    private function getUploadDir()
    {
        return $this->rootDir.'/var/uploads';
    }
}
