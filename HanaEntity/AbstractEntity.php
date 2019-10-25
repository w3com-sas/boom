<?php

namespace W3com\BoomBundle\HanaEntity;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;

class AbstractEntity
{
    protected $changedFields = [];
    protected $collabPackField = '';
    private $refl = null;


    public function set($field, $value, $registerAsChanged = true)
    {
        $this->$field = $value;
        $this->changedFields[$field] = $registerAsChanged;

        return $this;
    }

    public function get($field)
    {
        return $this->$field;
    }

    public function hydrate($field, $value)
    {
        $this->$field = $value;
    }

    public function getChangedFields()
    {
        return $this->changedFields;
    }

    public function setByColumn($column, $value, $registerAsChanged = true)
    {
        $field = $this->getPropertyByColumn($column);
        $this->$field = $value;
        $this->changedFields[$field] = $registerAsChanged;
    }

    /**
     * @return string
     */
    public function getCollabPackField()
    {
        return $this->collabPackField;
    }

    /**
     * @param string $collabPackField
     */
    public function setCollabPackField($collabPackField): void
    {
        $this->collabPackField = $collabPackField;
    }

    /**
     * @param $description
     * @return string
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getFieldByDescription($description): string
    {
        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();

        foreach ($refl->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {

                if ($annotation->description == $description) {
                    return $property->getName();
                }
            }
        }
        return '';
    }

    /**
     * @param $fieldName
     * @return string
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getTypeByField($fieldName)
    {
        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();

        foreach ($refl->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {

                if ($property->getName() == $fieldName) {
                    return $annotation->type;
                }
            }
        }
        return '';
    }

    /**
     * @param $propertyName
     * @return string
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getChoicesByProperty($propertyName){

        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();

        foreach ($refl->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {

                if ($property->getName() == $propertyName) {
                    return $annotation->choices;
                }
            }
        }
        return '';
    }

    /**
     * @param $propertyName
     * @return string
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getDescriptionByProperty($propertyName): string
    {
        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();

        foreach ($refl->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {

                if ($property->getName() == $propertyName) {
                    return $annotation->description;
                }
            }
        }
        return '';
    }

    /**
     * @param $column
     * @return string
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getPropertyByColumn($column): string
    {
        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();

        foreach ($refl->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {

                if ($annotation->column == $column) {
                    return $property->getName();
                }
            }
        }
        return '';
    }

    /**
     * @return string
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getEntityJson()
    {
        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();
        $ar = [];
        foreach ($refl->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {
                $ar[$annotation->column] = $this->get($property->getName());
            }
        }

        return \GuzzleHttp\json_encode($ar);
    }

    /**
     * @throws \ReflectionException
     */
    private function getReflectionClass()
    {
        if ($this->refl === null){
            $this->refl = new \ReflectionClass(get_class($this));
        }
        return $this->refl;
    }


}
