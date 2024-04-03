<?php

namespace W3com\BoomBundle\Generator;

use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Finder\Finder;
use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;
use W3com\BoomBundle\Annotation\EntitySynchronizedData;
use W3com\BoomBundle\Annotation\SynchronizedData;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\Utils\StringUtils;

class AppInspector implements InspectorInterface
{
    const ENTITIES_CACHE_DIRECTORY = '../var/cache/boom/';
    const ENTITIES_CACHE_KEY = 'entities';
    const ANNOTATION_COLUMN = 'column';
    const ANNOTATION_KEY = 'isKey';

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var BoomManager
     */
    private $manager;

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var PhpArrayAdapter
     */
    private $cache;

    public function __construct(BoomManager $manager)
    {
        $this->finder = new Finder();
        $this->reader = new AnnotationReader();
        $this->manager = $manager;
        $this->cache = new PhpArrayAdapter(
            self::ENTITIES_CACHE_DIRECTORY.AppInspector::ENTITIES_CACHE_KEY.'.cache',
            new FilesystemAdapter()
        );
    }

    /**
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function getEntity($name): ?Entity
    {
        $this->checkToInit();
        $cacheItem = $this->cache->getItem(self::ENTITIES_CACHE_KEY);

        /** @var Entity $entity */
        foreach ($cacheItem->get() as $entity) {
            if ($entity->getName() === $name || $entity->getTable() === $name) {
                return $entity;
            }
        }

        return null;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @throws ReflectionException
     */
    public function initEntities()
    {
        $this->finder->files()->in($this->manager->config['entity_directory']);

        foreach ($this->finder as $fileInfo) {
            $className = str_replace('.php', '', $fileInfo->getFilename());
            $class = new ReflectionClass(
                $this->manager->config['app_namespace'] .
                '\HanaEntity\\' . $className
            );
            $this->hydrateEntityModel($class, $className);
        }

        $this->cache->warmUp([self::ENTITIES_CACHE_KEY => $this->entities]);
    }

    /**
     * @param ReflectionClass $class
     * @param $className
     * @throws ReflectionException
     */
    private function hydrateEntityModel(ReflectionClass $class, $className)
    {
        // One entity per $metadatum
        $entity = new Entity();
        $entity->setName($className);

        foreach ($this->reader->getClassAnnotations($class) as $annotations) {

            if (is_a($annotations, EntityMeta::class)) {
                $entity->setToSynchronize($annotations->synchro);
            }

            if (is_a($annotations, EntitySynchronizedData::class)) {
                $entity->setDescription($annotations->TableDescription);
                $entity->setType($annotations->TableType);
                $entity->setArchivable($annotations->Archivable);
                $entity->setArchiveDate($annotations->ArchiveDateField);
            }

            foreach ($annotations as $annotation => $value) {
                // Alias Read
                if ($annotation === 'aliasRead' && $value !== null) {
                    $entity->setTable($value);
                    $this->hydratePropertyModel($class, $entity);
                }
            }
        }
        if ($entity->getTable() !== null) {
            $this->entities[] = $entity;
        }
    }

    /**
     * @param ReflectionClass $class
     * @param Entity $entity
     * @throws ReflectionException
     */
    private function hydratePropertyModel(ReflectionClass $class, Entity $entity)
    {
        foreach ($class->getDefaultProperties() as $propertyName => $value) {

            $property = new ReflectionProperty($class->getName(), $propertyName);

            $modelProperty = new Property();
            $modelProperty->setTable($entity->getTable());
            foreach ($this->reader->getPropertyAnnotations($property) as $annotations) {

                if (is_a($annotations, EntityColumnMeta::class)) {
                    $modelProperty->setIsUDF($annotations->synchro);
                    $modelProperty->setIsMandatory($annotations->isMandatory);
                    $modelProperty->setDefaultValue($annotations->defaultValue);
                    $modelProperty->setDescription($annotations->description);
                    $modelProperty->setFieldType($annotations->type);
                    $modelProperty->setHasQuotes($annotations->quotes);

                    if ($annotations->choices !== null && $annotations->choices !== '') {
                        $modelProperty->setChoices(StringUtils::choicesStringToArray($annotations->choices));
                    }
                }

                foreach ($annotations as $annotation => $value) {

                    if ($annotation == $this::ANNOTATION_COLUMN) {
                        $modelProperty->setField($value);
                        $modelProperty->setName($property->getName());
                    }


                    if ($annotation === $this::ANNOTATION_KEY) {
                        $modelProperty->setIsKey($value);

                        if ($value) {
                            $entity->setKey($modelProperty->getField());
                        }
                    }
                }

                if (is_a($annotations, SynchronizedData::class)) {
                    $modelProperty->setLinkedUDO($annotations->LinkedUDO);
                    $modelProperty->setLinkedTable($annotations->LinkedTable);
                    $modelProperty->setLinkedSystemObject($annotations->LinkedSystemObject);
                    $modelProperty->setFieldTypeMD($annotations->Type);
                    $modelProperty->setFieldSubTypeMD($annotations->SubType);
                    $modelProperty->setSapTable($annotations->TableName);
                    $modelProperty->setSize($annotations->EditSize);
                    $modelProperty->setName($property->getName());

                    if ($annotations->ValidValuesMD !== null && $annotations->ValidValuesMD !== '') {
                        $choices = StringUtils::choicesStringToValidValuesMD($annotations->ValidValuesMD);
                        $modelProperty->setChoices($choices);
                    }
                }

            }
            $entity->setProperty($modelProperty);
        }
    }

    /**
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    private function checkToInit()
    {
        $cacheItem = $this->cache->getItem(self::ENTITIES_CACHE_KEY);

        if (empty($cacheItem->get())) {
            $this->initEntities();
        }
    }
}