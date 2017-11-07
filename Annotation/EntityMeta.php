<?php


namespace W3com\BoomBundle\Annotation;


use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class EntityMeta extends Annotation
{
    /**
     * @Annotation\Enum({"sl","ods"})
     */
    public $read;

    /**
     * @Annotation\Enum({"sl","ods"})
     */
    public $write;

    /**
     * @var string
     */
    public $aliasSl;

    /**
     * @var string
     */
    public $aliasOds;
}