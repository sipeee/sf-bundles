<?php

namespace Company\ExportBundle\Service\Writer;

use Company\ExportBundle\Service\FieldTransformerComposite;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ExcelDataTableWriter
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_TEXT = 'txt';
    public const BUFFER_SIZE = 2048;

    public const UPDATE_EXPORT_PROGRESS_CACHE_MODULUS = 2000; //Update db cache to show export progress info for users

    private PropertyAccessorInterface $propertyAccessor;
    private FieldTransformerComposite $transformer;

    private array $options = [
        'headerRowIndex' => 1,
        'dataStartRowIndex' => null,
        'dateFormat' => 'Y-m-d H:i:s',
    ];

    private ?\PHPExcel_Worksheet $sheet;

    private array $fieldMapping;

    public function __construct(PropertyAccessorInterface $propertyAccessor, FieldTransformerComposite $transformer)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->transformer = $transformer;
    }

    /**
     * @param array|iterable $entityList
     */
    public function generateCsvFileByEntityList($entityList, array $fieldMapping, string $filePath, ?\Closure $closure = null): void
    {
        $this->generateCsvOrTextFileByEntityList($entityList, $fieldMapping, $filePath, self::FORMAT_CSV, $closure);
    }

    /**
     * @param array|iterable $entityList
     */
    public function generateTextFileByEntityList($entityList, array $fieldMapping, string $filePath, ?\Closure $closure = null): void
    {
        $this->generateCsvOrTextFileByEntityList($entityList, $fieldMapping, $filePath, self::FORMAT_TEXT, $closure);
    }

    /**
     * @param array|iterable $entityList
     * @param resource       $stream
     */
    public function generateCsvStreamByEntityList($entityList, array $fieldMapping, $stream, ?\Closure $closure = null): void
    {
        $this->generateCsvOrTextStreamByEntityList($entityList, $fieldMapping, $stream, self::FORMAT_CSV, $closure);
    }

    /**
     * @param array|iterable $entityList
     * @param resource       $stream
     */
    public function generateTextStreamByEntityList($entityList, array $fieldMapping, $stream, ?\Closure $closure = null): void
    {
        $this->generateCsvOrTextStreamByEntityList($entityList, $fieldMapping, $stream, self::FORMAT_TEXT, $closure);
    }

    /**
     * @param array|iterable $entityList
     */
    public function writeByEntityList(\PHPExcel_Worksheet $sheet, array $fieldMapping, $entityList, array $options = []): void
    {
        $this->sheet = $sheet;
        $this->fieldMapping = $fieldMapping;
        $this->setStartRowIndex($options);
        $this->createHeaderByMapping();
        $this->createRowsByEntityList($entityList);
    }

    /**
     * @param array|iterable $rows
     */
    public function writeByRawData(\PHPExcel_Worksheet $sheet, $rows, array $options = []): void
    {
        $this->sheet = $sheet;
        $this->setDefaultStartRowToFirstRow($options);
        $this->setStartRowIndex($options);
        $this->createRowsByRawData($rows);
    }

    public function createRowsByRawData(array $rows): void
    {
        $rowIndex = $this->options['dataStartRowIndex'];
        foreach ($rows as $row) {
            $columnIndex = 0;
            foreach ($row as $cellValue) {
                $pCoordinate = $this->getCoordinate($columnIndex, $rowIndex);
                $this->sheet->setCellValue($pCoordinate, $cellValue);
                ++$columnIndex;
            }
            ++$rowIndex;
        }
    }

    public function applyCellStyle(\PHPExcel_Worksheet $sheet, int $rowIndex, int $columnIndex, array $style): void
    {
        $this->sheet = $sheet;
        $coordinate = $this->getCoordinate($columnIndex, $rowIndex);
        $this->sheet->getStyle($coordinate)->applyFromArray($style);
    }

    public function applyNumberFormatOnCell(\PHPExcel_Worksheet $sheet, int $rowIndex, int $columnIndex, string $decimalSep = '.', string $thousandSep = ','): void
    {
        $this->sheet = $sheet;
        $coordinate = $this->getCoordinate($columnIndex, $rowIndex);
        $this->sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('#'.$thousandSep.'##0'.$decimalSep.'00');
    }

    /**
     * If options parameter does not have 'dataStartRowIndex'
     * we calculate from 'headerRowIndex + 1' (assume that the header row is one row height).
     */
    public function setStartRowIndex(array $options): void
    {
        $this->options = array_merge($this->options, $options);
        if (empty($this->options['dataStartRowIndex'])) {
            $this->options['dataStartRowIndex'] = $this->options['headerRowIndex'] + 1;
        }
        if (!isset($this->options['dateFormat'])) {
            $this->options['dateFormat'] = 'Y-m-d H:i:s';
        }
    }

    /**
     * @param array|iterable $entityList
     */
    private function generateCsvOrTextFileByEntityList($entityList, array $fieldMapping, string $filePath, string $type, \Closure $closure = null): void
    {
        $fileStream = fopen($filePath, 'w');
        stream_set_write_buffer($fileStream, self::BUFFER_SIZE);

        $this->generateCsvOrTextStreamByEntityList($entityList, $fieldMapping, $fileStream, $type, $closure);

        fclose($fileStream);
    }

    /**
     * @param array|iterable $entityList
     * @param resource       $stream
     */
    private function generateCsvOrTextStreamByEntityList($entityList, array $fieldMapping, $stream, string $type, ?\Closure $closure = null): void
    {
        if (null === $closure) {
            $closure = function () {
            };
        }

        $closure(null, null);
        $count = (is_array($entityList) || ($entityList instanceof \Countable))
            ? count($entityList)
            : null;

        if (self::FORMAT_CSV === $type) {
            $this->writeCsvHeader($stream, $fieldMapping);
        }

        foreach ($entityList as $index => $entityData) {
            if (0 == $index % self::UPDATE_EXPORT_PROGRESS_CACHE_MODULUS) {
                $closure($index, $count);
            }

            $this->writeCsvOrTextRowByEntity($stream, $fieldMapping, $entityData, $type);
        }

        $closure($count, $count);
    }

    private function createHeaderByMapping(): void
    {
        $columnIndex = 0;
        foreach ($this->fieldMapping['fields'] as $field) {
            $pCoordinate = $this->getCoordinate($columnIndex, $this->options['headerRowIndex']);
            $this->sheet->setCellValue($pCoordinate, $this->getHeaderByFieldConfig($field));
            if (isset($field['size'])) {
                $this->sheet->getColumnDimensionByColumn($columnIndex)->setWidth($field['size']);
            }

            ++$columnIndex;
        }
    }

    /**
     * @param array|iterable $entityList
     */
    private function createRowsByEntityList($entityList): void
    {
        $rowIndex = $this->options['headerRowIndex'] + 1;
        foreach ($entityList as $entity) {
            $columnIndex = 0;
            foreach ($this->fieldMapping['fields'] as $field) {
                $pCoordinate = $this->getCoordinate($columnIndex, $rowIndex);
                if (isset($this->options['verticalAlignText'])) {
                    $this->sheet->getStyle($pCoordinate)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_TOP);
                }

                $fieldValue = $this->getFieldValue($field, $entity);

                if (isset($field['image'])) {
                    $this->writeImage($rowIndex, $columnIndex, $fieldValue, $field);
                } elseif (isset($field['link'])) {
                    $this->writeLink($rowIndex, $columnIndex, $fieldValue, $field['link']);
                } else {
                    if (isset($field['explicitValue']) && true == $field['explicitValue']) {
                        $this->sheet->setCellValueExplicit($pCoordinate, $fieldValue);
                    } else {
                        $this->sheet->setCellValue($pCoordinate, $fieldValue);
                    }

                    $this->sheet->getStyleByColumnAndRow($columnIndex, $rowIndex)->getAlignment()->setWrapText(true);
                }

                ++$columnIndex;
            }

            ++$rowIndex;
        }
    }

    /**
     * @param resource $fileStream
     *
     * @return string
     */
    private function writeCsvHeader($fileStream, array $fieldMapping): void
    {
        $headers = [];

        foreach ($fieldMapping['fields'] as $fieldConfig) {
            $headers[] = self::getHeaderByFieldConfig($fieldConfig);
        }

        fputcsv($fileStream, $headers);
    }

    /**
     * @param resource     $fileStream
     * @param array|object $entity
     */
    private function writeCsvOrTextRowByEntity($fileStream, array $fieldMapping, $entity, string $format)
    {
        $row = [];
        foreach ($fieldMapping['fields'] as $fieldConfig) {
            $fieldValue = $this->getFieldValue($fieldConfig, $entity);
            $header = self::getHeaderByFieldConfig($fieldConfig);

            $row[$header] = $fieldValue;
        }

        switch ($format) {
            case self::FORMAT_CSV:
                fputcsv($fileStream, array_values($row));

                break;
            case self::FORMAT_TEXT:
                foreach ($row as $fieldHeader => $fieldValue) {
                    fwrite($fileStream, sprintf("%s: %s\r\n", $fieldHeader, $fieldValue));
                }
                fwrite($fileStream, "================================================================\r\n\r\n");

                break;
        }
    }

    private function getCoordinate(int $columnIndex, int $rowIndex): ?string
    {
        return \PHPExcel_Cell::stringFromColumnIndex($columnIndex).$rowIndex;
    }

    private function setDefaultStartRowToFirstRow(array $options): void
    {
        if (empty($options)) {
            $this->options['dataStartRowIndex'] = 1;
        }
    }

    /**
     * @param array|object $entity
     */
    private function getFieldValue(array $fieldConfig, $entity): ?string
    {
        $fieldValue = $this->propertyAccessor->getValue($entity, $fieldConfig['fieldName']);

        return $this->transformer->transform($fieldValue, $fieldConfig['transformers']);
    }

    private function writeImage(int $rowIndex, int $columnIndex, File $file, array $fieldConfig): void
    {
        //TODO: include, implement
    }

    private function writeLink(int $rowIndex, int $columnIndex, string $url, array $link): void
    {
        $linkStyle = [
            'font' => [
                'color' => ['rgb' => '0000FF'],
                'underline' => 'single',
            ],
        ];
        $coordinate = $this->getCoordinate($columnIndex, $rowIndex);

        $cell = $this->sheet->getCellByColumnAndRow($columnIndex, $rowIndex);
        $cell->getHyperlink()->setUrl($url);
        $cell->setValue($link['label']);
        $this->sheet->getStyle($coordinate)->applyFromArray($linkStyle);
    }

    private static function getHeaderByFieldConfig(array $fieldConfig): string
    {
        return isset($fieldConfig['header']) && !empty($fieldConfig['header'])
            ? $fieldConfig['header']
            : $fieldConfig['fieldName'];
    }
}
