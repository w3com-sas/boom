<?php

namespace W3com\BoomBundle\Generator;

use Symfony\Component\Translation\Exception\NotFoundResourceException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\Service\BoomManager;

class SapTableInspector
{
    const CV_DESC_FIELD = 'FieldDefinitionType';
    const FIELD_TABLE = 'TABLE_NAME';
    const FIELD_DESC = 'TableID';
    const FIELD_ = 'TableID';

    private $boom;

    public function __construct(BoomManager $manager)
    {
        $this->boom = $manager;
    }


    public function getEntity($name)
    {
        $repo = $this->boom->getRepository(self::CV_DESC_FIELD);
        $params = $repo->createParams()->addFilter(strtolower(self::FIELD_TABLE),
            $name);
        $userFields = $repo->findAll($params);

        if (count($userFields) > 0) {

            $entity = new Entity();
            $entity->setTable($userFields[0]->get('tableName'));
            $entity->setName($userFields[0]->get(''));

            foreach ($userFields as $userField) {

                $property = new Property();
                $property->setName('');
                $entity->setProperty($property);

                $this->entities[$entity->getName()] = $entity;
            }

            return $entity;
        }

    }


}