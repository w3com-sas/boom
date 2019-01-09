<?php

namespace W3com\BoomBundle\Generator\Model;

class Property
{
    const PROPERTY_VISIBILITY = 'protected';

    const PROPERTY_ANNOTATION = '@EntityColumnMeta(column="ZZ")';

    const PROPERTY_ANNOTATION_ISKEY = '@EntityColumnMeta(column="ZZ", isKey=true)';

    const TYPE_ODS = 'ods';
    const TYPE_APP = 'app';

    private $name;

    private $type;

    private $field;

    private $fieldType;

    private $isKey;

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
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param mixed $fieldType
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;
    }

    /**
     * @return mixed
     */
    public function getIsKey()
    {
        return $this->isKey;
    }

    /**
     * @param mixed $isKey
     */
    public function setIsKey($isKey)
    {
        $this->isKey = $isKey;
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     */
    public function setField($field)
    {
        $this->field = $field;
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
    public function setType($type)
    {
        $this->type = $type;
    }

}