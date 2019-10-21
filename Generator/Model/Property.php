<?php

namespace W3com\BoomBundle\Generator\Model;

class Property
{
    const PROPERTY_VISIBILITY = 'protected';

    const PROPERTY_ANNOTATION_BASE = '@EntityColumnMeta(column="ZZ_FIELD", description="ZZ_DESC", type="ZZ_TYPE", synchro=ZZ_SYNCHRO';

    const PROPERTY_ANNOTATION_CHOICES = ', choices="ZZ_CHOICES"';

    const PROPERTY_ANNOTATION_QUOTES = ', quotes=false';

    const PROPERTY_ANNOTATION_IS_KEY = ', isKey=true';

    const PROPERTY_ANNOTATION_DEFAULT_VALUE = ', defaultValue="ZZ_DEFAULT_VALUE"';

    const PROPERTY_ANNOTATION_IS_MANDATORY = ', isMandatory=true';

    const PROPERTY_ANNOTATION_END = ')';

    const SYNCHRONIZE_ANNOTATION_BASE = '@SynchronizedData(Name="ZZ_COLUMN", Type="ZZ_TYPE", SubType="ZZ_SUBTYPE", Description="ZZ_DESCRIPTION", TableName="ZZ_TABLE", EditSize=ZZ_SIZE';

    const SYNCHRONIZE_ANNOTATION_MANDATORY = ', Mandatory="tYES"';

    const SYNCHRONIZE_ANNOTATION_DEFAULT_VALUE = ', DefaultValue=ZZ_DEFAULT_VALUE';

    const SYNCHRONIZE_ANNOTATION_LINKED_TABLE = ', LinkedTable="ZZ_LINKED_TABLE"';

    const SYNCHRONIZE_ANNOTATION_LINKED_UDO = ', LinkedUDO="ZZ_LINKED_UDO"';

    const SYNCHRONIZE_ANNOTATION_LINKED_SYSTEM_OBJECT = ', LinkedSystemObject="ZZ_LINKED_SYSTEM_OBJECT"';

    const SYNCHRONIZE_ANNOTATION_VALID_VALUES = ', ValidValuesMD="ZZ_VALID_VALUES"';

    const SYNCHRONIZE_ANNOTATION_END = ')';

//    const PROPERTY_ANNOTATION_CHOICES = '@EntityColumnMeta(column="ZZ", description="ZZ_DESC", type="choice", quotes=ZZ_QUOTES, choices="ZZ_CHOICES")';
//
//    const PROPERTY_ANNOTATION = '@EntityColumnMeta(column="ZZ", description="ZZ_DESC", type="ZZ_TYPE", quotes=ZZ_QUOTES)';
//
//    const PROPERTY_ANNOTATION_ISKEY = '@EntityColumnMeta(column="ZZ", isKey=true, description="ZZ_DESC", type="ZZ_TYPE", quotes=ZZ_QUOTES)';

    const TYPE_ODS = 'ods';

    const TYPE_SL = 'sl';

    const TYPE_APP = 'app';

    const FIELD_TYPE_INTEGER = 'Edm.Int32';

    const FIELD_TYPE_STRING = 'Edm.String';

    const FIELD_TYPE_DATE_TIME = 'Edm.DateTime';

    const FIELD_TYPE_DOUBLE = 'Edm.Double';

    const FIELD_TYPE_TIME = 'Edm.Time';

    const FIELD_TYPE_YES_NO_ENUM = 'SAPB1.BoYesNoEnum';

    private $name;

    private $var;

    private $field;

    private $fieldType;

    private $fieldTypeMD;

    private $fieldSubTypeMD;

    private $size;

    private $table;

    private $choices = [];

    private $isKey;

    private $hasQuotes;

    private $description;

    private $isMandatory = false;

    private $defaultValue;

    private $linkedTable;

    private $linkedUDO;

    private $linkedSystemObject;

    private $isUDF = false;

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
     * @param $fieldType
     * @param array $enumTypes
     */
    public function setFieldTypeSAPFormat($fieldType, $enumTypes = [])
    {
        $hasQuotes = true;
        switch ($fieldType) {
            case self::FIELD_TYPE_DATE_TIME:
                $value = 'date';
                $var = 'string';
                break;
            case self::FIELD_TYPE_DOUBLE:
                $value = 'float';
                $var = 'float';
                break;
            case self::FIELD_TYPE_INTEGER:
                $value = 'int';
                $var = 'int';
                $hasQuotes = false;
                break;
            case self::FIELD_TYPE_STRING:
                $value = 'string';
                $var = 'string';
                break;
            case self::FIELD_TYPE_TIME:
                $value = 'time';
                $var = 'string';
                break;
            default:
                $enumName = substr($fieldType, 6);
                $value = 'string';
                $var = 'string';
                $choices = [];

                foreach ($enumTypes as $enumType) {
                    if ($enumType['@Name'] === $enumName) {
                        $enumClassName = '\W3com\BoomBundle\HanaEnum\\' . $enumName;
                        foreach ($enumType['Member'] as $enumChoice) {
                            if (isset($enumChoice['@Name'])) {

                                $const = strtoupper($enumChoice['@Name']);

                                try {
                                    $choice = constant("$enumClassName::$const");
                                } catch (\ErrorException $e) {
                                    $choice = '';
                                }

                                $choices[$enumChoice['@Name']] = $choice === '' ? $enumChoice['@Name'] : $choice;
                            }
                        }
                        $this->setChoices($choices);
                        break;
                    }
                }

                break;
        }

        $this->setVar($var);
        $this->setFieldType($value);
        $this->setHasQuotes($hasQuotes);
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
     * @return Property
     */
    public function setIsKey($isKey)
    {
        $this->isKey = $isKey;

        return $this;
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
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @param mixed $choices
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function hasQuotes()
    {
        return $this->hasQuotes;
    }

    /**
     * @param mixed $hasQuotes
     */
    public function setHasQuotes($hasQuotes)
    {
        $this->hasQuotes = $hasQuotes;
    }

    /**
     * @param mixed $isUDF
     */
    public function setIsUDF($isUDF)
    {
        $this->isUDF = $isUDF;
    }

    /**
     * @return mixed
     */
    public function isUDF()
    {
        return $this->isUDF;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return mixed
     */
    public function isMandatory()
    {
        return $this->isMandatory;
    }

    /**
     * @param mixed $isMandatory
     */
    public function setIsMandatory($isMandatory): void
    {
        $this->isMandatory = $isMandatory;
    }

    /**
     * @return mixed
     */
    public function getVar()
    {
        return $this->var;
    }

    /**
     * @param mixed $var
     */
    public function setVar($var): void
    {
        $this->var = $var;
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
    public function setTable($table): void
    {
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getFieldTypeMD()
    {
        return $this->fieldTypeMD;
    }

    /**
     * @param mixed $fieldTypeMD
     */
    public function setFieldTypeMD($fieldTypeMD): void
    {
        $this->fieldTypeMD = $fieldTypeMD;
    }

    /**
     * @return mixed
     */
    public function getFieldSubTypeMD()
    {
        return $this->fieldSubTypeMD;
    }

    /**
     * @param mixed $fieldSubTypeMD
     */
    public function setFieldSubTypeMD($fieldSubTypeMD): void
    {
        $this->fieldSubTypeMD = $fieldSubTypeMD;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size): void
    {
        $this->size = $size;
    }

    /**
     * @return mixed
     */
    public function getLinkedTable()
    {
        return $this->linkedTable;
    }

    /**
     * @param mixed $linkedTable
     */
    public function setLinkedTable($linkedTable): void
    {
        $this->linkedTable = $linkedTable;
    }

    /**
     * @return mixed
     */
    public function getLinkedSystemObject()
    {
        return $this->linkedSystemObject;
    }

    /**
     * @param mixed $linkedSystemObject
     */
    public function setLinkedSystemObject($linkedSystemObject): void
    {
        $this->linkedSystemObject = $linkedSystemObject;
    }

    /**
     * @return mixed
     */
    public function getLinkedUDO()
    {
        return $this->linkedUDO;
    }

    /**
     * @param mixed $linkedUDO
     */
    public function setLinkedUDO($linkedUDO): void
    {
        $this->linkedUDO = $linkedUDO;
    }

}