<?php

namespace W3com\BoomBundle\Parameters;

class Clause
{
    const EQUALS = '%s eq %s';
    const NOT_EQUALS = '%s ne %s';
    const STARTS_WITH = 'startswith(%s,%s)';
    const ENDS_WITH = 'endswith(%s,%s)';
    const CONTAINS = 'contains(%s,%s)';
    const SUBSTRINGOF = 'substringof(%s,%s)';
    const GREATER_THAN = '%s gt %s';
    const GREATER_OR_EQUAL = '%s ge %s';
    const LOWER_THAN = '%s lt %s';
    const LOWER_OR_EQUAL = '%s le %s';
    const AND = ' and ';
    const OR = ' or ';
    const TO_LOWER = 'tolower(%s)';

    private $column;
    private $value;
    private $operator;
    private $quote;
    private $logicalOperator;
    private $transformFunction;

    public function __construct($column, $value, $operator, $quote, $logicalOperator = self:: AND, $transformFunction = null)
    {
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
        $this->quote = $quote ? "'" : '';
        $this->transformFunction = $transformFunction;
        $this->logicalOperator = (self:: OR == $logicalOperator) ? self:: OR : self:: AND;
    }

    /**
     * @return string
     */
    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }

    public function render()
    {
        if (is_array($this->value)) {
            $tmp = [];
            foreach ($this->value as $value) {
                // gestion du null dans les calculation views
                $quote = (null === $value) ? '' : $this->quote;
                $value = (null === $value) ? 'null' : $quote.$value.$quote;
                $value = (null === $this->transformFunction) ? $value : sprintf($this->transformFunction, $value);
                $this->column = (null === $this->transformFunction) ? $this->column : sprintf($this->transformFunction, $this->column);
                $tmp[] = sprintf($this->operator, $this->column, $value);
            }
            $retour = '(' . implode(' or ', $tmp) . ')';
        } else {
            // gestion du null dans les calculation views
            $quote = (null === $this->value) ? '' : $this->quote;
            $value = (null === $this->value) ? 'null' : $quote.$this->value.$quote;
            $value = (null === $this->transformFunction) ? $value : sprintf($this->transformFunction, $value);
            $this->column = (null === $this->transformFunction) ? $this->column : sprintf($this->transformFunction, $this->column);
            $retour = sprintf($this->operator, $this->column,  $value);
        }

        return $retour;
    }
}
