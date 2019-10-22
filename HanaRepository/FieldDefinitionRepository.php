<?php

namespace W3com\BoomBundle\HanaRepository;

use W3com\BoomBundle\Repository\AbstractRepository;

class FieldDefinitionRepository extends AbstractRepository
{
    public function findByTableName($tableName)
    {
        $params = $this->createParams()
            ->addFilter('table_name', $tableName);

        $result = $this->findAll($params);
        return $result;
    }
}
