<?php

namespace W3com\BoomBundle\Generator\Model;

use W3com\BoomBundle\Generator\OdsInspector;

class Entity
{
    const ABSTRACT_ENTITY = 'AbstractEntity';

    const ANNOTATION_READ = '@EntityMeta(read="ZZ_TYPE_READ", write="ZZ_TYPE_WRITE", aliasRead="ZZ_ALIAS")';

    const ANNOTATION_WRITE = '@EntityMeta(read="ZZ_TYPE_READ", write="ZZ_TYPE_WRITE", aliasRead="ZZ_ALIAS", aliasWrite="ZZ_ALIAS_WRITE")';

    public $name;

    public $table;

    public $properties;

    public $key;

    public static function formatTableName($name)
    {
        if (substr($name, -4) === 'Type') {

            $name = substr($name, 0, strlen($name) - 4);

            if (substr($name, -5) === 'Query') {


                return substr($name, 0, strlen($name) - 5);
            }
        } elseif (substr($name, -5) == 'Query') {

            return substr($name, 0, strlen($name) - 5);

        }

        return $name;
    }

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
        $this->properties[] = $property->setIsKey(($property->getField() == $this->getKey()));
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $propertyRef
     */
    public function setKey($propertyRef)
    {
        if (is_array($propertyRef)) {
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


    public function getProperty($name)
    {
        /** @var Property $property */
        foreach ($this->properties as $property){
            if ($property->getName() === $name){
                return $property;
            } elseif ($property->getField() === $name){
                return $property;
            }
        }
        return null;
    }

    /**
     * @param mixed $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

}