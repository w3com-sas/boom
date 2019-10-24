<?php

namespace W3com\BoomBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class SynchronizedData extends Annotation
{
    /**
     * @var string
     */
    public $Name = null;

    /**
     * @var string
     */
    public $Type = null;

    /**
     * @var string
     */
    public $SubType = null;

    /**
     * @var string
     */
    public $Description = '';

    /**
     * @var string
     */
    public $TableName = '';

    /**
     * @var int
     */
    public $EditSize = null;

    /**
     * @var string
     */
    public $LinkedTable = null;

    /**
     * @var string
     */
    public $LinkedUDO = null;

    /**
     * @var string
     */
    public $LinkedSystemObject = null;

    /**
     * @var string
     */
    public $ValidValuesMD = null;

    /**
     * @var string
     */
    public $DefaultValue = null;

    /**
     * @Annotation\Enum({"tYES","tNO"})
     */
    public $Mandatory = 'tNO';
}
