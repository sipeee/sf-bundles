<?php

namespace Company\ExportBundle\Service;

use Company\ExportBundle\Service\Writer\ExcelDataTableWriter;
use Company\ExportBundle\Service\Writer\ExcelReport;
use Company\Library\QueryIteration\QueryBuilderBatchIterator;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * GenericExcelExportService.
 */
class ExcelExportService
{
    public const SHEET_TITLE = 'Export';
    public const TEMPLATE_XLSX_PATH = '@CompanyExportBundle/Resources/export/genericExcelExport.xlsx';

    private ExcelDataTableWriter $excelDataTableWriter;
    private ExcelReport $excelReport;
    private ExportConfiguration $exportConfiguration;
    private TmpFileCacheService $tmpFileCacheService;
    private KernelInterface $kernel;

    public function __construct(ExcelDataTableWriter $excelDataTableWriter, ExcelReport $excelReport, ExportConfiguration $exportConfiguration, TmpFileCacheService $fileUtils)
    {
        $this->excelDataTableWriter = $excelDataTableWriter;
        $this->excelReport = $excelReport;
        $this->exportConfiguration = $exportConfiguration;
        $this->tmpFileCacheService = $fileUtils;
    }

    public function generateCsv(string $exportName, QueryBuilder $queryBuilder, bool $isComplex = false): string
    {
        $generator = $this->dataGenerator($queryBuilder, $isComplex);
        $mapping = $this->exportConfiguration->getExportConfigurations($exportName);
        $path = $this->tmpFileCacheService->createTmpPathInCache('csv');
        $this->excelDataTableWriter->generateCsvFileByEntityList($generator, $mapping, $path);

        return $path;
    }

    public function generateText(string $exportName, QueryBuilder $queryBuilder, bool $isComplex = false): string
    {
        $generator = $this->dataGenerator($queryBuilder, $isComplex);
        $mapping = $this->exportConfiguration->getExportConfigurations($exportName);
        $path = $this->tmpFileCacheService->createTmpPathInCache('csv');
        $this->excelDataTableWriter->generateTextFileByEntityList($generator, $mapping, $path);

        return $path;
    }

    public function generateXlsx(string $exportName, QueryBuilder $queryBuilder, bool $isComplex = false): string
    {
        $mapping = $this->exportConfiguration->getExportConfigurations($exportName);
        $generator = $this->dataGenerator($queryBuilder, $isComplex);

        $this->excelReport->setTemplate(static::TEMPLATE_XLSX_PATH);
        $this->excelReport->generateSheetByEntityList(static::SHEET_TITLE, $mapping, $generator);

        return $this->excelReport->saveExcel();
    }

    private function dataGenerator(QueryBuilder $queryBuilder, bool $isComplex): \Generator
    {
        $batch = new QueryBuilderBatchIterator($queryBuilder);
        if ($isComplex) {
            $batch->setComplexSelectMode();
        } else {
            $batch->setSimpleSelectMode();
        }

        foreach ($batch as $row) {
            yield $row;

            if (is_object($row)) {
                $queryBuilder->getEntityManager()->detach($row);
            }
        }
    }
}
