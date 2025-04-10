<?php

namespace Company\FormCookieBundle\Form\Extension;

use Company\FormCookieBundle\Form\EventSubscriber\FormCookieSubscriber;
use Company\FormCookieBundle\Service\CachedFormDataService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormCookieTypeExtension extends AbstractTypeExtension
{
    public const DEFAULT_COOKIE_CACHE_TTL = 60 * 10; // 10minutes

    /** @var CachedFormDataService */
    private $cachedFormDataService;

    public function __construct(CachedFormDataService $cachedFormDataService)
    {
        $this->cachedFormDataService = $cachedFormDataService;
    }

    public function getExtendedType(): string
    {
        $types = self::getExtendedTypes();

        return reset($types);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'cookie_cache' => false,
            'cookie_cache_ttl' => self::DEFAULT_COOKIE_CACHE_TTL,
            'csrf_protection' => function (Options $options, $previousValue) {
                return ($options['cookie_cache'])
                    ? false
                    : $previousValue;
            },
        ]);

        $resolver->setAllowedTypes('cookie_cache', ['bool']);
        $resolver->setAllowedTypes('cookie_cache_ttl', ['int', \DateInterval::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['cookie_cache']) {
            $builder->addEventSubscriber(
                new FormCookieSubscriber($this->cachedFormDataService, $options['cookie_cache_ttl'])
            );
        }
    }
}
