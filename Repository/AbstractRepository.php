<?php

namespace W3com\BoomBundle\Repository;

use phpDocumentor\Reflection\Types\Null_;
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
    private $aliasRead;

    /**
     * @var string
     */
    private $aliasWrite;

    /**
     * @var string
     */
    private $aliasSearch;

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
     * @var RepoMetadata
     */
    private $metadata;

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
        $this->aliasRead = $metadata->getAliasRead();
        $this->aliasWrite = $metadata->getAliasWrite();
        $this->aliasSearch = $metadata->getAliasSearch();
        $this->key = $metadata->getKey();
        $this->columns = $metadata->getColumns();
        $this->metadata = $metadata;
    }

    /**
     * Finds one object in SAP
     *
     * @param $id
     * @return null|AbstractEntity
     * @throws \Exception
     */
    public function find($id)
    {
        if (BoomConstants::SL == $this->read) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasRead;
            $uri .= "($quotes" . $id . "$quotes)";
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODSL == $this->read) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'] . $this->aliasRead;
            $uri .= "($quotes" . $id . "$quotes)";
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasRead;
            $uri .= "($quotes" . $id . "$quotes)";
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

    /**
     * Search for objects in SAP
     *
     * @param Parameters|null $params
     * @return array
     * @throws \Exception
     */
    public function findAll(Parameters $params = null)
    {
        if (BoomConstants::SL == $this->read) {
            $uri = $this->aliasRead;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODSL == $this->read) {
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'] . $this->aliasRead;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $uri = $this->aliasRead;
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
     * Deletes an object from SAP
     *
     * @param $id
     * @throws \Exception
     */
    public function delete($id)
    {
        if (BoomConstants::SL == $this->write) {
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasWrite;
            $res = $this->manager->restClients['sl']->delete($uri . "($quotes" . $id . "$quotes)");
        } elseif (BoomConstants::ODS == $this->read) {
        } else {
            throw new \Exception('Unknown entity delete method');
        }
    }

    /**
     * Counts objects in SAP
     * @param Parameters|null $params
     * @return mixed
     * @throws \Exception
     */
    public function count(Parameters $params = null)
    {
        if (BoomConstants::SL == $this->read) {
            $uri = $this->aliasRead;
            $uri .= '/$count';
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODSL == $this->read) {
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'] . $this->aliasRead;
            $uri .= '/$count';
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri);
        } elseif (BoomConstants::ODS == $this->read) {
            $uri = $this->aliasRead;
            $uri = $uri . '/$count';
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

    public function search(Parameters $params = null)
    {
        $uri = $this->aliasSearch . $params->getIPFilter() . '/Results';
        $uri .= $params->setFormat('json')->getParameters(false);

        $res = $this->manager->restClients['odata']->get($uri);

        $ret = [];

        foreach ($res as $array) {
            $ret[] = $this->hydrate($array);
        }

        return $ret;
    }

    /**
     * Updates an object
     *
     * @param AbstractEntity $entity
     * @param mixed|null $id DEPRECATED
     * @param bool $batch
     * @return AbstractEntity
     * @throws \Exception
     */
    public function update(AbstractEntity $entity, $id = null)
    {
        if ($id === null) {
            $id = $entity->get($this->key);
        } else {
            @trigger_error(
                'When updating an object with Boom, specifying the entity ID is useless since 1.0 and will be removed in 2.0. Remove the second argument.',
                E_USER_DEPRECATED
            );
        }

        if (BoomConstants::SL == $this->write) {
            // update
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasWrite;
            $uri = $uri . "($quotes" . $id . "$quotes)";

            if ($entity->getCollabPackField() != '') {
                // creation of a changedField like array with the collabPack fields ('field1;field2;field3')
                $fields = array_map(function () {
                    return true;
                }, array_flip(explode(';', $entity->getCollabPackField())));
            } else {
                $fields = $entity->getChangedFields();
            }
            $data = $this->getDataToSend($fields, $entity);
            if (count($data) > 0) {
                $this->manager->restClients['sl']->patch($uri, $data);
                $entity->hydrate('changedFields', []);
            }
            return $entity;
        } elseif (BoomConstants::ODS == $this->write) {
        } else {
            throw new \Exception('Unknown entity WRITE method');
        }
    }

    /**
     * Updates an object
     *
     * @param AbstractEntity $entity
     * @param mixed|null $id DEPRECATED
     * @return bool
     * @throws \Exception
     */
    public function cancel(AbstractEntity $entity, $id = null)
    {
        if ($id === null) {
            $id = $entity->get($this->key);
        } else {
            @trigger_error(
                'When updating an object with Boom, specifying the entity ID is useless since 1.0 and will be removed in 2.0. Remove the second argument.',
                E_USER_DEPRECATED
            );
        }

        if (BoomConstants::SL == $this->write) {
            // update
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasWrite;
            $uri = $uri . "($quotes" . $id . "$quotes)";

            $this->manager->restClients['sl']->cancel($uri);

            return true;
        } elseif (BoomConstants::ODS == $this->write) {
            throw new \Exception('Cancel operation is not permitted in this context');
        } else {
            throw new \Exception('Unknown entity WRITE method');
        }
    }

    /**
     * Adds a new object in SAP
     * @param AbstractEntity $entity
     * @return AbstractEntity
     * @throws \Exception
     */
    public function add(AbstractEntity $entity)
    {
        if (BoomConstants::SL == $this->write) {
            $uri = $this->aliasWrite;
            $data = $this->getDataToSend($entity->getChangedFields(), $entity);
            $res = $this->manager->restClients['sl']->post($uri, $data);
            return $this->hydrate($res);
        } elseif (BoomConstants::ODS == $this->write) {
        } else {
            throw new \Exception('Unknown entity WRITE method');
        }
    }

    /**
     * @param array $updateFields
     * @param AbstractEntity $entity
     * @param AbstractRepository|null $repository
     * @return array
     * @throws \Exception
     */
    public function getDataToSend(array $updateFields, AbstractEntity $entity, AbstractRepository $repository = null)
    {
        $repository = $repository === null ? $this : $repository;
        $data = [];
        foreach ($updateFields as $field => $value) {
            if ($repository->columns[$field]['readOnly'] === false && $value && $repository->columns[$field]['complexEntity'] === null) {
                // on exclut les column en readonly
                $data[$repository->columns[$field]['column']] = $entity->get($field);
            } elseif ($repository->columns[$field]['complexEntity'] !== null) {
                $complexData = [];
                $complexRepository = $this->manager->getRepository($repository->columns[$field]['complexEntity']);
                // Si l'objet à plusieurs complexType
                $complexEntities = $entity->get($field);

                if (is_array($complexEntities) && $complexEntities[array_rand($complexEntities)] instanceof AbstractEntity) {
                    /** @var AbstractEntity $complexEntity */
                    foreach ($complexEntities as $complexEntity) {
                        $complexData[] = $this->getDataToSend($complexEntity->getChangedFields(), $complexEntity, $complexRepository);

                    }
                    $data[$repository->columns[$field]['complexColumn']] = $complexData;

                } elseif (($complexEntity = $entity->get($field)) instanceof AbstractEntity) {
                    $data[$repository->columns[$field]['complexColumn']] = $this->getDataToSend($complexEntity->getChangedFields(), $complexEntity, $complexRepository);
                }
            }
        }
        return $data;
    }

    /**
     * @param $array
     * @return AbstractEntity
     * @throws \Exception
     */
    public function hydrate($array)
    {
        /** @var AbstractEntity $obj */
        $obj = new $this->className();
        foreach ($this->columns as $attribute => $column) {
            if (array_key_exists($column['column'], $array)) {
                $obj->set($attribute, $array[$column['column']], false);
            } elseif (array_key_exists($column['readColumn'], $array)) {
                $obj->set($attribute, $array[$column['readColumn']], false);
            } elseif (array_key_exists($column['complexColumn'], $array) && count($array[$column['complexColumn']]) > 0) {
                $complexEntity = $this->hydrateComplexEntity($array[$column['complexColumn']], $column['complexEntity']);
                $obj->set($attribute, $complexEntity, false);
            }
        }
        return $obj;
    }

    /**
     * @param $array
     * @param $complexEntity
     * @return array
     * @throws \Exception
     */
    public function hydrateComplexEntity($array, $complexEntity)
    {
        $complexObjs = [];
        /** @var AbstractEntity $complexObj */
        $complexClass = $this->manager->getRepository($complexEntity);

        $oneToMany = is_array($array[array_rand($array)]);

        if ($oneToMany) {
            //create an empty entity array
            for ($i = 0; $i < count($array); $i++) {
                $complexObjs[] = new $complexClass->className();
            }
        } else {
            $complexObj = new $complexClass->className();
        }


        foreach ($complexClass->columns as $attribute => $column) {
            if ($oneToMany) {
                foreach ($array as $iterator => $data) {
                    if (array_key_exists($column['column'], $data)) {
                        $complexObjs[$iterator]->set($attribute, $data[$column['column']], false);
                    } elseif (array_key_exists($column['readColumn'], $data)) {
                        $complexObjs[$iterator]->set($attribute, $data[$column['readColumn']], false);
                    }
                }
            } else {
                if (array_key_exists($column['column'], $array)) {
                    $complexObj->set($attribute, $array[$column['column']], false);
                } elseif (array_key_exists($column['readColumn'], $array)) {
                    $complexObj->set($attribute, $array[$column['readColumn']], false);
                }
            }
        }

        return $complexObjs;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return Parameters
     */
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

    public function getRepoMetadata()
    {
        return $this->metadata;
    }
}
