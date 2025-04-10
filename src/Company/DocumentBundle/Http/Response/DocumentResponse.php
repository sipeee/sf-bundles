<?php

namespace Company\DocumentBundle\Http\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DocumentResponse extends BinaryFileResponse
{
    public function __construct(File $file, string $downloadName = '', bool $removeAfterDownload = false)
    {
        parent::__construct($file, 200, ['Content-Type' => $file->getMimeType()]);

        if (!empty($downloadName)) {
            $this->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadName);
        } else {
            $this->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
        }

        if ($removeAfterDownload) {
            $this->deleteFileAfterSend(true);
        }
    }
}
