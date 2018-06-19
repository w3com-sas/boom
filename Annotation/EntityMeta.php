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
     * @Annotation\Enum({"sl","ods","odsl"})
     */
    public $read;

    /**
     * @Annotation\Enum({"sl","ods","odsl"})
     */
    public $write;

    /**
     * @var string
     */
    public $aliasRead = null;

    /**
     * @var string
     */
    public $aliasWrite = null;

}
