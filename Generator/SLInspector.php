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
use W3com\BoomBundle\HanaRepository\FieldDefinitionRepository;
use W3com\BoomBundle\HanaRepository\UserFieldsMDRepository;
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

    private $fieldsDefinition = [];

    private $enumTypes = [];

    private $userFieldsMD = [];

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
        /** @var FieldDefinitionRepository $fieldRepo */
        $fieldRepo = $this->boom->getRepository('FieldDefinition');

        $fields = $fieldRepo->findByTableName($entity->getTable());

        /** @var UserFieldsMDRepository $fieldRepo */
        $udfRepo = $this->boom->getRepository('UserFieldsMD');

        if (strpos($entity->getTable(), 'U_') !== false) {
            $sapTableName = '@' . substr($entity->getTable(), 2);
        } else {
            $sapTableName = '@' . $entity->getTable();
        }

        if (count($fields) === 0) {
            $fields = $fieldRepo->findByTableName(substr($entity->getTable(), 2));
            if (count($fields) === 0) {
                $fields = $fieldRepo->findByTableName('@' . substr($entity->getTable(), 2));
                if (count($fields) === 0) {
                    $fields = $fieldRepo->findByTableName('@' . $entity->getTable());
                }
            }
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

        if (count($fields) === 0) {
            $fields = $fieldRepo->findByTableName(substr($entity->getTable(), 2));
            if (count($fields) === 0) {
                $fields = $fieldRepo->findByTableName('@' . substr($entity->getTable(), 2));
            }
        }

        /** @var Property $property */
        foreach ($entity->getProperties() as $property) {
            if (!$property->isUDF()) {
                continue;
            }
            /** @var FieldDefinition $field */
            foreach ($fields as $field) {
                if (strtolower($property->getField()) === strtolower($field->getColumn_name())) {
                    $property->setDescription($field->getDescription());
                    $property->setName(StringUtils::stringToCamelCase($field->getDescription()));
                    if ($field->getChoices() !== null) {
                        $property->setChoices($field->getChoices());
                    }
                    $property->setIsMandatory($field->isMandatory() !== 'N');
                    $property->setDefaultValue($field->getDefaultValue());
                }
            }

            /** @var UserFieldsMD $udf */
            foreach ($udfs as $udf) {
                if ($udf->getName() === substr($property->getField(), 2)) {
                    $property->setFieldTypeMD($udf->getType());
                    $property->setFieldSubTypeMD($udf->getSubType());
                    $property->setSize($udf->getEditSize());
                    $property->setLinkedTable($udf->getLinkedTable());
                    $property->setLinkedSystemObject($udf->getLinkedSystemObject());
                    $property->setLinkedUDO($udf->getLinkedUDO());
                }
            }

        }

        return $entity;
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

        foreach ($entitiesMetadata as $entityMetadata) {
            $this->hydrateEntityModel($entityMetadata);
        }
    }

    private function hydrateEntityModel($entityMetadata)
    {
        $entity = new Entity();

        if (strpos($entityMetadata[$this::NAME_ENTITY_PROPERTY], 'U_') !== false) {
            $entityName = StringUtils::stringToPascalCase(str_replace('U_', '', Entity::formatTableName($entityMetadata[$this::NAME_ENTITY_PROPERTY])));
            $entity->setToSynchronize(true);
        } else {
            $entityName = Entity::formatTableName($entityMetadata[$this::NAME_ENTITY_PROPERTY]);
        }

        $entity->setName($entityName);

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

        $property->setDescription($propertyMetadata[$this::NAME_ENTITY_PROPERTY]);

        if (strpos(strtolower($propertyMetadata[$this::NAME_ENTITY_PROPERTY]), 'u_') !== false) {
            /** @var FieldDefinition $fieldDefinition */
            $property->setIsUDF(true);
        }

        if (isset($propertyMetadata['@Nullable']) && $propertyMetadata['@Nullable'] == 'false') {
            $property->setIsMandatory(true);
        }

        $property->setName(str_replace('_', '', lcfirst($propertyMetadata[$this::NAME_ENTITY_PROPERTY])));

        $property->setField($propertyMetadata[$this::NAME_ENTITY_PROPERTY]);

        $property->setFieldTypeSAPFormat($propertyMetadata[$this::TYPE_PROPERTY], $this->enumTypes);

        $entity->setProperty($property);
    }
}