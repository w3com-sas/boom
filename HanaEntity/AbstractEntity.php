<?php


namespace W3com\BoomBundle\HanaEntity;


class AbstractEntity implements EntityInterface
{
    public function __construct($rawData)
    {
        foreach ($this->columns as $column => $value) {
            $this->$column = $rawData[$value] ?? null;
        }
    }
}