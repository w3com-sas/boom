<?php

namespace W3com\BoomBundle\Generator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Finder\Finder;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;

class AppInspector
{

    const ANNOTATION_COLUMN = 'column';

    const ANNOTATION_KEY = 'isKey';

    private $finder;

    private $reader;

    private $manager;

    private $entities = [];

    /**
     * AppInspector constructor.
     * @param BoomManager $manager
     * @throws \ReflectionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct(BoomManager $manager)
    {

        $this->finder = new Finder();
        $this->reader = new AnnotationReader();
        $this->manager = $manager;
        $this->initProjectEntities();
    }

    /**
     * @throws \ReflectionException
     */
    private function initProjectEntities()
    {

        if (empty($this->entities)) {

            $this->finder->files()->in($this->manager->config['entity_directory']);

            foreach ($this->finder as $fileInfo) {

                $className = str_replace('.php', '', $fileInfo->getFilename());
                $class = new \ReflectionClass($this->manager->config['app_namespace'] .
                    '\HanaEntity\\' . $className);
                $this->hydrateEntityModel($class, $className);
            }
        }
    }

    /**
     * @param \ReflectionClass $class
     * @param $className
     * @throws \ReflectionException
     */
    private function hydrateEntityModel(\ReflectionClass $class, $className)
    {
        // One entity per $metadatum
        $entity = new Entity();
        $entity->setName($className);

        AnnotationRegistry::registerLoader('class_exists');
        foreach ($this->reader->getClassAnnotations($class) as $annotations) {

            foreach ($annotations as $annotation => $value) {
                // Alias Read
                if ($annotation === 'aliasRead' && $value !== null) {
                    $entity->setTable($value);
                    $this->hydratePropertyModel($class, $entity);
                }
            }
            if ($entity->getTable() !== null){
                $this->entities[] = $entity;
            }
        }
    }

    /**
     * @param \ReflectionClass $class
     * @param Entity $entity
     * @throws \ReflectionException
     */
    private function hydratePropertyModel(\ReflectionClass $class, Entity $entity)
    {
        foreach ($class->getDefaultProperties() as $propertyName => $value) {

            $property = new \ReflectionProperty($class->getName(), $propertyName);

            foreach ($this->reader->getPropertyAnnotations($property) as $annotations) {

                $modelProperty = new Property();
                $modelProperty->setType(Property::TYPE_APP);

                foreach ($annotations as $annotation => $value) {

                    if ($annotation == $this::ANNOTATION_COLUMN) {
                        $modelProperty->setField($value);
                        $modelProperty->setName(strtolower($value));
                    }


                    if ($annotation === $this::ANNOTATION_KEY) {
                        $modelProperty->setIsKey($value);

                        if ($value) {
                            $entity->setKey($modelProperty->getField());
                        }
                    }
                }
                $entity->setProperty($modelProperty);

            }
        }
    }

    public function getProjectEntity($name)
    {
        /** @var Entity $entity */
        foreach ($this->entities as $entity) {
            if ($entity->getName() === $name||$entity->getTable() === $name) {
                return $entity;
            }
        }
    }

    public function getProjectEntities()
    {
        return $this->entities;
    }
}