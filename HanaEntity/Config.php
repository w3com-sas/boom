<?php

namespace W3com\BoomBundle\HanaEntity;

use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;
use W3com\BoomBundle\HanaEntity\AbstractEntity;

/**
 * Class Config.
 *
 * @EntityMeta(read="ods", write="ods", aliasRead="Config", aliasWrite="")
 */
class Config extends AbstractEntity
{
    /**
     * @var string
     * @EntityColumnMeta(column="Code", isKey=true)
     */
    protected $code;
    /**
     * @var string
     * @EntityColumnMeta(column="ConfigType")
     */
    protected $configType;

    /**
     * @var string
     * @EntityColumnMeta(column="Key")
     */
    protected $key;

    /**
     * @var string
     * @EntityColumnMeta(column="Value")
     */
    protected $value;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return Config
     */
    public function setCode(string $code): Config
    {
        return $this->set('code', $code);
    }

    /**
     * @return string
     */
    public function getConfigType(): string
    {
        return $this->configType;
    }

    /**
     * @param string $configType
     *
     * @return Config
     */
    public function setConfigType(string $configType): Config
    {
        return $this->set('configType', $configType);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return Config
     */
    public function setKey(string $key): Config
    {
        return $this->set('key', $key);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Config
     */
    public function setValue(string $value): Config
    {
        return $this->set('value', $value);
    }
}
