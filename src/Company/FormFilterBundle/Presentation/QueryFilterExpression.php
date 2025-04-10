<?php

namespace Company\FormFilterBundle\Presentation;

class QueryFilterExpression
{
    private string $expression;
    private array $parameters;

    public function __construct(string $expression, array $parameters)
    {
        $this->expression = $expression;
        $this->parameters = $parameters;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
