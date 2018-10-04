<?php

namespace W3com\BoomBundle\HanaEntity;

use Doctrine\Common\Annotations\AnnotationReader;

class AbstractEntity
{
    protected $changedFields = [];

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

    /**
     * @return string
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function getEntityJson()
    {
        $refl = new \ReflectionClass(get_class($this));
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
}
