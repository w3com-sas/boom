<?php

namespace W3com\BoomBundle\Generator\Model;

use W3com\BoomBundle\Generator\OdsInspector;

class Entity
{
    const ABSTRACT_ENTITY = 'AbstractEntity';

    const ANNOTATION = '@EntityMeta(read="ZZ_TYPE", write="", aliasRead="ZZ_ALIAS")';

    private $name;

    private $table;

    private $properties;

    private $key;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param $property
     */
    public function setProperty(Property $property)
    {
        if ($property->getType() == Property::TYPE_ODS){
            if ($property->getField() == $this->getKey()) {
                $property->setIsKey(true);
            } else {
                $property->setIsKey(false);
            }
        }

        $this->properties[] = $property;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param array $propertyRef
     */
    public function setKey($propertyRef)
    {
        if (is_array($propertyRef)){
            foreach ($propertyRef as $property => $value) {
                if ($property == OdsInspector::NAME_ENTITY_PROPERTY) {
                    $this->key = $value;
                }
            }
        } else {
            $this->key = $propertyRef;
        }

    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }


}