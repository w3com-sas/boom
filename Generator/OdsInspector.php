<?php

namespace W3com\BoomBundle\Generator;

use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;

class OdsInspector
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


    public function __construct(BoomManager $manager)
    {
        $this->oDataRestClient = new OdataRestClient($manager);
        $this->initOdsEntities();
    }


    public function getOdsEntity($name)
    {
        /** @var Entity $entity */
        foreach ($this->entities as $entity) {
            if ($entity->getName() === $name) {
                return $entity;
            }
        }
    }

    public function getOdsEntities()
    {
        return $this->entities;
    }


    private function initOdsEntities()
    {

        if (empty($this->entities)) {

            $odsViewMetadata = $this->oDataRestClient->getOdsViewMetadata();
            $schema = array_column($odsViewMetadata, $this::NODE_SCHEMA);
            $entitiesType = array_column($schema, $this::NODE_ENTITY, 'Property');

            // If 1 entity the array contain 1 "couche", so one foreach
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

        return $this->entities;
    }

    private function hydrateEntityModel($entityMetadata)
    {
        // 1 entity per $metadatum
        $entity = new Entity();

        foreach ($entityMetadata as $metadatum => $value) {

            switch ($metadatum) {
                case $this::NAME_ENTITY_PROPERTY:
                    $entity->setName($value);
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
        $this->entities[] = $entity;
    }

    private function hydratePropertyModel($propertyMetadata, Entity $entity)
    {
        foreach ($propertyMetadata as $propertiesMetadata) {

            $property = new Property();
            $property->setType(Property::TYPE_ODS);

            foreach ($propertiesMetadata as $propertyMetadatum => $value) {

                switch ($propertyMetadatum) {

                    case $this::NAME_ENTITY_PROPERTY:
                        $property->setField($value);
                        $property->setName(strtolower($value));
                        break;
                    case $this::TYPE_PROPERTY:
                        $property->setFieldType($value);
                        break;
                }
            }
            $entity->setProperty($property);
        }
    }
}