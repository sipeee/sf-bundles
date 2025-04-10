<?php

namespace Company\DbCacheBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cache_entry")
 */
class CacheEntry
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private ?string $key;
    /**
     * @ORM\Column(type="blob", nullable=true)
     */
    private $value;
    /**
     * @ORM\Column(name="expired_at", type="datetime", nullable=true)
     */
    private ?\DateTime $expiredAt;

    public function setKey(?string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getContent()
    {
        return null !== $this->value
            ? unserialize(stream_get_contents($this->value))
            : null;
    }

    public function setContent($content): self
    {
        if (null !== $content) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, serialize($content));
            rewind($stream);

            $this->value = $stream;
        } else {
            $this->value = null;
        }

        return $this;
    }

    public function isHit(): bool
    {
        return null === $this->getExpiredAt() || new \DateTime() <= $this->getExpiredAt();
    }

    /* ##################################################### */
    /* ##------------------- GENERATED -------------------## */
    /* ##################################################### */

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeInterface
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeInterface $expiredAt): self
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
}
