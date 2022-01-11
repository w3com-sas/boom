<?php

namespace W3com\BoomBundle\Repository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use W3com\BoomBundle\BoomEvents;
use W3com\BoomBundle\Event\PreAddEvent;
use W3com\BoomBundle\Event\PreDeleteEvent;
use W3com\BoomBundle\Event\PreUpdateEvent;
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
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * AbstractRepository constructor.
     * @param RepoMetadata $metadata
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(RepoMetadata $metadata, EventDispatcherInterface $dispatcher)
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
        $this->dispatcher = $dispatcher;
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
        $caseInsensitive = false;
        if (isset($params)){
            $caseInsensitive = $params->getCaseInsensitive();
        }
        if (BoomConstants::SL == $this->read) {
            $uri = $this->aliasRead;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri, false, $caseInsensitive);
        } elseif (BoomConstants::ODSL == $this->read) {
            $uri = $this->manager->config['service_layer']['semantic_layer_suffix'] . $this->aliasRead;
            $uri .= (null == $params) ? '' : $params->getParameters();
            $res = $this->manager->restClients['sl']->get($uri, false, $caseInsensitive);
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
    public function update(AbstractEntity $entity, $updateCollection = false)
    {
        if ($this->dispatcher->hasListeners(BoomEvents::PRE_UPDATE_EVENT)){
            $event = new PreUpdateEvent($entity, BoomEvents::TYPE_ONE);
            $this->dispatcher->dispatch($event, BoomEvents::PRE_UPDATE_EVENT);
        }

        $id = $entity->get($this->key);

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
                $this->manager->restClients['sl']->patch($uri, $data, $updateCollection);
                $entity->hydrate('changedFields', []);
            }
            return $entity;
        } elseif (BoomConstants::ODS == $this->write) {
            // update
            $quotes = $this->columns[$this->key]['quotes'] ? "'" : '';
            $uri = $this->aliasWrite;
            $uri = $uri . "($quotes" . $id . "$quotes)";

            $fields = $entity->getChangedFields();
            $data = $this->getDataToSend($fields, $entity);

            if (count($data) > 0) {
                $this->manager->restClients['odata']->patch($uri, $data);
                $entity->hydrate('changedFields', []);
            }
            return $entity;
        } else {
            throw new \Exception('Unknown entity WRITE method');
        }
    }

    /**
     * Cancel an object
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
     * Close an object
     *
     * @param AbstractEntity $entity
     * @param mixed|null $id DEPRECATED
     * @return bool
     * @throws \Exception
     */
    public function close(AbstractEntity $entity, $id = null)
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

            $this->manager->restClients['sl']->close($uri);

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
        if ($this->dispatcher->hasListeners(BoomEvents::PRE_ADD_EVENT)){
            $event = new PreAddEvent($entity, BoomEvents::TYPE_ONE);
            $this->dispatcher->dispatch($event, BoomEvents::PRE_ADD_EVENT);
        }

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
            } elseif ($repository->columns[$field]['complexEntity'] !== null && $value) {
                $complexData = [];
                $complexRepository = $this->manager->getRepository($repository->columns[$field]['complexEntity']);
                // Si l'objet à plusieurs complexType
                $complexEntities = $entity->get($field);

                if (is_array($complexEntities) && count($complexEntities) > 0 && $complexEntities[array_rand($complexEntities)] instanceof AbstractEntity) {
                    /** @var AbstractEntity $complexEntity */
                    foreach ($complexEntities as $complexEntity) {
                        $changedData = $this->getDataToSend($complexEntity->getChangedFields(), $complexEntity, $complexRepository);
                        if (!empty($changedData)) {
                            $complexData[] = $changedData;
                        }
                    }
                    $data[$repository->columns[$field]['column']] = $complexData;

                } elseif (($complexEntity = $entity->get($field)) instanceof AbstractEntity) {
                    $changedData = $this->getDataToSend($complexEntity->getChangedFields(), $complexEntity, $complexRepository);
                    if (!empty($changedData)){
                        $data[$repository->columns[$field]['column']] = $changedData;
                    }
                } elseif (is_array($complexEntities)/* && count($complexEntities) > 0*/) {
                    $data[$repository->columns[$field]['column']] = $complexEntities;
                }
            }
        }
        return $data;
    }

    /**
     * @param $array
     * @param null $columns
     * @param null $objClassName
     * @return AbstractEntity
     * @throws \Exception
     */
    public function hydrate($array, $columns = null, $objClassName = null)
    {
        $columns = $columns === null ? $this->columns : $columns;
        /** @var AbstractEntity $obj */
        $obj = $objClassName === null ? new $this->className() : new $objClassName();

        foreach ($columns as $attribute => $column) {
            if (array_key_exists($column['column'], $array) && $column['complexEntity'] === null) {
                $obj->set($attribute, $array[$column['column']], false);
            } elseif (array_key_exists($column['readColumn'], $array) && $column['complexEntity'] === null) {
                $obj->set($attribute, $array[$column['readColumn']], false);
            } elseif ($column['complexEntity'] !== null && array_key_exists($column['column'], $array)) {
                $value = $this->hydrateComplexEntity($array[$column['column']], $column['complexEntity']);
                $obj->set($attribute, $value, false);
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
        /** @var AbstractEntity $complexObj */
        $complexRepo = $this->manager->getRepository($complexEntity);
        $dataObj = [];
        foreach ($array as $column => $value) {
            // ONE TO MANY OR ONE TO ONE
            if (is_array($value)) {
                $dataObj[] = $this->hydrate($value, $complexRepo->columns, $complexRepo->className);
            } else {
                $dataObj = $this->hydrate($array, $complexRepo->columns, $complexRepo->className);
            }
        }
        return $dataObj;
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
                    if (intval($res[0]->getCode()) < 1000000000000) {
                        return intval(str_pad($res[0]->getCode(), 13, 0)) + 1;
                    }
                    return intval($res[0]->getCode()) + 1;
                    break;            }
        } else {
            throw new \Exception('Unsupported entity, is there a Code column ?');
        }
    }

    public function getRepoMetadata()
    {
        return $this->metadata;
    }
}
