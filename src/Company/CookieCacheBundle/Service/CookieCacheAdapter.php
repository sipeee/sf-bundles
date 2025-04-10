<?php

namespace Company\CookieCacheBundle\Service;

use Company\Library\Psr6Cache\CacheAdapterInterface;
use Company\Library\Psr6Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CookieCacheAdapter implements CacheAdapterInterface
{
    private const DELETION_DATE = '2000-01-01 00:00:00';

    /** @var RequestStack */
    private $requestStack;

    /** @var array<Cookie|null> */
    private $changedValues = [];

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getOneItem($key);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function saveItems(array $items): bool
    {
        $success = true;
        foreach ($items as $key => $item) {
            $success |= $this->saveOneItem($item);
        }

        return (bool) $success;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        if (null === $this->getRequest()) {
            return false;
        }

        $this->changedValues = [];
        foreach ($this->getRequest()->cookies as $key => $_) {
            $this->changedValues[$key] = null;
        }
        foreach ($this->changedValues as $key => $_) {
            $this->changedValues[$key] = null;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success |= $this->deleteOneItem($key);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return $this->hasChangedCookie($key)
            ? null !== $this->changedValues[$key]
            : $this->hasCookieValue($key);
    }

    /**
     * {@inheritdoc}
     */
    public function addHeadersToResponse(Response $response): void
    {
        $headerBag = $response->headers;
        foreach ($this->changedValues as $key => $cookie) {
            $headerBag->setCookie(
                null !== $cookie
                    ? $cookie
                    : self::createRemoveCookie($key)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    private function getOneItem(string $key): CacheItemInterface
    {
        return ($this->hasChangedCookie($key))
            ? $this->getChangedItem($key)
            : $this->getCookieItem($key);
    }

    /**
     * {@inheritdoc}
     */
    private function deleteOneItem($key)
    {
        if (null === $this->getRequest()) {
            return false;
        }

        if ($this->hasCookieValue($key)) {
            $this->changedValues[$key] = null;

            $success = true;
        } else {
            $success = isset($this->changedValues[$key]);

            unset($this->changedValues[$key]);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    private function saveOneItem(CacheItemInterface $item): bool
    {
        if (null === $this->getRequest()) {
            return false;
        }

        $this->changedValues[$item->getKey()] = self::createCookie($item);

        return true;
    }

    private function getCookieItem(string $key): CacheItemInterface
    {
        $request = $this->getRequest();

        if ($this->hasCookieValue($key)) {
            $item = new CacheItem(
                $key,
                self::decodeValue($request->cookies->get($key)),
                true
            );
        } else {
            $item = new CacheItem($key, null, false);
            $item->expiresAt(new \DateTime(self::DELETION_DATE));
        }

        return $item;
    }

    private function hasCookieValue(string $key): bool
    {
        $request = $this->getRequest();

        return null !== $request && $request->cookies->has($key);
    }

    private function getChangedItem(string $key): CacheItemInterface
    {
        $cookie = $this->changedValues[$key];
        if (null !== $cookie) {
            $item = new CacheItem(
                $key,
                self::decodeValue($cookie->getValue($key)),
                true
            );
            $item->expiresAfter($cookie->getExpiresTime());
        } else {
            $item = new CacheItem($key, null, false);
            $item->expiresAt(new \DateTime(self::DELETION_DATE));
        }

        return $item;
    }

    private function hasChangedCookie(string $key)
    {
        return array_key_exists($key, $this->changedValues);
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private static function convertDateIntervalToTTL(\DateInterval $interval): int
    {
        $now = new \DateTime();
        $after = clone $now;
        $after->add($interval);

        return $after->getTimestamp() - $now->getTimestamp();
    }

    private static function encodeValue($value): string
    {
        return json_encode($value);
    }

    private static function decodeValue(string $rawCookieValue)
    {
        $decodedValue = json_decode($rawCookieValue, true);

        return null !== $decodedValue
            ? $decodedValue
            : $rawCookieValue;
    }

    private static function createRemoveCookie(string $key): Cookie
    {
        return new Cookie($key, null, 0, '/', null, false, true);
    }

    private static function createCookie(CacheItemInterface $item): Cookie
    {
        $value = self::encodeValue($item->get());
        $time = (null !== $item->getExpiry())
            ? self::calculateTime($item->getExpiry())
            : null;

        return new Cookie($item->getKey(), $value, $time, '/', null, false, true);
    }

    private static function calculateTime(\DateTimeInterface $expiry)
    {
        $now = new \DateTime();

        return ($now <= $expiry)
            ? $expiry->getTimestamp()
            : 0;
    }
}
