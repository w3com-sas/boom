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
    private $aliasRead;

    /**
     * @var string
     */
    private $aliasWrite;

    /**
     * @var string
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    private $aliasSl;

    /**
     * @var string
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
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
     * @param string $entityName
     * @param string $entityClassName
     * @param BoomManager $manager
     * @param string $read
     * @param string $write
     * @param string $aliasSl
     * @param string $aliasOds
     * @param string $key
     * @param string $aliasRead
     * @param string $aliasWrite
     * @param array $columns
     */
    public function __construct(
        string $entityName,
        string $entityClassName,
        BoomManager $manager,
        string $read,
        string $write,
        string $aliasSl=null,
        string $aliasOds=null,
        string $key,
        string $aliasRead=null,
        string $aliasWrite=null,
        array $columns
    ) {
        $this->entityName = $entityName;
        $this->entityClassName = $entityClassName;
        $this->manager = $manager;
        $this->read = $read;
        $this->write = $write;
        $this->aliasRead = $aliasRead;
        $this->aliasWrite = $aliasWrite;
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
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    public function getAliasSl(): ?string
    {
        if($this->aliasSl != null){
            @trigger_error(
                'getAliasSL() is deprecated since BOOM version 0.4 and will be removed in 1.0. Use getAliasRead and getAliasWrite().',
                E_USER_DEPRECATED
            );
        }

        return $this->aliasSl;
    }

    /**
     * @return string
     * @deprecated since version 0.4, to be removed in 1.0. Use aliasRead and aliasWrite instead.
     */
    public function getAliasOds(): ?string
    {
        if($this->aliasOds != null){
            @trigger_error(
                'getAliasOds() is deprecated since BOOM version 0.4 and will be removed in 1.0. Use getAliasRead and getAliasWrite().',
                E_USER_DEPRECATED
            );
        }

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

    /**
     * @return string
     */
    public function getAliasRead(): ?string
    {
        return $this->aliasRead;
    }

    /**
     * @return string
     */
    public function getAliasWrite(): ?string
    {
        return $this->aliasWrite;
    }
}
