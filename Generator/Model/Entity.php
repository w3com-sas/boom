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

    public static function formatTableName($name)
    {
        if (substr($name, -4) === 'Type') {

            $name = substr($name, 0, strlen($name) - 4);

            if (substr($name, -5) === 'Query') {


                return substr($name, 0, strlen($name) -5);
            }
        } elseif (substr($name, -5) == 'Query') {

            return substr($name, 0, strlen($name) -5);
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
        if ($property->getType() == Property::TYPE_ODS) {
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


}