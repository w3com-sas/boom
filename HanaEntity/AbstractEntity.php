<?php

namespace W3com\BoomBundle\HanaEntity;

class AbstractEntity
{

    public function set($field, $value)
    {
        $this->$field = $value;
    }
}