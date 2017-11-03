<?php

namespace W3com\BoomBundle\Repository;

use GuzzleHttp\Client;
use W3com\BoomBundle\HanaEntity\AbstractEntity;
use W3com\BoomBundle\Service\BoomConstants;
use W3com\BoomBundle\Service\BoomManager;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var BoomManager
     */
    private $manager;

    /**
     * @var string
     */
    private $read;

    /**
     * @var string
     */
    private $write;

    /**
     * @var string
     */
    private $aliasSL;

    /**
     * @var string
     */
    private $aliasODS;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $columns;

    /**
     * AbstractRepository constructor.
     *
     * @param string $entityName
     * @param $className
     * @param BoomManager $manager
     * @param string $read
     * @param string $write
     * @param string $aliasSL
     * @param string $aliasODS
     * @param string $key
     * @param array $columns
     */
    public function __construct($entityName, $className, $manager, $read, $write, $aliasSL, $aliasODS, $key, $columns)
    {
        $this->entityName = $entityName;
        $this->className = $className;
        $this->manager = $manager;
        $this->read = $read;
        $this->write = $write;
        $this->aliasSL = $aliasSL;
        $this->aliasODS = $aliasODS;
        $this->key = $key;
        $this->columns = $columns;
    }

    public function find($id)
    {
        /** @var Client $client */
        if ($this->read == BoomConstants::SL) {
            $res = $this->manager->restClients['sl']->get($this->aliasSL."('".$id."')");
        } elseif ($this->read == BoomConstants::ODS) {

        } else {
            throw new \Exception("Unknown entity READ method");
        }

        return $this->hydrate($res);
    }

    public function findAll()
    {
        /** @var Client $client */
        if ($this->read == BoomConstants::SL) {
            $res = $this->manager->restClients['sl']->get($this->aliasSL);
        } elseif ($this->read == BoomConstants::ODS) {

        } else {
            throw new \Exception("Unknown entity READ method");
        }
        $ret = array();
        foreach ($res as $array) {
            $ret[] = $this->hydrate($array);
        }

        return $ret;
    }

    public function findBy(array $criteria)
    {

    }

    public function findByEquals(array $criteria)
    {
        /** @var Client $client */
        if ($this->read == BoomConstants::SL) {
            $uri = $this->aliasSL.'?$filter=';
            $filterAr = array();
            foreach ($criteria as $field => $value) {
                $filterAr[] = $this->columns[$field]['column']."%20eq%20'".$value."'";
            }
            $filter = implode('%20and%20', $filterAr);
            dump($uri.$filter);
            $res = $this->manager->restClients['sl']->get($uri.$filter);
        } elseif ($this->read == BoomConstants::ODS) {

        } else {
            throw new \Exception("Unknown entity READ method");
        }
        $ret = array();
        foreach ($res as $array) {
            $ret[] = $this->hydrate($array);
        }

        return $ret;
    }

    public function delete($id)
    {

    }

    public function count()
    {

    }

    public function persist($entity, $id)
    {

    }

    public function hydrate($array)
    {
        /** @var AbstractEntity $obj */
        $obj = new $this->className();
        foreach ($this->columns as $attribute => $column) {
            $obj->set($attribute, $array[$column['column']]);
        }

        return $obj;
    }

    public function getEntityName()
    {
        return $this->entityName;
    }
}