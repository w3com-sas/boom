<?php

namespace W3com\BoomBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class EntitySynchronizedData extends Annotation
{
    /**
     * @var string
     */
    public $TableName = null;

    /**
     * @var string
     */
    public $TableDescription = null;

    /**
     * @var string
     */
    public $TableType = null;

    /**
     * @var string
     */
    public $Archivable = null;

    /**
     * @var string
     */
    public $ArchiveDateField = null;
}
