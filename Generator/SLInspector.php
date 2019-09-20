<?php

namespace W3com\BoomBundle\Generator;

use Doctrine\Common\Annotations\AnnotationRegistry;
use W3com\BoomBundle\Exception\EntityNotFoundException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\HanaEntity\FieldDefinition;
use W3com\BoomBundle\RestClient\SLRestClient;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Utils\StringUtils;

class SLInspector implements InspectorInterface
{
    const NODE_SCHEMA = 'Schema';

    const NODE_ENTITY = 'EntityType';

    const NAME_ENTITY_PROPERTY = '@Name';

    const TYPE_ENTITY_PROPERTY = '@EntityType';

    const NAME_KEY = 'Key';

    const NAME_PROPERTY_KEY = 'PropertyRef';

    const NAME_PROPERTY = 'Property';

    const TYPE_PROPERTY = '@Type';

    private $boom;

    private $SLClient;

    private $UDTEntities = [];

    private $SAPEntities = [];

    private $metadata = [];

    private $entityTypes = [];

    private $fieldsDefinition = [];

    public function __construct(BoomManager $manager)
    {
        $this->boom = $manager;
        $this->SLClient = new SLRestClient($manager);
    }

    /**
     * @param $name
     * @return Entity
     * @throws EntityNotFoundException
     */
    public function getEntity($name)
    {
        /** @var Entity $entity */
        foreach ($this->getEntities() as $entity) {
            if ($entity->getTable() === $name) {
                return $entity;
            }
        }
        throw new EntityNotFoundException('Unable to find "' . $name .
            '" entity.');
    }


    public function getEntities()
    {
        return array_merge($this->UDTEntities, $this->SAPEntities);
    }

    public function getUDTEntities()
    {
        return $this->UDTEntities;
    }

    public function getSAPEntities()
    {
        return $this->SAPEntities;
    }

    public function initEntities()
    {
        AnnotationRegistry::registerLoader('class_exists');

        $metadata = $this->SLClient->getMetadata();

        $this->metadata = $metadata;

        $entitiesMetadata = $metadata['edmx:DataServices']['Schema']['EntityContainer']['EntitySet'];

        $entityTypes = [];

        foreach ($this->metadata['edmx:DataServices']['Schema']['EntityType'] as $entityType) {
            $entityTypes[$entityType[$this::NAME_ENTITY_PROPERTY]] = $entityType;
        }

        $this->entityTypes = $entityTypes;

        $fieldRepo = $this->boom->getRepository('FieldDefinition');

        $this->fieldsDefinition = $fieldRepo->findAll();

        foreach ($entitiesMetadata as $entityMetadata) {
            $this->hydrateEntityModel($entityMetadata);
        }
    }

    private function hydrateEntityModel($entityMetadata)
    {
        $entity = new Entity();

        $entity->setName(str_replace('U_W3C_', '', Entity::formatTableName($entityMetadata[$this::NAME_ENTITY_PROPERTY])));
        $entity->setTable(str_replace('Type', '', $entityMetadata[$this::NAME_ENTITY_PROPERTY]));

        $type = explode('.', $entityMetadata[$this::TYPE_ENTITY_PROPERTY]);

        if ($type[0] !== 'SAPB1') {
            return;
        }

        $key = $this->entityTypes[$type[1]][$this::NAME_KEY][$this::NAME_PROPERTY_KEY];

        if (isset($key[$this::NAME_ENTITY_PROPERTY])) {
            $entity->setKey($key[$this::NAME_ENTITY_PROPERTY]);
        } else {
            $entity->setKey($key[0][$this::NAME_ENTITY_PROPERTY]);
        }

        if (array_key_exists($this::NAME_ENTITY_PROPERTY, $this->entityTypes[$type[1]][$this::NAME_PROPERTY])) {
            $this->hydratePropertyModel($this->entityTypes[$type[1]][$this::NAME_PROPERTY], $entity);
        } else {
            foreach ($this->entityTypes[$type[1]][$this::NAME_PROPERTY] as $propertyMetadata) {
                $this->hydratePropertyModel($propertyMetadata, $entity);
            }
        }

        if (strpos($entityMetadata[$this::NAME_ENTITY_PROPERTY], 'W3C_') !== false) {
            $this->UDTEntities[] = $entity;
        } else {
            $this->SAPEntities[] = $entity;
        }
    }

    public function hydratePropertyModel($propertyMetadata, Entity $entity)
    {
        $property = new Property();
        $property->setType(Property::TYPE_SL);

        $property->setDescription($propertyMetadata[$this::NAME_ENTITY_PROPERTY]);

        if (strpos(strtolower($propertyMetadata[$this::NAME_ENTITY_PROPERTY]), 'u_w3c') !== false) {
            /** @var FieldDefinition $fieldDefinition */
            foreach ($this->fieldsDefinition as $fieldDefinition) {
                if (strtolower($fieldDefinition->getColumn_name()) === strtolower($propertyMetadata[$this::NAME_ENTITY_PROPERTY])) {
                    $property->setDescription($fieldDefinition->getDescription());
                    $property->setName(StringUtils::descriptionToProperty($property->getDescription()));
                    break;
                }
            }
        }

        foreach ($propertyMetadata as $propertyMetadatum => $value) {

            switch ($propertyMetadatum) {

                case $this::NAME_ENTITY_PROPERTY:
                    $property->setField($value);
                    if (!$property->getName()) {
                        $property->setName(str_replace('_', '', lcfirst(str_ireplace('u_w3c_', '', $value))));
                    }
                    break;
                case $this::TYPE_PROPERTY:
                    $hasQuotes = 'true';
                    switch ($value) {
                        case Property::FIELD_TYPE_DATE_TIME:
                            $value = 'date';
                            break;
                        case Property::FIELD_TYPE_DOUBLE:
                            $value = 'float';
                            break;
                        case Property::FIELD_TYPE_INTEGER:
                            $value = 'int';
                            $hasQuotes = 'false';
                            break;
                        case Property::FIELD_TYPE_STRING:
                            $value = 'string';
                            break;
                        case Property::FIELD_TYPE_TIME:
                            $value = 'date';
                            break;
                        case Property::FIELD_TYPE_YES_NO_ENUM:
                            $value = 'choice';
                            $property->setChoices([
                                'Oui' => 'tYes',
                                'Non' => 'tNo'
                            ]);
                            break;
                        default:
                            $value = 'choice';
                            break;
                    }

                    $property->setFieldType($value);
                    $property->setHasQuotes($hasQuotes);
                    break;
            }
        }
        $entity->setProperty($property);
    }
}