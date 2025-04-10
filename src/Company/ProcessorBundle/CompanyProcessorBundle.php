<?php

namespace Company\ProcessorBundle;

use Company\ProcessorBundle\DependencyInjection\CompilerPass\ProcessorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CompanyProcessorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ProcessorCompilerPass());
    }
}
