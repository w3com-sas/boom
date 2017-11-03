<?php


namespace W3com\BoomBundle\Annotation;


use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class EntityColumnMeta extends Annotation
{
    public $column;

    public $isKey = false;

    public $quotes = true;
}