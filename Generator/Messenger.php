<?php

namespace W3com\BoomBundle\Generator;

use W3com\BoomBundle\Generator\Model\Entity;

class Messenger
{

    private $updatedEntities = [];

    private $createdEntities = [];


    public function addUpdatedEntity(Entity $entity)
    {
        $this->updatedEntities[] = $entity;
    }

    /**
     * @return array
     */
    public function getUpdatedEntities(): array
    {
        return $this->updatedEntities;
    }

    /**
     * @return array
     */
    public function getCreatedEntities(): array
    {
        return $this->createdEntities;
    }

    /**
     * @param $createdEntity
     */
    public function addCreatedEntities($createdEntity)
    {
        $this->createdEntities[] = $createdEntity;
    }

}