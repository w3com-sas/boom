<?php

namespace W3com\BoomBundle\Service;

use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use W3com\BoomBundle\Generator\AppInspector;
use W3com\BoomBundle\Generator\ClassCreator;
use W3com\BoomBundle\Generator\EntityComparator;
use W3com\BoomBundle\Generator\Messenger;
use W3com\BoomBundle\Generator\OdsInspector;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\SLInspector;

class BoomGenerator
{
    /**
     * @var BoomManager
     */
    private $manager;

    /**
     * @var OdsInspector
     */
    private $odsInspector;

    /**
     * @var SLInspector
     */
    private $SLInspector;

    /**
     * @var AppInspector
     */
    private $appInspector;

    /**
     * @var EntityComparator
     */
    private $comparator;

    /**
     * @var ClassCreator
     */
    private $classCreator;

    /**
     * @var Messenger
     */
    private $messenger;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * BoomGenerator constructor.
     * @param BoomManager $manager
     * @param AdapterInterface $cache
     * @throws AnnotationException
     */
    public function __construct(BoomManager $manager, AdapterInterface $cache)
    {
        $this->manager = $manager;
        $this->appInspector = new AppInspector($manager);
        $this->SLInspector = new SLInspector($manager, $cache);
        $this->odsInspector = new OdsInspector($manager, $cache);
        $this->comparator = new EntityComparator(
            $this->appInspector,
            $this->odsInspector
        );
        $this->classCreator = new ClassCreator($manager, $this);
        $this->messenger = new Messenger();

    }

    public function createSapEntity($tableName, $fields = [])
    {
        $this->getSLInspector()->initEntities();
        $entity = $this->SLInspector->getEntity($tableName);
        $phpClass = $this->classCreator->generateClass($entity, 'sl', $fields);
        file_put_contents($this->manager->config['entity_directory'] . '/'
            . $entity->getName() . '.php', $phpClass);
    }

    /**
     * @param $calcViewName
     * @throws \W3com\BoomBundle\Exception\EntityNotFoundException
     */
    public function createViewEntity($calcViewName)
    {
        $entity = $this->odsInspector->getEntity($calcViewName);
        $phpClass = $this->classCreator->generateClass($entity);
        file_put_contents($this->manager->config['entity_directory'] . '/'
            . $entity->getName() . '.php', $phpClass);
    }

    public function createViewSchema()
    {
        /** @var Entity $entity */
        foreach ($this->comparator->getMissingEntities() as $entity) {
            $phpClass = $this->classCreator->generateClass($entity);
            file_put_contents($this->manager->config['entity_directory'] . '/' . $entity->getName() . '.php',
                $phpClass);
            $this->messenger->addCreatedEntities($entity);
        }
        return $this->messenger->getCreatedEntities();
    }

    public function updateViewSchema()
    {
        foreach ($this->comparator->getToUpdateEntities() as $entity) {
            $phpClass = $this->classCreator->generateClass($entity);
            file_put_contents($this->manager->config['entity_directory'] . '/' . $entity->getName() . '.php',
                $phpClass);
            $this->messenger->addUpdatedEntity($entity);
        }
        return $this->messenger->getUpdatedEntities();
    }

    public function inspectCurrentSchema()
    {
        $this->messages[] = count($this->comparator->getMissingEntities()) . ' entities to create.';
        $this->messages[] = $this->comparator->getAmountMissingFields() . ' fields to add in project entities.';
        return $this->messages;
    }

    /**
     * @return OdsInspector
     */
    public function getOdsInspector(): OdsInspector
    {
        return $this->odsInspector;
    }

    /**
     * @return AppInspector
     */
    public function getAppInspector(): AppInspector
    {
        return $this->appInspector;
    }

    /**
     * @return SLInspector
     */
    public function getSLInspector(): SLInspector
    {
        return $this->SLInspector;
    }

    /**
     * @return BoomManager
     */
    public function getManager(): BoomManager
    {
        return $this->manager;
    }


}