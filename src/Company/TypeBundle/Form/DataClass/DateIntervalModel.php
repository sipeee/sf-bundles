<?php

namespace Company\TypeBundle\Form\DataClass;

class DateIntervalModel
{
    public const DEFAULT_TIMEZONE = 'UTC';

    public const TYPE_PAST_7_DAYS = 'past_7_days';
    public const TYPE_PAST_30_DAYS = 'past_30_days';
    public const TYPE_PAST_60_DAYS = 'past_60_days';
    public const TYPE_PAST_90_DAYS = 'past_90_days';
    public const TYPE_THIS_YEAR = 'this_year';
    public const TYPE_LAST_YEAR = 'last_year';
    public const TYPE_CUSTOM = 'custom';

    public const RESOLUTION_HIGH = 1; // 1 day
    public const RESOLUTION_MEDIUM = 6; // 6 days
    public const RESOLUTION_LOW = 7 * 3; // 3 weeks

    public const THRESHOLD_MEDIUM_RESOLUTION = 30; // 30 days
    public const THRESHOLD_LOW_RESOLUTION = 30 * 6; // 6 months

    /** @var \DateTime|null */
    private $dateFrom;

    /** @var \DateTime|null */
    private $dateTo;

    public function __construct(\DateTime $dateFrom = null, \DateTime $dateTo = null)
    {
        $this->setDateFrom($dateFrom);
        $this->setDateTo($dateTo);
    }

    public static function createByType(string $type, int $dayOffset = 0, string $timeZone = self::DEFAULT_TIMEZONE)
    {
        $predefinedValues = self::createPredefinedValues($dayOffset, $timeZone);

        if (!isset($predefinedValues[$type])) {
            throw new \InvalidArgumentException(sprintf('Type "%s" is invalid. Possible types: "%s"', $type, implode('", "', array_keys($predefinedValues))));
        }

        return $predefinedValues[$type];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_PAST_7_DAYS,
            self::TYPE_PAST_30_DAYS,
            self::TYPE_PAST_60_DAYS,
            self::TYPE_PAST_90_DAYS,
            self::TYPE_THIS_YEAR,
            self::TYPE_LAST_YEAR,
            self::TYPE_CUSTOM,
        ];
    }

    /**
     * @throws \Exception
     *
     * @return array|DateIntervalModel[]
     */
    public static function createPredefinedValues(int $dayOffset = 0, string $timeZone = self::DEFAULT_TIMEZONE): array
    {
        $predefinedValues = [];

        $dateTo = new \DateTime('now', new \DateTimeZone($timeZone));
        $dateTo->setTime(0, 0, 0);
        $dateTo->setTimezone(new \DateTimeZone(self::DEFAULT_TIMEZONE));

        if (0 < $dayOffset) {
            $dateTo->add(new \DateInterval(sprintf('P%dD', $dayOffset)));
        } elseif ($dayOffset < 0) {
            $dateTo->sub(new \DateInterval(sprintf('P%dD', (-1) * $dayOffset)));
        }

        $dateFrom = clone $dateTo;
        $dateFrom->sub(new \DateInterval('P6D'));

        $predefinedValues[self::TYPE_PAST_7_DAYS] = new self($dateFrom, $dateTo);

        $dateTo = clone $dateTo;
        $dateFrom = clone $dateTo;
        $dateFrom->sub(new \DateInterval('P29D'));

        $predefinedValues[self::TYPE_PAST_30_DAYS] = new self($dateFrom, $dateTo);

        $dateTo = clone $dateTo;
        $dateFrom = clone $dateTo;
        $dateFrom->sub(new \DateInterval('P59D'));

        $predefinedValues[self::TYPE_PAST_60_DAYS] = new self($dateFrom, $dateTo);

        $dateFrom = clone $dateTo;
        $dateFrom->sub(new \DateInterval('P89D'));

        $predefinedValues[self::TYPE_PAST_90_DAYS] = new self($dateFrom, $dateTo);

        $dateTo = clone $dateTo;
        $dateFrom = clone $dateTo;
        $dateFrom->setDate($dateTo->format('Y'), 1, 1);

        $predefinedValues[self::TYPE_THIS_YEAR] = new self($dateFrom, $dateTo);

        $dateTo = clone $dateTo;
        $dateTo->setDate($dateTo->format('Y') - 1, 12, 31);
        $dateFrom = clone $dateTo;
        $dateFrom->setDate($dateTo->format('Y'), 1, 1);

        $predefinedValues[self::TYPE_LAST_YEAR] = new self($dateFrom, $dateTo);

        return $predefinedValues;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @return DateIntervalModel
     */
    public function setDateFrom(\DateTime $dateFrom = null)
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @return DateIntervalModel
     */
    public function setDateTo(\DateTime $dateTo = null)
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    /**
     * @return int
     */
    public function getResolution()
    {
        if ($this->isOpenInterval()) {
            return self::RESOLUTION_HIGH;
        }

        $dateDiffInDays = $this->getDayDifference();
        if ($dateDiffInDays > self::THRESHOLD_LOW_RESOLUTION) {
            return self::RESOLUTION_LOW;
        }

        if ($dateDiffInDays > self::THRESHOLD_MEDIUM_RESOLUTION) {
            return self::RESOLUTION_MEDIUM;
        }

        return self::RESOLUTION_HIGH;
    }

    /**
     * @return bool
     */
    public function isEqualTo(DateIntervalModel $dateInterval)
    {
        return $dateInterval->getDateFrom() == $this->getDateFrom() && $dateInterval->getDateTo() == $this->getDateTo();
    }

    /**
     * @return int
     */
    public function getDayDifference()
    {
        return (int) ($this->dateTo->diff($this->dateFrom)->format('%a'));
    }

    /**
     * @return bool
     */
    private function isOpenInterval()
    {
        return !$this->dateFrom || !$this->dateTo;
    }
}
