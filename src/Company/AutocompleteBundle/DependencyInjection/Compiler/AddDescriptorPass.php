<?php

namespace Company\AutocompleteBundle\DependencyInjection\Compiler;

use Company\AutocompleteBundle\Autocomplete\Manager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class AddDescriptorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $serviceIds = [];
        foreach ($container->findTaggedServiceIds('company.autocomplete.descriptor') as $id => $tags) {
            foreach ($tags as $tag) {
                $serviceIds[$tag['id'] ?? $id] = new Reference($id);
            }
        }

        $locatorId = 'company.autocomplete_bundle.autocomplete_descriptor.locator';
        $locatorDefinition = new Definition(ServiceLocator::class, [$serviceIds]);
        $locatorDefinition->addTag('container.service_locator');
        $container->setDefinition($locatorId, $locatorDefinition);

        $manager_definition = $container->getDefinition(Manager::class);
        $manager_definition->setArgument('$autocompleteDescriptors', new Reference($locatorId));
    }
}
