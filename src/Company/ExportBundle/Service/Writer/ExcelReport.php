<?php

namespace Company\ExportBundle\Service\Writer;

use Company\ExportBundle\Service\TmpFileCacheService;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * ExcelReport.
 */
class ExcelReport
{
    private ExcelDataTableWriter $excelDataTableWriter;
    private TmpFileCacheService $tmpFileCacheService;
    private KernelInterface $kernel;

    private ?\PHPExcel $excel;

    public function __construct(ExcelDataTableWriter $excelDataTableWriter, TmpFileCacheService $tmpFileCacheService, KernelInterface $kernel)
    {
        $this->excelDataTableWriter = $excelDataTableWriter;
        $this->tmpFileCacheService = $tmpFileCacheService;
        $this->kernel = $kernel;
    }

    /**
     * @param array|iterable $entityList
     */
    public function generateSheetByEntityList(string $sheetTitle, array $mapping, $entityList): void
    {
        $sheet = $this->getOrCreateSheet($sheetTitle);
        $this->excelDataTableWriter->writeByEntityList($sheet, $mapping, $entityList);
    }

    /**
     * @param array|iterable $rows
     */
    public function generateSheetByRawData(string $sheetTitle, $rows): void
    {
        $sheet = $this->getOrCreateSheet($sheetTitle);
        $this->excelDataTableWriter->writeByRawData($sheet, $rows);
    }

    public function setTemplate(string $filePath): void
    {
        $filePath = $this->kernel->locateResource($filePath);

        try {
            $obj = new \PHPExcel_Reader_Excel5();
            $this->excel = $obj->load($filePath);
        } catch (\Exception $e) {
            $obj = new \PHPExcel_Reader_Excel2007();
            $this->excel = @$obj->load($filePath);
        }

        if (0 == $this->excel->getSheetCount()) {
            $this->excel = null;
        }
    }

    public function initialize(): void
    {
        $this->excel = new \PHPExcel();
        $this->excel->removeSheetByIndex(0);
    }

    public function saveExcel(string $type = 'xlsx'): string
    {
        $path = $this->tmpFileCacheService->createTmpPathInCache($type);

        if ('xlsx' == $type) {
            $excelWriter = new \PHPExcel_Writer_Excel2007($this->excel);
        } elseif ('xls' == $type) {
            $excelWriter = new \PHPExcel_Writer_Excel5($this->excel);
        } else {
            throw new \Exception('Invalid file type, must be xlsx or xls');
        }

        $excelWriter->save($path);

        return $path;
    }

    public function applyCellStyle(string $sheetTitle, int $rowIndex, int $columnIndex, array $style): void
    {
        $sheet = $this->getOrCreateSheet($sheetTitle);
        $this->excelDataTableWriter->applyCellStyle($sheet, $rowIndex, $columnIndex, $style);
    }

    public function applyNumberFormatOnCell(string $sheetTitle, int $rowIndex, int $columnIndex): void
    {
        $sheet = $this->getOrCreateSheet($sheetTitle);
        $this->excelDataTableWriter->applyNumberFormatOnCell($sheet, $rowIndex, $columnIndex);
    }

    private function getOrCreateSheet($sheetTitle): \PHPExcel_Worksheet
    {
        $sheet = $this->excel->getSheetByName($sheetTitle);
        if (!$sheet) {
            $sheet = $this->excel->createSheet();
            $sheet->setTitle($sheetTitle);
        }

        return $sheet;
    }
}
