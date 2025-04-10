<?php

namespace Company\FormFilterBundle\Presentation;

use Doctrine\Common\Collections\Collection;

/**
 * FormFilter.
 */
class FormFilter
{
    public const LOGIC_AND = 'and';
    public const LOGIC_OR = 'or';

    public ?string $filterType;

    public ?string $propertyPath;

    public array $filterOptions;

    public string $aliasSuffix;

    public string $logic;

    /** @var array|FormFilter[] */
    public array $subFilters;

    public ?string $fieldAlias = null;

    public $value = null;

    /**
     * @param array|FormFilter[] $subFilters
     */
    private function __construct(
        ?string $filterType = null,
        ?string $propertyPath = null,
        array $filterOptions = [],
        string $aliasSuffix = '',
        string $logic = self::LOGIC_AND,
        array $subFilters = []
    ) {
        $this->filterType = $filterType;
        $this->propertyPath = $propertyPath;
        $this->filterOptions = $filterOptions;
        $this->aliasSuffix = $aliasSuffix;
        $this->logic = $logic;
        $this->subFilters = $subFilters;
    }

    /**
     * @param array|FormFilter[] $subFilters
     */
    public static function logic(
        string $logic,
        array $subFilters
    ): self {
        return new self(null, null, [], '', $logic, $subFilters);
    }

    public static function create(
        string $filterType = null,
        string $propertyPath = null,
        array $filterOptions = [],
        string $aliasSuffix = ''
    ): self {
        return new self(
            $filterType,
            $propertyPath,
            $filterOptions,
            $aliasSuffix
        );
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $value = $this->value;

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return $value;
    }
}
