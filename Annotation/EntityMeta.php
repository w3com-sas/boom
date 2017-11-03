<?php


namespace W3com\BoomBundle\Annotation;


use Doctrine\Common\Annotations\Annotation;

class EntityMeta extends Annotation
{
    public $read;

    public $write;

    public $aliasSl;

    public $aliasOds;
}