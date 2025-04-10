<?php

namespace Company\FormChangeConfirmationBundle\Form\EventSubscriber;

use Company\FormCookieBundle\Service\CachedFormDataService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FormCookieSubscriber implements EventSubscriberInterface
{
    /** @var CachedFormDataService */
    private $cachedFormDataService;
    /** @var int */
    private $ttl;

    public function __construct(CachedFormDataService $cachedFormDataService, int $ttl)
    {
        $this->cachedFormDataService = $cachedFormDataService;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    public function postSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($this->isValidCachedFormSettings($form)) {
            $this->cachedFormDataService->initializeCachedFormData($form, $this->ttl);
        }
    }

    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($this->isValidCachedFormSettings($form) && $form->isSubmitted() && $form->isValid()) {
            $this->cachedFormDataService->cacheFormData($form, $this->ttl);
        }
    }

    private static function isValidCachedFormSettings(FormInterface $form): bool
    {
        if (!self::isRootForm($form)) {
            return false;
        }

        self::checkCsrfSetting($form);

        return true;
    }

    private static function isRootForm(FormInterface $form): bool
    {
        return null === $form->getParent();
    }

    private static function checkCsrfSetting(FormInterface $form): void
    {
        if ($form->getConfig()->getOption('csrf_protection')) {
            throw new InvalidOptionsException(sprintf('Csrf protection ("csrf_protection") cannot be applied on type %s when cookie cache ("cookie_cache") option enabled.', get_class($form->getConfig()->getType()->getInnerType())));
        }
    }
}
