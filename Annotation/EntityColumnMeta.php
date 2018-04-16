<?php

namespace W3com\BoomBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class EntityColumnMeta extends Annotation
{
    /**
     * @Annotation\Required()
     *
     * @var string
     */
    public $column;

    /**
     * @var string
     */
    public $readColumn;

    /** @var bool */
    public $isKey = false;

    /** @var bool */
    public $quotes = true;

    /** @var bool */
    public $readOnly = false;
}
