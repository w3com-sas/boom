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
    public $description = '';

    /**
     * @var string
     */
    public $readColumn;

    /**
     * @var string
     */
    public $complexColumn;

    /**
     * @var string
     */
    public $complexEntity;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $isKey = false;

    /**
     * @var bool
     */
    public $quotes = true;

    /**
     * @var bool
     */
    public $readOnly = false;

    /**
     * @var string
     */
    public $ipName;

    /**
     * @var string
     */
    public $choices = '';

    /**
     * @var bool
     */
    public $synchro = false;

    /**
     * @var bool
     */
    public $isMandatory = false;

    /**
     * @var string
     */
    public $defaultValue = null;
}
