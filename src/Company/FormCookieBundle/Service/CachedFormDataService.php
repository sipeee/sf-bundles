<?php

namespace Company\FormCookieBundle\Service;

use Company\Library\Psr6Cache\CacheItem;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CachedFormDataService
{
    private const FORM_CACHE_DATA_KEY_PREFIX = 'form_data_';

    /** @var RequestStack */
    private $requestStack;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /** @var array|array[]|mixed[][] */
    private $initialFormViewDatas = [];

    public function __construct(RequestStack $requestStack, CacheItemPoolInterface $cachePool)
    {
        $this->requestStack = $requestStack;
        $this->cachePool = $cachePool;
    }

    /**
     * @param \DateInterval|int $ttl
     */
    public function initializeCachedFormData(FormInterface $form, $ttl): void
    {
        $request = $this->getRequest();
        $formName = $form->getName();
        $formKey = spl_object_hash($form);
        $requestDataContainer = $this->isGetMethodAcceptedByForm($form)
            ? $request->query
            : $request->request;
        $cachePool = $this->cachePool;
        $cookieKey = self::FORM_CACHE_DATA_KEY_PREFIX.$formName;

        $resetForms = $request->get('resetForms', false);
        if (!is_array($resetForms) && (bool) ($resetForms) || is_array($resetForms) && in_array($formName, $resetForms)) {
            $cachePool->deleteItem($cookieKey);
            $requestDataContainer->remove($formName);

            return;
        }

        $viewData = self::getViewDataOfForm($form);

        if ($requestDataContainer->has($formName)) {
            $this->initialFormViewDatas[$formKey] = $viewData;

            return;
        }

        $cookieData = $cachePool->getItem($cookieKey);

        if (!$cookieData->isHit()) {
            return;
        }

        if ($viewData != $cookieData->get()) {
            $requestDataContainer->set($formName, $cookieData->get());
        } else {
            $cachePool->deleteItem($cookieKey);
        }
    }

    /**
     * @param \DateInterval|int $ttl
     */
    public function cacheFormData(FormInterface $form, $ttl): void
    {
        $cachePool = $this->cachePool;
        $cookieKey = self::FORM_CACHE_DATA_KEY_PREFIX.$form->getName();

        $viewData = self::getViewDataOfForm($form);
        $formKey = spl_object_hash($form);

        if (!isset($this->initialFormViewDatas[$formKey]) || $this->initialFormViewDatas[$formKey] != $viewData) {
            $cacheValue = new CacheItem($cookieKey, $viewData, true);
            $cacheValue->expiresAfter($ttl);
            $cachePool->save($cacheValue);
        } else {
            $cachePool->deleteItem($cookieKey);
        }

        if (array_key_exists($formKey, $this->initialFormViewDatas)) {
            unset($this->initialFormViewDatas[$formKey]);
        }
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function isGetMethodAcceptedByForm(FormInterface $form): bool
    {
        return 'get' === mb_strtolower($form->getConfig()->getMethod());
    }

    private static function getViewDataOfForm(FormInterface $form)
    {
        if (!$form->getConfig()->getCompound()) {
            return $form->getViewData();
        }

        $data = [];
        foreach ($form as $childName => $childForm) {
            $data[$childName] = self::getViewDataOfForm($childForm);
        }

        return $data;
    }
}
