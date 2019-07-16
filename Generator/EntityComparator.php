<?php

namespace W3com\BoomBundle\Generator;

use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;

class EntityComparator
{

    private $appEntities;

    private $odsEntities;

    private $toUpdateEntities = [];

    private $toCreateEntity = [];

    private $missingFieldsAmount = 0;

    /**
     * EntityComparator constructor.
     * @param AppInspector $appInspector
     * @param OdsInspector $odsInspector
     * @param SapTableInspector $entityInspector
     */
    public function __construct(AppInspector $appInspector, OdsInspector $odsInspector)
    {
      //  $this->sapTables = $entityInspector->getOdsEntities();
        $this->appEntities = $appInspector->getProjectEntities();
        $this->odsEntities = $odsInspector->getOdsEntities();
        $this->findCommonToUpdate();
    }

    public function getMissingEntities()
    {
        $arOdsEntities = $this->formatData(Property::TYPE_ODS);
        $arAppEntities = $this->formatData(Property::TYPE_APP);
        return array_diff_key($arOdsEntities, $arAppEntities);
    }

    public function getAmountMissingFields()
    {
        return $this->missingFieldsAmount;
    }

    public function getToUpdateEntities()
    {
        return $this->toUpdateEntities;
    }

    private function findCommonToUpdate()
    {
        $arAppEntities = $this->formatData(Property::TYPE_APP);
        $commonEntities = $this->getCommonEntities();
        /**
         * @var string $table
         * @var Entity $entity
         */
        foreach ($commonEntities as $table => $entity) {

            if (count($entity->getProperties()) !== count($arAppEntities[$table]->getProperties())) {

                $this->missingFieldsAmount += count($entity->getProperties()) - count(
                        $arAppEntities[$table]->getProperties()
                    );
                $this->toUpdateEntities[] = $entity;
            }
        }
    }

    private function formatData($type)
    {
        switch ($type) {
            case Property::TYPE_APP:
                $entities = $this->appEntities;
                break;
            case Property::TYPE_ODS:
                $entities = $this->odsEntities;
                break;
        }

        $arEntities = [];

        /** @var Entity $entity */
        /** @var array $entities */
        foreach ($entities as $entity) {
            $arEntities[$entity->getTable()] = $entity;
        }
        return $arEntities;
    }


    /**
     * @return array of common entities with ODS value
     */
    private function getCommonEntities()
    {
        $arOdsEntities = $this->formatData(Property::TYPE_ODS);
        $arAppEntities = $this->formatData(Property::TYPE_APP);
        return array_intersect_key($arOdsEntities, $arAppEntities);
    }

}