<?php

namespace Company\DocumentBundle;

use Company\DocumentBundle\DependencyInjection\CompilerPass\AddDocumentTransformerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CompanyDocumentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddDocumentTransformerPass());
    }
}
