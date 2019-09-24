<?php

namespace W3com\BoomBundle\Generator\Model;

class Property
{
    const PROPERTY_VISIBILITY = 'protected';

    const PROPERTY_ANNOTATION_CHOICES = '@EntityColumnMeta(column="ZZ", description="ZZ_DESC", type="choice", quotes=ZZ_QUOTES, choices="ZZ_CHOICES")';

    const PROPERTY_ANNOTATION = '@EntityColumnMeta(column="ZZ", description="ZZ_DESC", type="ZZ_TYPE", quotes=ZZ_QUOTES)';

    const PROPERTY_ANNOTATION_ISKEY = '@EntityColumnMeta(column="ZZ", isKey=true, description="ZZ_DESC", type="ZZ_TYPE", quotes=ZZ_QUOTES)';

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

    private $type;

    private $field;

    private $fieldType;

    private $choices = [];

    private $isKey;

    private $hasQuotes;

    private $description;

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
        $hasQuotes = 'true';
        switch ($fieldType) {
            case self::FIELD_TYPE_DATE_TIME:
                $value = 'date';
                break;
            case self::FIELD_TYPE_DOUBLE:
                $value = 'float';
                break;
            case self::FIELD_TYPE_INTEGER:
                $value = 'int';
                $hasQuotes = 'false';
                break;
            case self::FIELD_TYPE_STRING:
                $value = 'string';
                break;
            case self::FIELD_TYPE_TIME:
                $value = 'date';
                break;
            default:
                $enumName = substr($fieldType, 6);
                $value = 'choice';
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

}