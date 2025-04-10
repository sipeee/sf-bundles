<?php

namespace Company\UnchangedDisabledFormFieldBundle\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DisabledFieldSubscriber implements EventSubscriberInterface
{
    /** @var PropertyAccessorInterface */
    private $accessor;

    /** @var array */
    private $disabledDataSnapshot = [];

    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'handleSetData',
            FormEvents::PRE_SUBMIT => 'handlePreSubmit',
        ];
    }

    public function handleSetData(FormEvent $event)
    {
        if (self::isFormFieldEnabled($event->getForm())) {
            $this->makeDataSnapshot($event);
        }
    }

    public function handlePreSubmit(FormEvent $event)
    {
        if (self::isFormFieldEnabled($event->getForm())) {
            $this->restoreDisabledDataSnapshot($event);
        }
    }

    private function makeDataSnapshot(FormEvent $event): void
    {
        $this->disabledDataSnapshot = [];

        /** @var FormInterface|FormInterface[] $form */
        $form = $event->getForm();

        foreach ($form as $fieldName => $child) {
            $this->disabledDataSnapshot[$fieldName] = $child->getViewData();
        }
    }

    private function restoreDisabledDataSnapshot(FormEvent $event): void
    {
        $data = $event->getData();
        /** @var FormInterface|FormInterface[] $form */
        $form = $event->getForm();
        foreach ($form as $fieldName => $child) {
            if (self::isFormFieldEnabled($child) || $form->getConfig()->getOption('change_disabled') || !isset($this->disabledDataSnapshot[$fieldName])) {
                continue;
            }

            $data[$fieldName] = $this->disabledDataSnapshot[$fieldName];
        }

        $event->setData($data);
    }

    private static function isFormFieldEnabled(FormInterface $form)
    {
        return !$form->getConfig()->getDisabled();
    }
}
