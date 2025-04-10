<?php

namespace Company\ExportBundle\Presentation;

use Symfony\Component\HttpFoundation\File\File;

class FileAttachmentResponseNoCache extends FileAttachmentResponse
{
    /**
     * @param File|string $file
     */
    public function __construct($file, string $fileName, int $status = 200, array $headers = [])
    {
        parent::__construct($file, $fileName, $status, $headers, false);

        $this->headers->add(HttpHeaders::NO_CACHE_HEADERS_FOR_FILES);
    }
}
