<?php

namespace Company\FormFilterBundle\DependencyInjection\CompilerPass;

use Company\FormFilterBundle\Service\FilterTypeComposite;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FilterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $serviceIds = [];
        foreach ($container->findTaggedServiceIds('company.filter_type') as $id => $tags) {
            $serviceIds[$id] = new Reference($id);
        }

        $locatorId = 'company.form_filter_bundle.filter_type.locator';
        $locatorDefinition = new Definition(ServiceLocator::class, [$serviceIds]);
        $locatorDefinition->addTag('container.service_locator');
        $container->setDefinition($locatorId, $locatorDefinition);

        $transformerManagerDef = $container->findDefinition(FilterTypeComposite::class);
        $transformerManagerDef->addArgument(new Reference($locatorId));
    }
}
