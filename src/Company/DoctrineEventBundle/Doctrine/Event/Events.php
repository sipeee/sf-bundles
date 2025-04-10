<?php

namespace Company\DoctrineEventBundle\Doctrine\Event;

abstract class Events
{
    public const PRE_PERSIST = 'pre_persist';
    public const POST_LOAD = 'post_load';
    public const PRE_CREATE = 'pre_create';
    public const ON_CREATE = 'on_create';
    public const POST_CREATE = 'post_create';
    public const PRE_UPDATE = 'pre_update';
    public const ON_UPDATE = 'on_update';
    public const POST_UPDATE = 'post_update';
    public const PRE_REMOVE = 'pre_remove';
    public const ON_REMOVE = 'on_remove';
    public const POST_REMOVE = 'post_remove';
    public const PRE_FLUSH = 'pre_flush';
    public const ON_FLUSH = 'on_flush';
    public const POST_FLUSH = 'post_flush';

    private const DOCTRINE_EVENT = 'company.doctrine.event';

    public static function getEventName(string $eventName, ?string $className = null): string
    {
        $eventName = self::DOCTRINE_EVENT.'.'.$eventName;
        if (null !== $className) {
            $eventName .= '.'.self::createUnderScoredClassName($className);
        }

        return $eventName;
    }

    private static function createUnderScoredClassName(string $className): string
    {
        return strtolower(preg_replace(
            ['/^\\\\/', '/\\\\/', '/([a-z])([A-Z])/'],
            ['', '.', '\\1_\\2'],
            $className
        ));
    }
}
