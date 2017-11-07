<?php

namespace W3com\BoomBundle\HanaEntity;

class AbstractEntity
{
    protected $changedFields = array();

    public function set($field, $value)
    {
        $this->$field = $value;
        $this->changedFields[$field] = true;

        return $this;
    }

    public function get($field)
    {
        return $this->$field;
    }

    public function hydrate($field, $value)
    {
        $this->$field = $value;
    }

    public function getChangedFields()
    {
        return $this->changedFields;
    }
}