<?php

namespace W3com\BoomBundle\Generator;

use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;

class Messenger
{

    private $messages = [];

    public function buildUpdateEntityMessage(Entity $entity)
    {
        $this->messages[$entity->getTable()] = [];

        /** @var Property $property */
        foreach ($entity->getProperties() as $property){
            $this->messages[$entity->getTable()][] = $property->getField();
        }
    }

    public function getMessages()
    {
        return $this->messages;
    }
}