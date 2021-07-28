<?php

namespace W3com\BoomBundle\HanaEntity;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use W3com\BoomBundle\Annotation\EntityColumnMeta;

class AbstractEntity
{
    protected $changedFields = [];
    protected $collabPackField = '';
    private $refl = null;
    private $propertiesAnnotation = [];


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
    public function getChoicesByProperty($propertyName)
    {

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

            if (array_key_exists($property->getName(), $this->propertiesAnnotation)) {
                $annotation = $this->propertiesAnnotation[$property->getName()];
            } else {
                $annotation = $reader->getPropertyAnnotation($property, EntityColumnMeta::class);
                $this->propertiesAnnotation[$property->getName()] = $annotation;
            }

            if ($annotation !== null && $annotation->column == $column) {
                return $property->getName();
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
     * @return array
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getEntityArray()
    {
        $refl = $this->getReflectionClass();
        $reader = new AnnotationReader();
        $ar = [];
        foreach ($refl->getProperties() as $property) {

            if ($annotation = $reader->getPropertyAnnotation(
                $property,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {

                if ($this->get($property->getName()) instanceof AbstractEntity && !in_array($property->getName(), ["collabPackField", "changedFields"])){
                    $ar[$annotation->column] = $this->get($property->getName())->getEntityArray();
                    continue;
                }

                if (is_iterable($this->get($property->getName())) && !in_array($property->getName(), ["collabPackField", "changedFields"])){
                    foreach ($this->get($property->getName()) as $hanaEntity){
                        $ar[$annotation->column][] = $hanaEntity->getEntityArray();
                    }
                    continue;
                }

                $ar[$annotation->column] = $this->get($property->getName());
            }
        }
        return $ar;
    }

    /**
     * Normalize the Entity to an array.
     *
     * TODO : If this, result same as the @getEntityToArray in a ComplexEntity case ...
     * TODO : ... @getEntityToArray will become deprecated.
     * TODO : But probably not usable like @getEntityToArray because of the the substring.
     */
    public function normalize(): array
    {
        $entityArray = (array) $this;
        $normalize = [];

        foreach ($entityArray as $key => $value) {
            if (!strpos($key, 'changedFields')
                && !strpos($key, 'refl')
                && !strpos($key, 'propertiesAnnotation')
                && !strpos($key, 'collabPackField')
            ) {
                // Substring of the key needed cause the array casting create 3 chars related to the field visibility
                $normalize[ucfirst(substr($key, 3))] = $value;
            }
        }

        return $normalize;
    }

    /**
     * @throws \ReflectionException
     */
    private function getReflectionClass()
    {
        if ($this->refl === null) {
            $this->refl = new \ReflectionClass(get_class($this));
        }
        return $this->refl;
    }

    /**
     * @param string $collectionProperty
     * @param string $field
     * @param $value
     * @return AbstractEntity|null
     */
    public function getOneInCollection(string $collectionProperty, string $field, $value)
    {
        /** @var AbstractEntity $collection */
        foreach ($this->get($collectionProperty) as $collection) {
            if ($collection->get($field) === $value) {
                return $collection;
            }
        }
        return null;
    }
}
