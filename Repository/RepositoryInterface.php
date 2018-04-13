<?php

namespace W3com\BoomBundle\Repository;

use W3com\BoomBundle\HanaEntity\AbstractEntity;

interface RepositoryInterface
{
    public function find($id);

    public function findAll();

    public function findBy(array $criteria);

    public function getEntityName();

    public function delete($id);

    public function count();

    public function update(AbstractEntity $entity, $id);

    public function add(AbstractEntity $entity);
}
