<?php

namespace Company\TypeBundle\Form\Transformer;

use Company\TypeBundle\Form\DataClass\DateIntervalModel;
use Symfony\Component\Form\DataTransformerInterface;

class DateIntervalTransformer implements DataTransformerInterface
{
    private int $predefinedDateOffset;
    private string $viewTimeZone;

    public function __construct(int $predefinedDateOffset, string $viewTimeZone)
    {
        $this->predefinedDateOffset = $predefinedDateOffset;
        $this->viewTimeZone = $viewTimeZone;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function transform($value)
    {
        /** @var DateIntervalModel|null $value */
        if (null === $value) {
            return [
                'type' => null,
                'dateFrom' => null,
                'dateTo' => null,
            ];
        }

        $type = DateIntervalModel::TYPE_CUSTOM;
        foreach ($this->getPredefinedValues() as $key => $dateInterval) {
            if ($dateInterval->isEqualTo($value)) {
                $type = $key;

                break;
            }
        }

        return [
            'type' => $type,
            'dateFrom' => (null !== $value->getDateFrom())
                ? clone $value->getDateFrom()
                : null,
            'dateTo' => (null !== $value->getDateTo())
                ? clone $value->getDateTo()
                : null,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function reverseTransform($value)
    {
        $predefinedValues = $this->getPredefinedValues();
        if (isset($value['type']) && isset($predefinedValues[$value['type']])) {
            return $predefinedValues[$value['type']];
        }

        $model = new DateIntervalModel();
        $model->setDateFrom(
            isset($value['dateFrom'])
                ? clone $value['dateFrom']
                : null
        );
        $model->setDateTo(
            isset($value['dateTo'])
                ? clone $value['dateTo']
                : null
        );

        return $model;
    }

    /**
     * @throws \Exception
     *
     * @return array|DateIntervalModel[]
     */
    private function getPredefinedValues()
    {
        return DateIntervalModel::createPredefinedValues($this->predefinedDateOffset, $this->viewTimeZone);
    }
}
