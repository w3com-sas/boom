<?php

namespace W3com\BoomBundle\HanaRepository;

use W3com\BoomBundle\HanaEntity\UserFieldsMD;
use W3com\BoomBundle\Parameters\Clause;
use W3com\BoomBundle\Repository\AbstractRepository;

class UserFieldsMDRepository extends AbstractRepository
{
    public function findByTableNameAndFieldName($tableName, $fieldName)
    {
        $params = $this->createParams()
            ->addFilter('tableName', $tableName, Clause::CONTAINS)
            ->addFilter('name', $fieldName);

        $result = $this->findAll($params);

        $res = [];
        /** @var UserFieldsMD $udf */
        foreach ($result as $udf) {
            if ($udf->getTableName() === $tableName) {
                $res[] = $udf;
            }
        }

        return $res;
    }

    public function findByTableName($tableName)
    {
        $params = $this->createParams()
            ->addFilter('tableName', $tableName, Clause::CONTAINS);

        $result = $this->findAll($params);

        $res = [];
        /** @var UserFieldsMD $udf */
        foreach ($result as $udf) {
            if ($udf->getTableName() === $tableName) {
                $res[] = $udf;
            }
        }

        return $res;
    }
}