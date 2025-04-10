<?php

namespace Company\ProcessorBundle\DependencyInjection\CompilerPass;

use Company\ProcessorBundle\Service\DataProcessorComposite;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $collectionDefinition = $container->getDefinition(DataProcessorComposite::class);

        foreach ($container->findTaggedServiceIds('company.processor.data_processor') as $id => $tags) {
            $collectionDefinition->addMethodCall('addDataProcessor', [new Reference($id)]);
        }
    }
}
