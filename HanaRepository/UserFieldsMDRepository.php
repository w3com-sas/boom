<?php

namespace W3com\BoomBundle\HanaRepository;

use W3com\BoomBundle\Parameters\Clause;
use W3com\BoomBundle\Repository\AbstractRepository;

class UserFieldsMDRepository extends AbstractRepository
{
    public function findByTableNameAndFieldName($tableName, $fieldName)
    {
        $params = $this->createParams()
            ->addFilter('tableName', $tableName)
            ->addFilter('name', $fieldName);

        $result = $this->findAll($params);

        return $result;
    }

    public function findByTableName($tableName)
    {
        $params = $this->createParams()
            ->addFilter('tableName', $tableName, Clause::CONTAINS);

        $result = $this->findAll($params);

        return $result;
    }
}