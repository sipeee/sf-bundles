<?php

namespace Company\AutocompleteBundle;

use Company\AutocompleteBundle\DependencyInjection\Compiler\AddDescriptorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CompanyAutocompleteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddDescriptorPass());
    }
}
