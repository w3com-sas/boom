<?php

namespace W3com\BoomBundle\Generator;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use W3com\BoomBundle\Exception\EntityNotFoundException;
use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\Utils\StringUtils;

class OdsInspector implements InspectorInterface
{
    const NODE_SCHEMA = 'Schema';

    const NODE_ENTITY = 'EntityType';

    const NAME_ENTITY_PROPERTY = '@Name';

    const NAME_KEY = 'Key';

    const NAME_PROPERTY_KEY = 'PropertyRef';

    const NAME_PROPERTY = 'Property';

    const TYPE_PROPERTY = '@Type';

    private $oDataRestClient;

    private $entities = [];


    public function __construct(BoomManager $manager, AdapterInterface $cache)
    {
        $this->oDataRestClient = new OdataRestClient($manager, $cache);
        $this->initEntities();
    }

    /**
     * @param $name
     * @return Entity
     * @throws EntityNotFoundException
     */
    public function getEntity($name)
    {
        /** @var Entity $entity */
        foreach ($this->entities as $entity) {
            if ($entity->getTable() === $name) {
                return $entity;
            }
        }
        throw new EntityNotFoundException('Unable to find "' . $name .
            '" calculation view in services.xsodata ');
    }

    public function getEntities()
    {
        return $this->entities;
    }


    public function initEntities()
    {
        $odsViewMetadata = $this->oDataRestClient->getOdsViewMetadata();
        $schema = array_column($odsViewMetadata, $this::NODE_SCHEMA);
        $entitiesType = array_column($schema, $this::NODE_ENTITY, 'Property');

        if (array_key_exists('Key', $entitiesType[0])) {
            foreach ($entitiesType as $entitiesMetadata) {
                $this->hydrateEntityModel($entitiesMetadata);
            }
        } else {
            foreach ($entitiesType as $entitiesMetadata) {
                foreach ($entitiesMetadata as $entityMetadata) {
                    $this->hydrateEntityModel($entityMetadata);
                }

            }
        }
    }

    private function hydrateEntityModel($entityMetadata)
    {
        $entity = new Entity();

        foreach ($entityMetadata as $metadatum => $value) {

            switch ($metadatum) {
                case $this::NAME_ENTITY_PROPERTY:
                    $entity->setName(str_replace('Type', '', $value));
                    $entity->setTable(str_replace('Type', '', $value));
                    break;
                case $this::NAME_PROPERTY:
                    $this->hydratePropertyModel($value, $entity);
                    break;
                case $this::NAME_KEY:
                    $entity->setKey($value[$this::NAME_PROPERTY_KEY]);
                    break;
            }
        }
        $this->entities[$entity->getName()] = $entity;
    }

    private function hydratePropertyModel($propertyMetadata, Entity $entity)
    {
        foreach ($propertyMetadata as $propertiesMetadata) {

            $property = new Property();

            foreach ($propertiesMetadata as $propertyMetadatum => $value) {

                switch ($propertyMetadatum) {

                    case $this::NAME_ENTITY_PROPERTY:
                        $property->setField($value);
                        $property->setName(lcfirst($value));
                        break;
                    case $this::TYPE_PROPERTY:
                        $property->setFieldTypeSAPFormat($value);
                        break;
                }
            }
            $entity->setProperty($property);
        }
    }
}