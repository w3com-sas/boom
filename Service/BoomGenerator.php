<?php

namespace W3com\BoomBundle\Service;

use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use W3com\BoomBundle\Exception\EntityNotFoundException;
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
     * @var ClassCreator
     */
    private $classCreator;

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
        $this->classCreator = new ClassCreator($manager, $this);

    }

    public function createSLEntity($tableName, $fields = [])
    {
        $this->getSLInspector()->initEntities();
        $entity = $this->SLInspector->getEntity($tableName);
        $phpClass = $this->classCreator->generateClass($entity, 'sl', $fields);
        file_put_contents($this->manager->config['entity_directory'] . '/'
            . $entity->getName() . '.php', $phpClass);
    }

    /**
     * @param $calcViewName
     * @throws EntityNotFoundException
     */
    public function createODSEntity($calcViewName)
    {
        $entity = $this->odsInspector->getEntity($calcViewName);
        $phpClass = $this->classCreator->generateClass($entity);
        file_put_contents($this->manager->config['entity_directory'] . '/'
            . $entity->getName() . '.php', $phpClass);
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