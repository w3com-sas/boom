<?php

namespace W3com\BoomBundle\Repository;

use W3com\BoomBundle\HanaEntity\AbstractEntity;
use W3com\BoomBundle\Parameters\Parameters;
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

    public function find($id, Parameters $params = null)
    {
        if ($this->read == BoomConstants::SL) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasSL."($quotes".$id."$quotes)";
            $uri .= ($params == null) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif ($this->read == BoomConstants::ODS) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasODS."($quotes".$id."$quotes)";
            $uri .= $params->setFormat('json')->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
        } else {
            throw new \Exception("Unknown entity READ method");
        }
        if ($res == null) {
            return null;
        }
        return $this->hydrate($res);
    }

    public function findAll(Parameters $params = null)
    {
        if ($this->read == BoomConstants::SL) {
            $uri = $this->aliasSL;
            $uri .= ($params == null) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif ($this->read == BoomConstants::ODS) {
            $uri = $this->aliasODS;
            if ($params === null) {
                $params = $this->createParams();
            }
            $uri .= $params->setFormat('json')->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
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

    public function findByEquals(Parameters $params = null)
    {
        if ($this->read == BoomConstants::SL) {
            $uri = $this->aliasSL;
            $uri .= ($params == null) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif ($this->read == BoomConstants::ODS) {
            $uri = $this->aliasODS;
            if ($params === null) {
                $params = $this->createParams();
            }
            $uri .= $params->setFormat('json')->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
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
        if ($this->read == BoomConstants::SL) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $res = $this->manager->restClients['sl']->delete($this->aliasSL."($quotes".$id."$quotes)");
        } elseif ($this->read == BoomConstants::ODS) {

        } else {
            throw new \Exception("Unknown entity READ method");
        }
    }

    public function count(Parameters $params = null)
    {
        if ($this->read == BoomConstants::SL) {
            $uri = $this->aliasSL.'/$count';
            $uri .= ($params == null) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif ($this->read == BoomConstants::ODS) {
            $uri = $this->aliasODS.'/$count';
            if ($params === null) {
                $params = $this->createParams();
            }
            $uri .= ($params == null) ? '' : $params->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
        } else {
            throw new \Exception("Unknown entity READ method");
        }
        return $res;
    }

    public function update(AbstractEntity $entity, $id)
    {
        if ($this->write == BoomConstants::SL) {
            // update
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasSL."($quotes".$id."$quotes)";
            $data = array();
            $data[$this->columns[$this->key]['column']] = $entity->get($this->key);
            foreach ($entity->getChangedFields() as $field => $value) {
                $data[$this->columns[$field]['column']] = $entity->get($field);
            }
            $res = $this->manager->restClients['sl']->patch($uri, $data);
            $entity->hydrate('changedFields', array());

            return $entity;
        } elseif ($this->write == BoomConstants::ODS) {

        } else {
            throw new \Exception("Unknown entity WRITE method");
        }
    }

    public function add(AbstractEntity $entity)
    {
        if ($this->write == BoomConstants::SL) {
            $uri = $this->aliasSL;
            $data = array();
            foreach ($entity->getChangedFields() as $field => $value) {
                $data[$this->columns[$field]['column']] = $entity->get($field);
            }
            $res = $this->manager->restClients['sl']->post($uri, $data);

            return $this->hydrate($res);
        } elseif ($this->write == BoomConstants::ODS) {

        } else {
            throw new \Exception("Unknown entity WRITE method");
        }
    }

    public function hydrate($array)
    {
        /** @var AbstractEntity $obj */
        $obj = new $this->className();
        foreach ($this->columns as $attribute => $column) {
            if (array_key_exists($column['column'], $array)) {
                $obj->hydrate($attribute, $array[$column['column']]);
            }
        }

        return $obj;
    }

    public function rehydrate(AbstractEntity $obj, $array)
    {
        foreach ($this->columns as $attribute => $column) {
            if (array_key_exists($column['column'], $array)) {
                $obj->set($attribute, $array[$column['column']]);
            }
        }

        return $obj;
    }

    public function getEntityName()
    {
        return $this->entityName;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function createParams()
    {
        return new Parameters($this->columns);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getNextCode()
    {
        if ($this->key == 'code') {
            $params = $this->createParams();
            $params->setTop(1)->addOrder('code', Parameters::ORDER_DESC);
            $res = $this->findAll($params);
            switch (count($res)) {
                case 0 :
                    return 1000000000;
                    break;
                default:
                    return intval($res[0]->getCode()) + 1;
                    break;
            }
        } else {
            throw new \Exception('Unsupported entity, is there a Code column ?');
        }
    }
}