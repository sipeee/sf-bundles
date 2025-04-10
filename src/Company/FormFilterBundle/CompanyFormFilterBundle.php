<?php

namespace Company\FormFilterBundle;

use Company\FormFilterBundle\DependencyInjection\CompilerPass\FilterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CompanyFormFilterBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FilterCompilerPass());
    }
}
