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
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    private $aliasSL;

    /**
     * @var string
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    private $aliasODS;

    /**
     * @var string
     */
    private $aliasRead;

    /**
     * @var string
     */
    private $aliasWrite;

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
     * @param RepoMetadata $metadata
     */
    public function __construct(RepoMetadata $metadata)
    {
        $this->entityName = $metadata->getEntityName();
        $this->className = $metadata->getEntityClassName();
        $this->manager = $metadata->getManager();
        $this->read = $metadata->getRead();
        $this->write = $metadata->getWrite();
        $this->aliasSL = $metadata->getAliasSl();
        $this->aliasODS = $metadata->getAliasOds();
        $this->aliasRead = $metadata->getAliasRead();
        $this->aliasWrite = $metadata->getAliasWrite();
        $this->key = $metadata->getKey();
        $this->columns = $metadata->getColumns();
    }

    public function find($id)
    {
        if (BoomConstants::SL == $this->read) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = ($this->aliasRead != null) ? $this->aliasRead : $this->aliasSL;
            $uri .= "($quotes".$id."$quotes)";
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODSL == $this->read) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'].$this->aliasRead;
            $uri .= "($quotes".$id."$quotes)";
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = ($this->aliasRead != null) ? $this->aliasRead : $this->aliasODS;
            $uri .= "($quotes".$id."$quotes)";
            $uri .= $this->createParams()->setFormat('json')->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
        } else {
            throw new \Exception('Unknown entity READ method');
        }
        if (null == $res) {
            return null;
        }

        return $this->hydrate($res);
    }

    public function findAll(Parameters $params = null)
    {
        if (BoomConstants::SL == $this->read) {
            $uri = ($this->aliasRead != null) ? $this->aliasRead : $this->aliasSL;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODSL == $this->read) {
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'].$this->aliasRead;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $uri = ($this->aliasRead != null) ? $this->aliasRead : $this->aliasODS;
            if (null === $params) {
                $params = $this->createParams();
            }
            $uri .= $params->setFormat('json')->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
        } else {
            throw new \Exception('Unknown entity READ method');
        }
        $ret = [];
        foreach ($res as $array) {
            $ret[] = $this->hydrate($array);
        }

        return $ret;
    }

    /**
     * @deprecated since version 0.2.3, to be removed in 0.3. Use findAll() and parameters instead.
     */
    /*
    public function findBy(array $criteria)
    {
        @trigger_error(
            'findBy() is deprecated since BOOM version 0.2.3 and will be removed in 0.3. Use findAll() and parameters instead.',
            E_USER_DEPRECATED
        );
    }
    */

    /**
     * @deprecated since version 0.2.3, to be removed in 0.3. Use findAll() and parameters instead.
     */
    public function findByEquals(Parameters $params = null)
    {
        @trigger_error(
            'findByEquals() is deprecated since BOOM version 0.2.3 and will be removed in 0.3. Use findAll() and parameters instead.',
            E_USER_DEPRECATED
        );
        /*
        if (BoomConstants::SL == $this->read) {
            $uri = $this->aliasSL;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $uri = $this->aliasODS;
            if (null === $params) {
                $params = $this->createParams();
            }
            $uri .= $params->setFormat('json')->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
        } else {
            throw new \Exception('Unknown entity READ method');
        }
        $ret = [];
        foreach ($res as $array) {
            $ret[] = $this->hydrate($array);
        }

        return $ret;
        */
    }

    public function delete($id)
    {
        if (BoomConstants::SL == $this->write) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = ($this->aliasWrite != null) ? $this->aliasWrite : $this->aliasSL;

            $res = $this->manager->restClients['sl']->delete($uri."($quotes".$id."$quotes)");
        } elseif (BoomConstants::ODS == $this->read) {
        } else {
            throw new \Exception('Unknown entity delete method');
        }
    }

    public function count(Parameters $params = null)
    {
        if (BoomConstants::SL == $this->read) {
            $uri = ($this->aliasRead != null) ? $this->aliasRead : $this->aliasSL;
            $uri .= '/$count';
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODSL == $this->read) {
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'].$this->aliasRead;
            $uri .= '/$count';
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $uri = ($this->aliasRead != null) ? $this->aliasRead : $this->aliasODS;
            $uri = $uri.'/$count';
            if (null === $params) {
                $params = $this->createParams();
            }
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['odata']->get($uri);
        } else {
            throw new \Exception('Unknown entity READ method');
        }

        return $res;
    }

    public function update(AbstractEntity $entity, $id)
    {
        if (BoomConstants::SL == $this->write) {
            // update
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = ($this->aliasWrite != null) ? $this->aliasWrite : $this->aliasSL;
            $uri .= $uri."($quotes".$id."$quotes)";
            $data = [];
            $data[$this->columns[$this->key]['column']] = $entity->get($this->key);
            foreach ($entity->getChangedFields() as $field => $value) {
                if ($this->columns[$field]['readOnly'] === false) {
                    // on exclut les column en readonly
                    $data[$this->columns[$field]['column']] = $entity->get($field);
                }
            }
            $res = $this->manager->restClients['sl']->patch($uri, $data);
            $entity->hydrate('changedFields', []);

            return $entity;
        } elseif (BoomConstants::ODS == $this->write) {
        } else {
            throw new \Exception('Unknown entity WRITE method');
        }
    }

    public function add(AbstractEntity $entity)
    {
        if (BoomConstants::SL == $this->write) {
            $uri = ($this->aliasWrite != null) ? $this->aliasWrite : $this->aliasSL;
            //$uri = $this->aliasSL;
            $data = [];
            foreach ($entity->getChangedFields() as $field => $value) {
                $data[$this->columns[$field]['column']] = $entity->get($field);
            }
            $res = $this->manager->restClients['sl']->post($uri, $data);

            return $this->hydrate($res);
        } elseif (BoomConstants::ODS == $this->write) {
        } else {
            throw new \Exception('Unknown entity WRITE method');
        }
    }

    public function hydrate($array)
    {
        /** @var AbstractEntity $obj */
        $obj = new $this->className();
        foreach ($this->columns as $attribute => $column) {
            if (array_key_exists($column['column'], $array)) {
                $obj->set($attribute, $array[$column['column']], false);
            } elseif (array_key_exists($column['readColumn'], $array)) {
                $obj->set($attribute, $array[$column['readColumn']], false);
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
     *
     * @throws \Exception
     */
    public function getNextCode()
    {
        if ('code' == $this->key) {
            $params = $this->createParams();
            $params->setTop(1)->addOrder('code', Parameters::ORDER_DESC);
            $res = $this->findAll($params);
            switch (count($res)) {
                case 0:
                    return 1000000000000;
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
