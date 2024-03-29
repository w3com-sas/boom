<?php

namespace W3com\BoomBundle\Service;

use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use W3com\BoomBundle\Exception\EntityNotFoundException;
use W3com\BoomBundle\Generator\AppInspector;
use W3com\BoomBundle\Generator\ClassCreator;
use W3com\BoomBundle\Generator\EntityComparator;
use W3com\BoomBundle\Generator\Messenger;
use W3com\BoomBundle\Generator\Model\Property;
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
     * @param FilesystemAdapter $cache
     * @param AppInspector $appInspector
     */
    public function __construct(BoomManager $manager, AppInspector $appInspector)
    {
        $cache = new FilesystemAdapter();
        $this->manager = $manager;
        $this->appInspector = $appInspector;
        $this->SLInspector = new SLInspector($manager, $cache);
        $this->odsInspector = new OdsInspector($manager, $cache);
        $this->classCreator = new ClassCreator($manager, $this);

    }

    public function createSLEntity($tableName, $fields = [])
    {
        $this->getSLInspector()->initEntities();
        $entity = $this->SLInspector->getEntity($tableName);
        $this->classCreator->generateClass($entity, 'sl', $fields);
    }

    public function addAndRemovePropertiesInAppEntity($propertiesToAdd, $propertiesToRemove, Entity $appEntity)
    {
        $this->classCreator->addAndRemovePropertiesToExistingClass($propertiesToAdd, $propertiesToRemove, $appEntity);
    }

    /**
     * @param $calcViewName
     * @throws EntityNotFoundException
     */
    public function createODSEntity($calcViewName)
    {
        $this->getOdsInspector()->initEntities();
        $entity = $this->odsInspector->getEntity($calcViewName);
        $this->classCreator->generateClass($entity);
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
