<?php

namespace Company\ExportBundle\Presentation;

use Company\ExportBundle\Utility\StringUtility;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileAttachmentResponse extends BinaryFileResponse
{
    /**
     * @param File|string $file
     */
    public function __construct($file, string $fileName, int $status = 200, array $headers = [], bool $public = true, bool $autoEtag = false, bool $autoLastModified = true)
    {
        //Make sure $file is File
        if (!$file instanceof File) {
            $file = new File((string) $file);
        }

        //Call parent constructor
        parent::__construct($file, $status, $headers, $public, null, $autoEtag, $autoLastModified);

        //Handle content type
        $this->headers->set('Content-Type', MimeType::getMimeTypeFromExtension($file->getFilename()));

        //Set disposition to attachment
        $this->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->getFileName($fileName),
            $this->getFileNameFallback($fileName)
        );
    }

    private function getFileName(string $fileName): string
    {
        return StringUtility::mb_str_replace(['%', '\/', '\\\\'], '-', $fileName);
    }

    private function getFileNameFallback(string $fileName): string
    {
        $fileName = $this->getFileName($fileName);
        $fallback = @iconv('UTF-8', 'ASCII//TRANSLIT', $fileName);
        if (false === $fallback) {
            return iconv('UTF-8', 'ASCII//IGNORE', $fileName);
        }

        return $fallback;
    }
}
