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
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    public $aliasSl=null;

    /**
     * @var string
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    public $aliasOds=null;

    /**
     * @var string
     */
    public $aliasRead=null;

    /**
     * @var string
     */
    public $aliasWrite=null;

}
