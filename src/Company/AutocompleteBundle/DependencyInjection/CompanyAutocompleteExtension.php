<?php

namespace Company\AutocompleteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class CompanyAutocompleteExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'company_autocomplete';
    }

    public function getXsdValidationBasePath(): string
    {
        return __DIR__.'/../Resources/config/';
    }

    public function getNamespace(): string
    {
        return 'http://www.example.com/symfony/schema/';
    }
}
