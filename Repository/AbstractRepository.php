<?php

namespace W3com\BoomBundle\Repository;

use GuzzleHttp\Client;
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
     * AbstractRepository constructor.
     *
     * @param string $entityName
     * @param BoomManager $manager
     * @param string $read
     * @param string $write
     * @param string $aliasSL
     * @param string $aliasODS
     */
    public function __construct($entityName, $className, $manager, $read, $write, $aliasSL, $aliasODS, $key)
    {
        $this->entityName = $entityName;
        $this->className = $className;
        $this->manager = $manager;
        $this->read = $read;
        $this->write = $write;
        $this->aliasSL = $aliasSL;
        $this->aliasODS = $aliasODS;
        $this->key = $key;
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

        return new $this->className($res);
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
            $obj = new $this->className($array);
            $ret[] = $obj;
        }

        return $ret;
    }

    public function findBy(array $criteria)
    {

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

    public function getEntityName()
    {
        return $this->entityName;
    }
}