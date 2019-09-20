<?php

namespace W3com\BoomBundle\Generator;

use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;

class EntityComparator
{
    private $appInspector;

    private $odsInspector;

    private $toUpdateEntities = [];

    private $toCreateEntity = [];

    private $missingFieldsAmount = 0;

    /**
     * EntityComparator constructor.
     * @param AppInspector $appInspector
     * @param OdsInspector $odsInspector
     */
    public function __construct(AppInspector $appInspector, OdsInspector $odsInspector)
    {
        $this->appInspector = $appInspector;
        $this->odsInspector = $odsInspector;
    }

    public function getMissingEntities()
    {
        $arOdsEntities = $this->formatData(Property::TYPE_ODS);
        $arAppEntities = $this->formatData(Property::TYPE_APP);
        return array_diff_key($arOdsEntities, $arAppEntities);
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

    public function getAmountMissingFields()
    {
        return $this->missingFieldsAmount;
    }

    public function getToUpdateEntities()
    {
        $arAppEntities = $this->formatData(Property::TYPE_APP);
        $commonEntities = $this->getCommonEntities();
        $toUpdateEntities = [];
        /**
         * @var string $table
         * @var Entity $entity
         */
        foreach ($commonEntities as $table => $entity) {

            if (count($entity->getProperties()) !== count($arAppEntities[$table]->getProperties())) {

                $this->missingFieldsAmount += count($entity->getProperties()) - count(
                        $arAppEntities[$table]->getProperties()
                    );
                $toUpdateEntities[] = $entity;
            }
        }
        return $toUpdateEntities;
    }


    private function formatData($type)
    {
        switch ($type) {
            case Property::TYPE_APP:
                $this->appInspector->initEntities();
                $entities = $this->appInspector->getEntities();
                break;
            case Property::TYPE_ODS:
                $this->odsInspector->initEntities();
                $entities = $this->odsInspector->getEntities();
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




}