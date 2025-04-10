<?php

namespace Company\ExportBundle\DependencyInjection\CompilerPass;

use Company\ExportBundle\Service\FieldTransformerComposite;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class AddFieldTransformerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $serviceIds = [];
        foreach ($container->findTaggedServiceIds('company.export.field_transformer') as $id => $tags) {
            $serviceIds[$id] = new Reference($id);
        }

        $locatorId = 'company.export_bundle.field_transformer.locator';
        $locatorDefinition = new Definition(ServiceLocator::class, [$serviceIds]);
        $locatorDefinition->addTag('container.service_locator');
        $container->setDefinition($locatorId, $locatorDefinition);

        $transformerManagerDef = $container->findDefinition(FieldTransformerComposite::class);
        $transformerManagerDef->addArgument(new Reference($locatorId));
    }
}
