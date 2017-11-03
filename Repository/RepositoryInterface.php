<?php


namespace W3com\BoomBundle\Repository;


interface RepositoryInterface
{
    public function find($id);

    public function findAll();

    public function findBy(array $criteria);

    public function getEntityName();

    public function delete($id);

    public function count();

    public function persist($entity, $id);
}