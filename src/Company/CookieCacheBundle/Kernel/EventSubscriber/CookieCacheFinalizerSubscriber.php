<?php

namespace Company\CookieCacheBundle\Kernel\EventSubscriber;

use Company\CookieCacheBundle\Service\CookieCacheAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieCacheFinalizerSubscriber implements EventSubscriberInterface
{
    /** @var CookieCacheAdapter */
    private $cookieCache;

    public function __construct(CookieCacheAdapter $cookieCache)
    {
        $this->cookieCache = $cookieCache;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['onKernelResponse', -10],
            ],
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $this->cookieCache->addHeadersToResponse(
            $event->getResponse()
        );
    }
}
