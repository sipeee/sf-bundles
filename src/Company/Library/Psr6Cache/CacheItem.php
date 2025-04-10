<?php

namespace Company\Library\Psr6Cache;

use Psr\Cache\CacheItemInterface;
use TypeError;

final class CacheItem implements CacheItemInterface
{
    private string $key;
    /** @var mixed */
    private $value;

    private bool $isHit;
    /** @var float|null */
    private ?\DateTimeInterface $expiry = null;

    /**
     * @internal
     *
     * @param mixed $data
     */
    public function __construct(string $key, $data, bool $isHit)
    {
        $this->key = $key;
        $this->value = $data;
        $this->isHit = $isHit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * {@inheritDoc}
     */
    public function set($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt($expiration): self
    {
        if (null === $expiration) {
            $this->expiry = null;
        } elseif ($expiration instanceof \DateTimeInterface) {
            $this->expiry = $expiration;
        } else {
            throw new TypeError(sprintf('Expected $expiration to be an instance of DateTimeInterface or null, got %s', is_object($expiration) ? get_class($expiration) : gettype($expiration)));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAfter($time): self
    {
        if (null === $time) {
            $date = null;
        } elseif ($time instanceof \DateInterval) {
            $date = new \DateTime();
            $date->add($time);
        } elseif (is_int($time)) {
            $date = new \DateTime();
            $date->setTimestamp($date->getTimestamp() + $time);
        } else {
            throw new \TypeError(sprintf('Expected $time to be either an integer, an instance of DateInterval or null, got %s', is_object($time) ? get_class($time) : gettype($time)));
        }

        $this->expiry = $date;

        return $this;
    }

    /**
     * @internal
     */
    public function getExpiry(): ?\DateTimeInterface
    {
        return $this->expiry;
    }
}
