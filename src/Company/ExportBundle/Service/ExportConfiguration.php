<?php

namespace Company\ExportBundle\Service;

class ExportConfiguration
{
    private array $exportConfigurations;

    public function __construct(array $exportConfigurations)
    {
        $this->exportConfigurations = $exportConfigurations;
    }

    public function getExportConfigurations(string $exportName): array
    {
        if (!isset($this->exportConfigurations[$exportName])) {
            throw new \Exception(sprintf('%s export does not exist in configuration.', $exportName));
        }

        return $this->exportConfigurations[$exportName];
    }
}
