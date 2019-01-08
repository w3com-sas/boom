<?php

namespace W3com\BoomBundle\Exception;

class EntityNotFoundException extends \Exception
{
    public function __construct($entity = null)
    {
        $code = 0;
        $message = 'Missing entity : '. $entity;
        parent::__construct($message, $code, null);
    }

}