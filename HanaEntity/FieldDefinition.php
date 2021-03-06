<?php

/**
 * This file is auto-generated by Boom.
 */

namespace W3com\BoomBundle\HanaEntity;

use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;

/**
 * @EntityMeta(read="ods", write="ods", aliasRead="FieldDefinition")
 */
class FieldDefinition extends AbstractEntity
{
    /** @EntityColumnMeta(column="ID", isKey=true) */
    protected $id;

    /** @EntityColumnMeta(column="TABLE_NAME") */
    protected $table_name;

    /** @EntityColumnMeta(column="COLUMN_NAME") */
    protected $column_name;

    /** @EntityColumnMeta(column="DATA_TYPE_NAME") */
    protected $data_type_name;

    /** @EntityColumnMeta(column="OFFSET") */
    protected $offset;

    /** @EntityColumnMeta(column="LENGTH") */
    protected $length;

    /** @EntityColumnMeta(column="POSITION") */
    protected $position;

    /** @EntityColumnMeta(column="DESCRIPTION") */
    protected $description;

    /** @EntityColumnMeta(column="Choices") */
    protected $choices;

    /** @EntityColumnMeta(column="NotNull") */
    protected $isMandatory;

    /** @EntityColumnMeta(column="Dflt") */
    protected $defaultValue;

    public function getTable_name()
    {
        return $this->table_name;
    }


    public function getColumn_name()
    {
        return $this->column_name;
    }


    public function getData_type_name()
    {
        return $this->data_type_name;
    }


    public function getOffset()
    {
        return $this->offset;
    }


    public function getLength()
    {
        return $this->length;
    }


    public function getPosition()
    {
        return $this->position;
    }


    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return mixed
     */
    public function isMandatory()
    {
        return $this->isMandatory;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
