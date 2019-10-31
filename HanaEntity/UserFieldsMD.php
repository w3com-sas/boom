<?php

/**
 * This file is auto-generated by Boom.
 */

namespace W3com\BoomBundle\HanaEntity;

use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;

/**
 * @EntityMeta(read="sl", write="sl", aliasRead="UserFieldsMD", aliasWrite="UserFieldsMD")
 */
class UserFieldsMD extends \W3com\BoomBundle\HanaEntity\AbstractEntity
{
    const TYPE_MEMO = 'db_Memo';

    const TYPE_ALPHA = 'db_Alpha';
    const SUBTYPE_ADDRESS = 'st_Address';
    const SUBTYPE_PHONE = 'st_Phone';

    const TYPE_NUMERIC = 'db_Numeric';

    const TYPE_FLOAT = 'db_Float';
    const SUBTYPE_RATE = 'st_Rate';
    const SUBTYPE_AMOUNT = 'st_Sum';
    const SUBTYPE_PRICE = 'st_Price';
    const SUBTYPE_QUANTITY = 'st_Quantity';
    const SUBTYPE_PERCENTAGE = 'st_Percentage';
    const SUBTYPE_MEASUREMENT = 'st_Measurement';

    const TYPE_DATE = 'db_Date';
    const SUBTYPE_TIME = 'st_Time';

    const SUBTYPE_NONE = 'st_None';

    const MANDATORY_NO = 'tNO';
    const MANDATORY_YES = 'tYES';

	/** @EntityColumnMeta(column="Name", description="Name", type="string", quotes=true) */
	protected $name;

	/** @EntityColumnMeta(column="Type", description="Type", type="choice", quotes=true, choices="db_Alpha|db_Alpha#db_Memo|db_Memo#db_Numeric|db_Numeric#db_Date|db_Date#db_Float|db_Float") */
	protected $type;

	/** @EntityColumnMeta(column="Size", description="Size", type="int", quotes=false) */
	protected $size;

	/** @EntityColumnMeta(column="Description", description="Description", type="string", quotes=true) */
	protected $description;

	/** @EntityColumnMeta(column="SubType", description="SubType", type="choice", quotes=true, choices="st_None|st_None#st_Address|st_Address#st_Phone|st_Phone#st_Time|st_Time#st_Rate|st_Rate#st_Sum|st_Sum#st_Price|st_Price#st_Quantity|st_Quantity#st_Percentage|st_Percentage#st_Measurement|st_Measurement#st_Link|st_Link#st_Image|st_Image") */
	protected $subType;

	/** @EntityColumnMeta(column="LinkedTable", description="LinkedTable", type="string", quotes=true) */
	protected $linkedTable;

	/** @EntityColumnMeta(column="DefaultValue", description="DefaultValue", type="string", quotes=true) */
	protected $defaultValue;

	/** @EntityColumnMeta(column="TableName", isKey=true, description="TableName", type="string", quotes=true) */
	protected $tableName;

	/** @EntityColumnMeta(column="FieldID", description="FieldID", type="int", quotes=false) */
	protected $fieldID;

	/** @EntityColumnMeta(column="EditSize", description="EditSize", type="int", quotes=false) */
	protected $editSize;

	/** @EntityColumnMeta(column="Mandatory", description="Mandatory", type="choice", quotes=true, choices="Non|tNO#Oui|tYES") */
	protected $mandatory;

	/** @EntityColumnMeta(column="LinkedUDO", description="LinkedUDO", type="string", quotes=true) */
	protected $linkedUDO;

	/** @EntityColumnMeta(column="LinkedSystemObject", description="LinkedSystemObject", type="choice", quotes=true, choices="oChartOfAccounts|oChartOfAccounts#oBusinessPartners|oBusinessPartners#oBanks|oBanks#oItems|oItems#oVatGroups|oVatGroups#oPriceLists|oPriceLists#oSpecialPrices|oSpecialPrices#oItemProperties|oItemProperties#oBusinessPartnerGroups|oBusinessPartnerGroups#oUsers|oUsers#oInvoices|oInvoices#oCreditNotes|oCreditNotes#oDeliveryNotes|oDeliveryNotes#oReturns|oReturns#oOrders|oOrders#oPurchaseInvoices|oPurchaseInvoices#oPurchaseCreditNotes|oPurchaseCreditNotes#oPurchaseDeliveryNotes|oPurchaseDeliveryNotes#oPurchaseReturns|oPurchaseReturns#oPurchaseOrders|oPurchaseOrders#oQuotations|oQuotations#oIncomingPayments|oIncomingPayments#oJournalVouchers|oJournalVouchers#oJournalEntries|oJournalEntries#oStockTakings|oStockTakings#oContacts|oContacts#oCreditCards|oCreditCards#oCurrencyCodes|oCurrencyCodes#oPaymentTermsTypes|oPaymentTermsTypes#oBankPages|oBankPages#oManufacturers|oManufacturers#oVendorPayments|oVendorPayments#oLandedCostsCodes|oLandedCostsCodes#oShippingTypes|oShippingTypes#oLengthMeasures|oLengthMeasures#oWeightMeasures|oWeightMeasures#oItemGroups|oItemGroups#oSalesPersons|oSalesPersons#oCustomsGroups|oCustomsGroups#oChecksforPayment|oChecksforPayment#oInventoryGenEntry|oInventoryGenEntry#oInventoryGenExit|oInventoryGenExit#oWarehouses|oWarehouses#oCommissionGroups|oCommissionGroups#oProductTrees|oProductTrees#oStockTransfer|oStockTransfer#oWorkOrders|oWorkOrders#oCreditPaymentMethods|oCreditPaymentMethods#oCreditCardPayments|oCreditCardPayments#oAlternateCatNum|oAlternateCatNum#oBudget|oBudget#oBudgetDistribution|oBudgetDistribution#oMessages|oMessages#oBudgetScenarios|oBudgetScenarios#oUserDefaultGroups|oUserDefaultGroups#oSalesOpportunities|oSalesOpportunities#oSalesStages|oSalesStages#oActivityTypes|oActivityTypes#oActivityLocations|oActivityLocations#oDrafts|oDrafts#oDeductionTaxHierarchies|oDeductionTaxHierarchies#oDeductionTaxGroups|oDeductionTaxGroups#oAdditionalExpenses|oAdditionalExpenses#oSalesTaxAuthorities|oSalesTaxAuthorities#oSalesTaxAuthoritiesTypes|oSalesTaxAuthoritiesTypes#oSalesTaxCodes|oSalesTaxCodes#oQueryCategories|oQueryCategories#oFactoringIndicators|oFactoringIndicators#oPaymentsDrafts|oPaymentsDrafts#oAccountSegmentations|oAccountSegmentations#oAccountSegmentationCategories|oAccountSegmentationCategories#oWarehouseLocations|oWarehouseLocations#oForms1099|oForms1099#oInventoryCycles|oInventoryCycles#oWizardPaymentMethods|oWizardPaymentMethods#oBPPriorities|oBPPriorities#oDunningLetters|oDunningLetters#oUserFields|oUserFields#oUserTables|oUserTables#oPickLists|oPickLists#oPaymentRunExport|oPaymentRunExport#oUserQueries|oUserQueries#oMaterialRevaluation|oMaterialRevaluation#oCorrectionPurchaseInvoice|oCorrectionPurchaseInvoice#oCorrectionPurchaseInvoiceReversal|oCorrectionPurchaseInvoiceReversal#oCorrectionInvoice|oCorrectionInvoice#oCorrectionInvoiceReversal|oCorrectionInvoiceReversal#oContractTemplates|oContractTemplates#oEmployeesInfo|oEmployeesInfo#oCustomerEquipmentCards|oCustomerEquipmentCards#oWithholdingTaxCodes|oWithholdingTaxCodes#oBillOfExchangeTransactions|oBillOfExchangeTransactions#oKnowledgeBaseSolutions|oKnowledgeBaseSolutions#oServiceContracts|oServiceContracts#oServiceCalls|oServiceCalls#oUserKeys|oUserKeys#oQueue|oQueue#oSalesForecast|oSalesForecast#oTerritories|oTerritories#oIndustries|oIndustries#oProductionOrders|oProductionOrders#oDownPayments|oDownPayments#oPurchaseDownPayments|oPurchaseDownPayments#oPackagesTypes|oPackagesTypes#oUserObjectsMD|oUserObjectsMD#oTeams|oTeams#oRelationships|oRelationships#oUserPermissionTree|oUserPermissionTree#oActivityStatus|oActivityStatus#oChooseFromList|oChooseFromList#oFormattedSearches|oFormattedSearches#oAttachments2|oAttachments2#oUserLanguages|oUserLanguages#oMultiLanguageTranslations|oMultiLanguageTranslations#oDynamicSystemStrings|oDynamicSystemStrings#oHouseBankAccounts|oHouseBankAccounts#oBusinessPlaces|oBusinessPlaces#oLocalEra|oLocalEra#oNotaFiscalCFOP|oNotaFiscalCFOP#oNotaFiscalCST|oNotaFiscalCST#oNotaFiscalUsage|oNotaFiscalUsage#oClosingDateProcedure|oClosingDateProcedure#oBPFiscalRegistryID|oBPFiscalRegistryID#oSalesTaxInvoice|oSalesTaxInvoice#oPurchaseTaxInvoice|oPurchaseTaxInvoice#oPurchaseQuotations|oPurchaseQuotations#oStockTransferDraft|oStockTransferDraft#oInventoryTransferRequest|oInventoryTransferRequest#oPurchaseRequest|oPurchaseRequest") */
	protected $linkedSystemObject;

	/** @EntityColumnMeta(column="ValidValuesMD", description="ValidValuesMD", type="choice", quotes=true, choices="") */
	protected $validValuesMD;


	public function getName()
	{
		return $this->name;
	}


	public function setName($name)
	{
		return $this->set('name', $name);
	}


	public function getType()
	{
		return $this->type;
	}


	public function setType($type)
	{
		return $this->set('type', $type);
	}


	public function getSize()
	{
		return $this->size;
	}


	public function setSize($size)
	{
		return $this->set('size', $size);
	}


	public function getDescription()
	{
		return $this->description;
	}


	public function setDescription($description)
	{
		return $this->set('description', $description);
	}


	public function getSubType()
	{
		return $this->subType;
	}


	public function setSubType($subType)
	{
		return $this->set('subType', $subType);
	}


	public function getLinkedTable()
	{
		return $this->linkedTable;
	}


	public function setLinkedTable($linkedTable)
	{
		return $this->set('linkedTable', $linkedTable);
	}


	public function getDefaultValue()
	{
		return $this->defaultValue;
	}


	public function setDefaultValue($defaultValue)
	{
		return $this->set('defaultValue', $defaultValue);
	}


	public function getTableName()
	{
		return $this->tableName;
	}


	public function getFieldID()
	{
		return $this->fieldID;
	}


	public function setFieldID($fieldID)
	{
		return $this->set('fieldID', $fieldID);
	}


	public function getEditSize()
	{
		return $this->editSize;
	}


	public function setEditSize($editSize)
	{
		return $this->set('editSize', $editSize);
	}


	public function getMandatory()
	{
		return $this->mandatory;
	}


	public function setMandatory($mandatory)
	{
		return $this->set('mandatory', $mandatory);
	}


	public function getLinkedUDO()
	{
		return $this->linkedUDO;
	}


	public function setLinkedUDO($linkedUDO)
	{
		return $this->set('linkedUDO', $linkedUDO);
	}


	public function getLinkedSystemObject()
	{
		return $this->linkedSystemObject;
	}


	public function setLinkedSystemObject($linkedSystemObject)
	{
		return $this->set('linkedSystemObject', $linkedSystemObject);
	}


	public function getValidValuesMD()
	{
		return $this->validValuesMD;
	}


	public function setValidValuesMD($validValuesMD)
	{
		return $this->set('validValuesMD', $validValuesMD);
	}

    /**
     * @param mixed $tableName
     * @return UserFieldsMD
     */
    public function setTableName($tableName)
    {
        return $this->set('tableName', $tableName);
    }
}