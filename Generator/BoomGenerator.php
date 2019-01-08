<?php

namespace W3com\BoomBundle\Generator;

use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;

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
     * @var array
     */
    private $messages = [];

    /**
     * BoomGenerator constructor.
     * @param BoomManager $manager
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
        $this->appInspector = new AppInspector($manager);
        $this->odsInspector = new OdsInspector($manager);
        $this->comparator = new EntityComparator(
            $this->appInspector,
            $this->odsInspector
        );
        $this->classCreator = new ClassCreator($manager);

    }

    /**
     * @param $calcViewName
     * @T
     */
    public function createViewEntity($calcViewName)
    {
        $entity = $this->odsInspector->getOdsEntity($calcViewName);
        $phpClass = $this->classCreator->generateClass($entity);
        file_put_contents($this->manager->config['entity_directory'].'/'
        .$entity->getName().'.php', $phpClass);
    }

    public function createViewSchema()
    {
        /** @var Entity $entity */
        foreach ($this->comparator->getMissingEntities() as $entity){
            $phpClass = $this->classCreator->generateClass($entity);
            file_put_contents($this->manager->config['entity_directory'].'/'.$entity->getName().'.php',
                $phpClass);
        }
    }

    public function updateViewSchema()
    {
        foreach ($this->comparator->getToUpdateEntities() as $entity){
            $phpClass = $this->classCreator->generateClass($entity);
            file_put_contents($this->manager->config['entity_directory'].'/'.$entity->getName().'.php',
                $phpClass);
        }
    }

    public function inspectCurrentSchema()
    {

        if (count($this->comparator->getMissingEntities()) === 0){
            $this->messages[] = '0 entity to create.';
        } elseif (count($this->comparator->getMissingEntities()) === 1){
            $this->messages[]  = '1 entity to create.';
        } else {
            $this->messages[]  = count($this->comparator->getMissingEntities()).' entities to create.';
        }


        if ($this->comparator->getAmountMissingFields() === 0){
            $this->messages[]  = 'No field to add in project entities.';
        } elseif ($this->comparator->getAmountMissingFields() === 1) {
            $this->messages[]  = '1 field to add in project entities.';
        } else {
            $this->messages[]  = $this->comparator->getAmountMissingFields().' fields to add in project entities.';
        }

        return $this->messages;
    }

    /**
     * @return OdsInspector
     */
    public function getOdsInspector(): OdsInspector
    {
        return $this->odsInspector;
    }


}