<?php

namespace Company\TypeBundle\Form\EventSubscriber;

use Company\TypeBundle\Service\FormFileContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FileSubscriber implements EventSubscriberInterface
{
    /** @var FormFileContainer */
    private $formDataContainer;

    public function __construct(FormFileContainer $formDataContainer)
    {
        $this->formDataContainer = $formDataContainer;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::SUBMIT => 'onSubmit',
        ];
    }

    public function postSetData(FormEvent $event)
    {
        $this->formDataContainer->setFormData($event->getForm(), $event->getData());
    }

    public function onSubmit(FormEvent $event)
    {
        $currentFileValue = $event->getData();
        $originalFileValue = $this->formDataContainer->getFormData($event->getForm());

        if (null !== $originalFileValue && null === $currentFileValue) {
            $event->setData($originalFileValue);
        }
    }
}
