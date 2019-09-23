<?php

namespace W3com\BoomBundle\Generator;

use DateInterval;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use W3com\BoomBundle\Exception\EntityNotFoundException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\HanaEntity\FieldDefinition;
use W3com\BoomBundle\RestClient\OdataRestClient;
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

    const STORAGE_KEY = 'sl.field.definition';

    private $boom;

    private $SLClient;

    private $UDTEntities = [];

    private $SAPEntities = [];

    private $metadata = [];

    private $entityTypes = [];

    private $fieldsDefinition = [];

    private $enumTypes = [];

    private $cache;

    public function __construct(BoomManager $manager, AdapterInterface $cache)
    {
        $this->boom = $manager;
        $this->SLClient = new SLRestClient($manager, $cache);
        $this->cache = $cache;
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

        $this->metadata = $this->SLClient->getMetadata();
        $entitiesMetadata = $this->metadata['edmx:DataServices']['Schema']['EntityContainer']['EntitySet'];
        $this->enumTypes = $this->metadata['edmx:DataServices']['Schema']['EnumType'];

        $entityTypes = [];

        foreach ($this->metadata['edmx:DataServices']['Schema']['EntityType'] as $entityType) {
            $entityTypes[$entityType[$this::NAME_ENTITY_PROPERTY]] = $entityType;
        }

        $this->entityTypes = $entityTypes;

        $fieldDefCache = $this->cache->getItem($this::STORAGE_KEY);

        if (!$fieldDefCache->isHit()){
            $fieldRepo = $this->boom->getRepository('FieldDefinition');
            $this->fieldsDefinition = $fieldRepo->findAll();
            $fieldDefCache->set($this->fieldsDefinition);
            $fieldDefCache->expiresAfter(DateInterval::createFromDateString('1 day'));
            $this->cache->save($fieldDefCache);
        } else {
            $this->fieldsDefinition = $fieldDefCache->get();
        }

        foreach ($entitiesMetadata as $entityMetadata) {
            $this->hydrateEntityModel($entityMetadata);
        }
    }

    private function hydrateEntityModel($entityMetadata)
    {
        $entity = new Entity();

        $entity->setName(str_replace('U_', '', Entity::formatTableName($entityMetadata[$this::NAME_ENTITY_PROPERTY])));
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

        if (strpos($entityMetadata[$this::NAME_ENTITY_PROPERTY], 'U_') !== false) {
            $this->UDTEntities[] = $entity;
        } else {
            $this->SAPEntities[] = $entity;
        }
    }

    private function hydratePropertyModel($propertyMetadata, Entity $entity)
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

        $property->setField($propertyMetadata[$this::NAME_ENTITY_PROPERTY]);
        if (!$property->getName()) {
            $property->setName(str_replace('_', '', lcfirst(str_ireplace('u_w3c_', '', $propertyMetadata[$this::NAME_ENTITY_PROPERTY]))));
        }

        $hasQuotes = 'true';

        switch ($propertyMetadata[$this::TYPE_PROPERTY]) {
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
            default:
                $enumName = substr($propertyMetadata[$this::TYPE_PROPERTY], 6);
                $value = 'choice';
                $choices = [];

                foreach ($this->enumTypes as $enumType) {
                    if ($enumType['@Name'] === $enumName) {
                        $enumClassName = '\W3com\BoomBundle\HanaEnum\\' . $enumName;
                        foreach ($enumType['Member'] as $enumChoice) {
                            if (isset($enumChoice['@Name'])) {

                                $const = strtoupper($enumChoice['@Name']);

                                try {
                                    $choice = constant("$enumClassName::$const");
                                } catch (\ErrorException $e) {
                                    $choice = '';
                                }

                                $choices[$enumChoice['@Name']] = $choice === '' ? $enumChoice['@Name'] : $choice;
                            }
                        }
                        $property->setChoices($choices);
                        break;
                    }
                }

                break;
        }

        $property->setFieldType($value);
        $property->setHasQuotes($hasQuotes);

        $entity->setProperty($property);
    }
}