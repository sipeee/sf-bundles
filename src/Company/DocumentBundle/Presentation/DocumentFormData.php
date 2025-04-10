<?php

namespace Company\DocumentBundle\Presentation;

use Symfony\Component\HttpFoundation\File\File;

class DocumentFormData extends DocumentVariant
{
    private ?File $originalFile = null;

    private ?string $originalUrl = null;

    private ?string $title = null;

    private bool $isRemovable = false;

    private ?string $identifier = null;

    public function getOriginalFile(): ?File
    {
        return $this->originalFile;
    }

    public function setOriginalFile(?File $originalFile): self
    {
        $this->originalFile = $originalFile;

        return $this;
    }

    public function getOriginalUrl(): ?string
    {
        return $this->originalUrl;
    }

    public function setOriginalUrl(?string $originalUrl): self
    {
        $this->originalUrl = $originalUrl;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function isRemovable(): bool
    {
        return $this->isRemovable;
    }

    public function setRemovable(bool $isRemovable): self
    {
        $this->isRemovable = $isRemovable;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}
