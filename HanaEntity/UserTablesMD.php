<?php

/**
 * This file is auto-generated by Boom.
 */

namespace W3com\BoomBundle\HanaEntity;

use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;

/**
 * @EntityMeta(read="sl", write="sl", aliasRead="UserTablesMD", aliasWrite="UserTablesMD")
 */
class UserTablesMD extends \W3com\BoomBundle\HanaEntity\AbstractEntity
{
    const TABLE_TYPE_OBJECT = 'bott_NoObject';

    const ARCHIVABLE_NO = 'tNO';

	/** @EntityColumnMeta(column="TableName", isKey=true, description="TableName", type="string", quotes=true) */
	protected $tableName;

	/** @EntityColumnMeta(column="TableDescription", description="TableDescription", type="string", quotes=true) */
	protected $tableDescription;

	/** @EntityColumnMeta(column="TableType", description="TableType", type="choice", quotes=true, choices="bott_Document|bott_Document#bott_DocumentLines|bott_DocumentLines#bott_MasterData|bott_MasterData#bott_MasterDataLines|bott_MasterDataLines#bott_NoObject|bott_NoObject#bott_NoObjectAutoIncrement|bott_NoObjectAutoIncrement") */
	protected $tableType;

	/** @EntityColumnMeta(column="Archivable", description="Archivable", type="choice", quotes=true, choices="Non|tNO#Oui|tYES") */
	protected $archivable;

	/** @EntityColumnMeta(column="ArchiveDateField", description="ArchiveDateField", type="string", quotes=true) */
	protected $archiveDateField;


	public function getTableName()
	{
		return $this->tableName;
	}


	public function getTableDescription()
	{
		return $this->tableDescription;
	}


	public function setTableDescription($tableDescription)
	{
		return $this->set('tableDescription', $tableDescription);
	}


	public function getTableType()
	{
		return $this->tableType;
	}


	public function setTableType($tableType)
	{
		return $this->set('tableType', $tableType);
	}


	public function getArchivable()
	{
		return $this->archivable;
	}


	public function setArchivable($archivable)
	{
		return $this->set('archivable', $archivable);
	}


	public function getArchiveDateField()
	{
		return $this->archiveDateField;
	}


	public function setArchiveDateField($archiveDateField)
	{
		return $this->set('archiveDateField', $archiveDateField);
	}

    /**
     * @param mixed $tableName
     * @return UserTablesMD
     */
    public function setTableName($tableName)
    {
        return $this->set('tableName', $tableName);
    }
}