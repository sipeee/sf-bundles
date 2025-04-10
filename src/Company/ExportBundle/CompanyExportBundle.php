<?php

namespace Company\ExportBundle;

use Company\ExportBundle\DependencyInjection\CompilerPass\AddFieldTransformerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CompanyExportBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddFieldTransformerPass());
    }
}
