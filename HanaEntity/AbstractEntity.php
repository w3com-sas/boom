<?php


namespace W3com\BoomBundle\HanaEntity;


class AbstractEntity implements EntityInterface
{
    private $columns;

    public function __construct($rawData)
    {
        foreach ($this->columns as $column => $value) {
            $this->$column = $rawData[$value] ?? null;
        }
    }
}