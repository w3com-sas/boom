<?php

namespace W3com\BoomBundle\Generator\Model;

use W3com\BoomBundle\Generator\OdsInspector;

class Entity
{
    const ABSTRACT_ENTITY = 'AbstractEntity';

    const ANNOTATION_READ = '@EntityMeta(read="ZZ_TYPE_READ", write="ZZ_TYPE_WRITE", aliasRead="ZZ_ALIAS")';

    const ANNOTATION_WRITE = '@EntityMeta(read="ZZ_TYPE_READ", write="ZZ_TYPE_WRITE", aliasRead="ZZ_ALIAS", aliasWrite="ZZ_ALIAS_WRITE", synchro=ZZ_SYNCHRO)';

    const SYNCHRONIZE_ANNOTATION_BASE = '@EntitySynchronizedData(TableName="ZZ_TABLE_NAME", TableDescription="ZZ_TABLE_DESCRIPTION", TableType="ZZ_TABLE_TYPE", Archivable="ZZ_ARCHIVABLE"';

    const SYNCHRONIZE_ANNOTATION_ARCHIVE_DATE = ', ArchiveDateField="ZZ_ARCHIVE_DATE"';

    const SYNCHRONIZE_ANNOTATION_END = ')';

    public $name;

    public $table;

    public $description;

    public $type;

    public $archivable;

    public $archiveDate;

    public $sapTable;

    public $properties;

    public $key;

    public $toSynchronize = false;

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

    /**
     * @return bool
     */
    public function isToSynchronize(): bool
    {
        return $this->toSynchronize;
    }

    /**
     * @param bool $toSynchronize
     */
    public function setToSynchronize(bool $toSynchronize): void
    {
        $this->toSynchronize = $toSynchronize;
    }

    /**
     * @return mixed
     */
    public function getSapTable()
    {
        return $this->sapTable;
    }

    /**
     * @param mixed $sapTable
     */
    public function setSapTable($sapTable): void
    {
        $this->sapTable = $sapTable;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getArchivable()
    {
        return $this->archivable;
    }

    /**
     * @param mixed $archivable
     */
    public function setArchivable($archivable): void
    {
        $this->archivable = $archivable;
    }

    /**
     * @return mixed
     */
    public function getArchiveDate()
    {
        return $this->archiveDate;
    }

    /**
     * @param mixed $archiveDate
     */
    public function setArchiveDate($archiveDate): void
    {
        $this->archiveDate = $archiveDate;
    }

}