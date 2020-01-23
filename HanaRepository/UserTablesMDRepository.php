<?php

namespace W3com\BoomBundle\HanaRepository;

use W3com\BoomBundle\HanaEntity\UserTablesMD;
use W3com\BoomBundle\Parameters\Clause;
use W3com\BoomBundle\Repository\AbstractRepository;

class UserTablesMDRepository extends AbstractRepository
{
    public function findAllTypedTables()
    {
        $params = $this->createParams()
            ->addFilter('tableType', UserTablesMD::TABLE_TYPE_NO_OBJECT, Clause::NOT_EQUALS)
            ->addFilter('tableType', UserTablesMD::TABLE_TYPE_NO_OBJECT_AUTO_INCREMENT, Clause::NOT_EQUALS)
            ->addFilter('tableName', 'W3C', Clause::CONTAINS)
        ;

        $result = $this->findAll($params);

        return $result;
    }
}