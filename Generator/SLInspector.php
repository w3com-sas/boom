<?php

namespace W3com\BoomBundle\Generator;

use DateInterval;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Nette\PhpGenerator\ClassType;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use W3com\BoomBundle\Exception\EntityNotFoundException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\HanaConst\TableNames;
use W3com\BoomBundle\HanaEntity\FieldDefinition;
use W3com\BoomBundle\HanaEntity\UserFieldsMD;
use W3com\BoomBundle\HanaEntity\UserTablesMD;
use W3com\BoomBundle\HanaRepository\FieldDefinitionRepository;
use W3com\BoomBundle\HanaRepository\UserFieldsMDRepository;
use W3com\BoomBundle\HanaRepository\UserTablesMDRepository;
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

    private $boom;

    private $SLClient;

    private $UDTEntities = [];

    private $SAPEntities = [];

    private $metadata = [];

    private $entityTypes = [];

    private $enumTypes = [];

    private $complexTypes = [];

    public function __construct(BoomManager $manager, AdapterInterface $cache)
    {
        $this->boom = $manager;
        $this->SLClient = new SLRestClient($manager, $cache);
    }
/*
    public function initEnum()
    {
        $metadata = $this->SLClient->getMetadata();
        $enums = $metadata['edmx:DataServices']['Schema']['EnumType'];

        foreach ($enums as $enum) {
            $class = new ClassType($enum['@Name']);
            foreach ($enum['Member'] as $property) {
                if (isset($property['@Name'])) {
                    $class->addConstant(explode(' ', strtoupper($property['@Name']))[0], '');
                }
            }
            dump($class);
            file_put_contents(__DIR__ . '/../HanaEnum/'
                . $enum['@Name'] . '.php', '<?php' . "\n\n" .
                'namespace W3com\\BoomBundle\\HanaEnum;' . "\n\n" .
                $class);
        }
    }*/

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

    /**
     * @param Entity $entity
     * @return Entity
     * @throws \Exception
     */
    public function addMetaToEntity(Entity $entity)
    {
        if ($entity->isToSynchronize()) {
            /** @var UserTablesMDRepository $udtRepo */
            $udtRepo = $this->boom->getRepository('UserTablesMD');
            /** @var UserTablesMD $udt */
            $udt =  $udtRepo->find(substr($entity->getTable(), 2));

            $entity->setDescription($udt->getTableDescription());
            $entity->setType($udt->getTableType());
            $entity->setArchivable($udt->getArchivable());
            $entity->setArchiveDate($udt->getArchiveDateField());
        }

        /** @var UserFieldsMDRepository $udfRepo */
        $udfRepo = $this->boom->getRepository('UserFieldsMD');

        if (strpos($entity->getTable(), 'U_') !== false) {
            $sapTableName = '@' . substr($entity->getTable(), 2);
        } else {
            $sapTableName = '@' . $entity->getTable();
        }

        $udfs = $udfRepo->findByTableName($sapTableName);

        if ($udfs === []) {
            $tableNameConst = strtoupper($entity->getTable());
            try {
                $sapTableName = constant("W3com\BoomBundle\HanaConst\TableNames::$tableNameConst");
            } catch (\Exception $e) {
                throw new \Exception("Veuillez créer la constante $tableNameConst dans W3com\BoomBundle\HanaConst\TableNames");
            }
            $udfs = $udfRepo->findByTableName($sapTableName);
        }

        $entity->setSapTable($sapTableName);

        /** @var UserFieldsMD $udf */
        foreach ($udfs as $udf) {
            $found = false;
            $propertyToChange = new Property();

            /** @var Property $property */
            foreach ($entity->getProperties() as $property) {
                if ($udf->getName() === substr($property->getField(), 2)) {
                    $propertyToChange = $property;
                    $found = true;
                    break;
                }
            }

            $this->hydratePropertyWithUDF($propertyToChange, $udf);

            if (!$found) {
                $entity->setProperty($propertyToChange, true);
            }
        }

        return $entity;
    }

    private function hydratePropertyWithUDF(Property $property, UserFieldsMD $udf)
    {
        $property->setIsUDF(true);
        $property->setField('U_' . $udf->getName());

        if ($udf->getDescription() !== null && $udf->getDescription() !== "") {
            $property->setName(StringUtils::stringToCamelCase($udf->getDescription()));
            $property->setDescription($udf->getDescription());
        } else {
            $property->setName(StringUtils::stringToCamelCase($udf->getName()));
            $property->setDescription($property->getField());
        }

        $property->setDefaultValue($udf->getDefaultValue());

        if ($udf->getMandatory() === 'tYES') {
            $property->setIsMandatory(true);
        } else {
            $property->setIsMandatory(false);
        }

        $property->setFieldTypeMD($udf->getType());
        $property->setFieldSubTypeMD($udf->getSubType());
        $property->setSapTable($udf->getTableName());
        $property->setSize($udf->getEditSize());
        $property->setLinkedTable($udf->getLinkedTable());
        $property->setLinkedSystemObject($udf->getLinkedSystemObject());
        $property->setLinkedUDO($udf->getLinkedUDO());

        if ($udf->getValidValuesMD() !== []) {
            $property->setChoices(StringUtils::choicesValidValuesMDToArray($udf->getValidValuesMD()));
        } else {
            $property->setChoices([]);
        }
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
        $this->complexTypes = $this->metadata['edmx:DataServices']['Schema']['ComplexType'];

        $entityTypes = [];

        foreach ($this->metadata['edmx:DataServices']['Schema']['EntityType'] as $entityType) {
            $entityTypes[$entityType[$this::NAME_ENTITY_PROPERTY]] = $entityType;
        }

        $this->entityTypes = $entityTypes;

        /** @var UserTablesMDRepository $udtRepo */
        $udtRepo = $this->boom->getRepository('UserTablesMD');

        $udtsTyped = $udtRepo->findAllTypedTables();

        /** @var UserTablesMD $udtTyped */
        foreach ($udtsTyped as $udtTyped) {
            $entitiesMetadata[] = [
                '@EntityType' => 'SAPB1.' . $udtTyped->getTableName(),
                '@Name' => 'U_' . $udtTyped->getTableName(),
                '#' => ""
            ];
        }

        foreach ($entitiesMetadata as $entityMetadata) {
            $this->hydrateEntityModel($entityMetadata);
        }
    }

    private function hydrateEntityModel($entityMetadata, $isComplex = false)
    {
        $entity = new Entity();

        if (strpos($entityMetadata[$this::NAME_ENTITY_PROPERTY], 'U_') !== false
        || strpos($entityMetadata[$this::NAME_ENTITY_PROPERTY], 'W3C_') !== false) {
            $entityName = StringUtils::stringToPascalCase(str_replace('U_', '', Entity::formatTableName($entityMetadata[$this::NAME_ENTITY_PROPERTY])));
            $entity->setToSynchronize(true);
        } else {
            $entityName = Entity::formatTableName($entityMetadata[$this::NAME_ENTITY_PROPERTY]);
        }

        $entity->setName($entityName);

        if (!$isComplex) {
            $entity->setTable(str_replace('Type', '', $entityMetadata[$this::NAME_ENTITY_PROPERTY]));

            $type = explode('.', $entityMetadata[$this::TYPE_ENTITY_PROPERTY]);

            if ($type[0] !== 'SAPB1') {
                return;
            }

            if (isset($this->entityTypes[$type[1]])) {
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
            } else {
                $entity->setProperties([]);
            }

            if (strpos($entityMetadata[$this::NAME_ENTITY_PROPERTY], 'U_') !== false) {
                $this->UDTEntities[] = $entity;
            } else {
                $this->SAPEntities[] = $entity;
            }
        } else {
            $entity->setTable('U_' . $entityMetadata[$this::NAME_ENTITY_PROPERTY]);
            if (array_key_exists($this::NAME_ENTITY_PROPERTY, $entityMetadata[$this::NAME_PROPERTY])) {
                $this->hydratePropertyModel($entityMetadata[$this::NAME_PROPERTY], $entity);
            } else {
                foreach ($entityMetadata[$this::NAME_PROPERTY] as $propertyMetadata) {
                    $this->hydratePropertyModel($propertyMetadata, $entity);
                }
            }
        }

        return $entity;
    }

    private function hydratePropertyModel($propertyMetadata, Entity $entity)
    {
        $property = new Property();

        $property->setDescription($propertyMetadata[$this::NAME_ENTITY_PROPERTY]);

        if (strpos(strtolower($propertyMetadata[$this::NAME_ENTITY_PROPERTY]), 'u_') !== false) {
            $property->setIsUDF(true);
        }

        if (isset($propertyMetadata['@Nullable']) && $propertyMetadata['@Nullable'] == 'false') {
            $property->setIsMandatory(true);
        }

        $property->setName(str_replace('_', '', lcfirst($propertyMetadata[$this::NAME_ENTITY_PROPERTY])));

        $property->setField($propertyMetadata[$this::NAME_ENTITY_PROPERTY]);

        $hasQuotes = true;

        $value = 'string';
        $var = 'string';

        switch ($propertyMetadata[$this::TYPE_PROPERTY]) {
            case Property::FIELD_TYPE_DOUBLE:
            case Property::FIELD_TYPE_DECIMAL:
                $value = 'float';
                $var = 'float';
                break;
            case Property::FIELD_TYPE_DATE_TIME:
                $value = 'date';
                $var = 'string';
                break;
            case Property::FIELD_TYPE_INTEGER:
                $value = 'int';
                $var = 'int';
                $hasQuotes = false;
                break;
            case Property::FIELD_TYPE_STRING:
                $value = 'string';
                $var = 'string';
                break;
            case Property::FIELD_TYPE_TIME:
                $value = 'time';
                $var = 'string';
                break;
            default:

                if (substr($propertyMetadata[$this::TYPE_PROPERTY],0,10) === 'Collection') {
                    $value = 'array';
                    $var = 'array';
                    $complexEntityName = substr(substr($propertyMetadata[$this::TYPE_PROPERTY],17), 0, -1);
                    foreach ($this->complexTypes as $complexType) {
                        if ($complexType['@Name'] === $complexEntityName) {
                            $var = 'array';
                            $value = 'Collection/'.$complexEntityName;
                            $property->setComplexEntity($this->hydrateEntityModel($complexType, true));
                            break;
                        }
                    }
                    break;
                } else {
                    $fieldName = substr($propertyMetadata[$this::TYPE_PROPERTY], 6);
                }

                $isEnum = false;
                $choices = [];
                foreach ($this->enumTypes as $enumType) {
                    if ($enumType['@Name'] === $fieldName) {
                        $var = 'string';
                        $value = 'string';
                        $isEnum = true;
                        $enumClassName = '\W3com\BoomBundle\HanaEnum\\' . $fieldName;
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
                if ($isEnum) {
                    break;
                }
                foreach ($this->complexTypes as $complexType) {
                    if ($complexType['@Name'] === $fieldName) {
                        $var = $fieldName;
                        $value = $fieldName;
                        $property->setComplexEntity($this->hydrateEntityModel($complexType, true));
                        break;
                    }
                }
        }

        $property->setVar($var);
        $property->setFieldType($value);
        $property->setHasQuotes($hasQuotes);

//        $property->setFieldTypeSAPFormat($propertyMetadata[$this::TYPE_PROPERTY], $this->enumTypes);

        $entity->setProperty($property);
    }
}