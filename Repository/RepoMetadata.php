<?php

namespace W3com\BoomBundle\Repository;

use W3com\BoomBundle\Service\BoomManager;

class RepoMetadata
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $entityClassName;

    /**
     * @var BoomManager
     */
    private $manager;

    /**
     * @var string
     */
    private $read;

    /**
     * @var string
     */
    private $write;

    /**
     * @var string
     */
    private $aliasSl;

    /**
     * @var string
     */
    private $aliasOds;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $columns;

    /**
     * RepoMetadata constructor.
     *
     * @param string      $entityName
     * @param string      $entityClassName
     * @param BoomManager $manager
     * @param string      $read
     * @param string      $write
     * @param string      $aliasSl
     * @param string      $aliasOds
     * @param string      $key
     * @param array       $columns
     */
    public function __construct(
        string $entityName,
        string $entityClassName,
        BoomManager $manager,
        string $read,
        string $write,
        string $aliasSl,
        string $aliasOds,
        string $key,
        array $columns
    ) {
        $this->entityName = $entityName;
        $this->entityClassName = $entityClassName;
        $this->manager = $manager;
        $this->read = $read;
        $this->write = $write;
        $this->aliasSl = $aliasSl;
        $this->aliasOds = $aliasOds;
        $this->key = $key;
        $this->columns = $columns;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    /**
     * @return BoomManager
     */
    public function getManager(): BoomManager
    {
        return $this->manager;
    }

    /**
     * @return string
     */
    public function getRead(): string
    {
        return $this->read;
    }

    /**
     * @return string
     */
    public function getWrite(): string
    {
        return $this->write;
    }

    /**
     * @return string
     */
    public function getAliasSl(): string
    {
        return $this->aliasSl;
    }

    /**
     * @return string
     */
    public function getAliasOds(): string
    {
        return $this->aliasOds;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
