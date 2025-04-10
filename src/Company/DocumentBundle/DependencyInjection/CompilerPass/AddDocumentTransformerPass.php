<?php

namespace Company\DocumentBundle\DependencyInjection\CompilerPass;

use Company\DocumentBundle\Service\DocumentTransformerManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class AddDocumentTransformerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $serviceIds = [];
        foreach ($container->findTaggedServiceIds('company.document.transformer') as $id => $tags) {
            $serviceIds[$id] = new Reference($id);
        }

        $locatorId = 'company.document_bundle.transformer.locator';
        $locatorDefinition = new Definition(ServiceLocator::class, [$serviceIds]);
        $locatorDefinition->addTag('container.service_locator');
        $container->setDefinition($locatorId, $locatorDefinition);

        $transformerManagerDef = $container->findDefinition(DocumentTransformerManager::class);
        $transformerManagerDef->addArgument(new Reference($locatorId));
    }
}
