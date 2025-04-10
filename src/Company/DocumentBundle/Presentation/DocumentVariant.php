<?php

namespace Company\DocumentBundle\Presentation;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentVariant
{
    public const EXTENSIONS = [
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/rtf' => 'rtf',
        'text/plain' => 'txt',
    ];

    public const FILE_ICONS = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'doc',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xls',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt',
        'application/rtf' => 'rtf',
        'text/plain' => 'txt',
    ];

    private ?File $file;

    private ?string $url;

    public function __construct(?File $file = null, ?string $url = null)
    {
        $this->file = $file;
        $this->url = $url;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function isImage(): bool
    {
        $mimeType = $this->getMimeType();

        return (null !== $mimeType) && preg_match('/^image\\//', $mimeType);
    }

    public function isDisplayedOnWeb(): bool
    {
        if ($this->isImage()) {
            return true;
        }

        $mimeType = $this->getMimeType();

        return (null !== $mimeType) && preg_match('/\\/pdf$/', $mimeType);
    }

    public function getIconLink(): ?string
    {
        if (null === $this->file) {
            return null;
        }

        if ($this->isImage()) {
            return null;
        }

        $mimeType = mime_content_type($this->file->getRealPath());

        if ('application/octet-stream' === $mimeType) {
            $extension = $this->getExtension();
            $mimeType2 = array_search($extension, self::EXTENSIONS, true);
            if (false !== $mimeType2) {
                $mimeType = $mimeType2;
            }
        }
        $icon = self::FILE_ICONS[$mimeType] ?? 'unknown';

        return sprintf('/bundles/companydocument/images/file-types/%s.png', $icon);
    }

    public function getExtension(): ?string
    {
        $file = $this->getFile();
        if (null === $file || false === $file->getRealPath()) {
            return null;
        }

        $mimeType = mime_content_type($file->getRealPath());
        if (isset(self::EXTENSIONS[$mimeType])) {
            return self::EXTENSIONS[$mimeType];
        }

        if ($file instanceof UploadedFile) {
            $clientOriginalName = $file->getClientOriginalName();
            $match = [];

            return preg_match('/\\.([A-Za-z]+)$/', $clientOriginalName, $match)
                ? strtolower($match[1])
                : null;
        }

        if ($file instanceof File) {
            return !empty($file->getExtension())
                ? strtolower($file->getExtension())
                : null;
        }

        return null;
    }

    public function getMimeType(): ?string
    {
        return (null !== $this->file)
            ? mime_content_type($this->file->getRealPath())
            : null;
    }
}
