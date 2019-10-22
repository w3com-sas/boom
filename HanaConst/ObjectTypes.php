<?php

namespace W3com\BoomBundle\HanaConst;

class ObjectTypes
{
    const OBJECT_TYPE_2 = 'BusinessPartners';
    const OBJECT_TYPE_13 = 'Invoices';

    public static function findEntityName($objectType)
    {
        $entityName = defined("self::OBJECT_TYPE_$objectType") ?
            constant("self::OBJECT_TYPE_$objectType"):
            '';

        return $entityName;
    }
}