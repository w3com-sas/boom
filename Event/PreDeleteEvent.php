<?php

namespace W3com\BoomBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use W3com\BoomBundle\HanaEntity\AbstractEntity;

class PreDeleteEvent extends Event
{

    /** @var AbstractEntity */
    protected $object;

    /** @var string */
    protected $type;

    /**
     * @param object $object
     * @param string $type
     */
    public function __construct($object, $type)
    {
        $this->object = $object;
        $this->type = $type;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

}
