<?php

namespace Company\DoctrineEventBundle\Doctrine\EventSubscriber;

use Company\DoctrineEventBundle\Doctrine\Event\Events;
use Company\DoctrineEventBundle\Doctrine\Event\FlushEvent;
use Company\DoctrineEventBundle\Doctrine\Event\FlushEventInterface;
use Company\DoctrineEventBundle\Doctrine\Event\LifecycleEvent;
use Company\DoctrineEventBundle\Doctrine\Event\LifecycleEventInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events as DoctrineEvents;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventHandlerSubscriber implements EventSubscriber
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var array|LifecycleEvent[] */
    private array $entityInsertions = [];
    /** @var array|\Company\DoctrineEventBundle\Doctrine\Event\LifecycleEvent[] */
    private array $entityUpdates = [];
    /** @var array|LifecycleEvent[] */
    private array $entityRemoves = [];

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getSubscribedEvents(): array
    {
        return [
            DoctrineEvents::prePersist => DoctrineEvents::prePersist,
            DoctrineEvents::postLoad => DoctrineEvents::postLoad,
            DoctrineEvents::preFlush => DoctrineEvents::preFlush,
            DoctrineEvents::onFlush => DoctrineEvents::onFlush,
            DoctrineEvents::postFlush => DoctrineEvents::postFlush,
            SoftDeleteableListener::PRE_SOFT_DELETE => SoftDeleteableListener::PRE_SOFT_DELETE,
        ];
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $dispatcherEvent = LifecycleEvent::createByDoctrineLifecycleEvent($event);

        $this->callLifecycleEventByEntity(Events::PRE_PERSIST, $dispatcherEvent);
    }

    public function postLoad(PostLoadEventArgs $event): void
    {
        $dispatcherEvent = LifecycleEvent::createByDoctrineLifecycleEvent($event);

        $this->callLifecycleEventByEntity(Events::POST_LOAD, $dispatcherEvent);
    }

    public function preFlush(PreFlushEventArgs $event): void
    {
        $dispatcherEvent = FlushEvent::createByDoctrinePreFlushEvent($event);

        foreach ($dispatcherEvent->getScheduledEntityInsertionEvents() as $hash => $entityEvent) {
            $this->callLifecycleEventByEntity(Events::PRE_CREATE, $entityEvent);
        }

        foreach ($dispatcherEvent->getScheduledEntityUpdateEvents() as $hash => $entityEvent) {
            $this->callLifecycleEventByEntity(Events::PRE_UPDATE, $entityEvent);
        }

        foreach ($dispatcherEvent->getScheduledEntityDeletionEvents() as $hash => $entityEvent) {
            $this->callLifecycleEventByEntity(Events::PRE_REMOVE, $entityEvent);
        }

        $this->callFlushEvent(Events::getEventName(Events::PRE_FLUSH), $dispatcherEvent);
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $dispatcherEvent = FlushEvent::createByDoctrineOnFlushEvent($event);

        foreach ($dispatcherEvent->getScheduledEntityInsertionEvents() as $hash => $entityEvent) {
            $this->entityInsertions[$hash] = $this->callLifecycleEventByEntity(Events::ON_CREATE, $entityEvent);
        }

        foreach ($dispatcherEvent->getScheduledEntityUpdateEvents() as $hash => $entityEvent) {
            $this->entityUpdates[$hash] = $this->callLifecycleEventByEntity(Events::ON_UPDATE, $entityEvent);
        }

        foreach ($dispatcherEvent->getScheduledEntityDeletionEvents() as $hash => $entityEvent) {
            $this->entityRemoves[$hash] = $this->callLifecycleEventByEntity(Events::ON_REMOVE, $entityEvent);
        }

        $this->callFlushEvent(Events::getEventName(Events::ON_FLUSH), $dispatcherEvent);
    }

    public function preSoftDelete(LifecycleEventArgs $event): void
    {
        $entity = $event->getEntity();
        $this->entityRemoves[spl_object_hash($entity)] = $this->callLifecycleEventByEntity(Events::ON_REMOVE, LifecycleEvent::createByDoctrineLifecycleEvent($event));
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        foreach ($this->entityInsertions as $dispatcherEvent) {
            $this->callLifecycleEvent(Events::POST_CREATE, $dispatcherEvent);
        }

        foreach ($this->entityUpdates as $dispatcherEvent) {
            $this->callLifecycleEvent(Events::POST_UPDATE, $dispatcherEvent);
        }

        foreach ($this->entityRemoves as $dispatcherEvent) {
            $this->callLifecycleEvent(Events::POST_REMOVE, $dispatcherEvent);
        }

        $this->entityInsertions = $this->entityUpdates = $this->entityRemoves = [];

        $dispatcherEvent = FlushEvent::createByDoctrinePostFlushEvent($event);

        $this->callFlushEvent(Events::getEventName(Events::POST_FLUSH), $dispatcherEvent);
    }

    private function callLifecycleEventByEntity(string $eventName, LifecycleEvent $lifecycleEvent): LifecycleEvent
    {
        $this->callLifecycleEvent(
            Events::getEventName($eventName),
            $lifecycleEvent
        );

        $this->callLifecycleEvent(
            Events::getEventName($eventName, $lifecycleEvent->getClassMetadata()->getName()),
            $lifecycleEvent
        );

        return $lifecycleEvent;
    }

    private function callLifecycleEvent(string $name, LifecycleEventInterface $lifecycleEvent): void
    {
        $this->eventDispatcher->dispatch($lifecycleEvent, $name);
    }

    private function callFlushEvent(string $name, FlushEventInterface $event): void
    {
        $this->eventDispatcher->dispatch($event, $name);
    }
}
