<?php

namespace W3com\BoomBundle\HanaConst;

class ObjectTypes
{
    const OBJECT_TYPE_2 = 'BusinessPartners';
    const OBJECT_TYPE_13 = 'Invoices';
    const OBJECT_TYPE_18 = 'PurchaseInvoices';
    const OBJECT_TYPE_33 = 'Activities';
    const OBJECT_TYPE_46 = 'VendorPayments';

    public static function findEntityName($objectType)
    {
        return defined("self::OBJECT_TYPE_$objectType") ?
            constant("self::OBJECT_TYPE_$objectType"):
            '';
    }
}