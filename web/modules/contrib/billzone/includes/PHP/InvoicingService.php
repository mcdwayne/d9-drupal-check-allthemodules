<?php

if (!class_exists("CreateInvoice")) {
/**
 * CreateInvoice
 */
class CreateInvoice {
	/**
	 * @access public
	 * @var CreateInvoiceRequest
	 */
	public $request;
}}

if (!class_exists("ConvertExternalInvoice")) {
/**
 * ConvertExternalInvoice
 */
class ConvertExternalInvoice {
	/**
	 * @access public
	 * @var ConvertExternalInvoiceRequest
	 */
	public $request;
}}

if (!class_exists("DownloadInvoice")) {
/**
 * DownloadInvoice
 */
class DownloadInvoice {
	/**
	 * @access public
	 * @var DownloadInvoiceRequest
	 */
	public $request;
}}

if (!class_exists("DownloadInvoices")) {
/**
 * DownloadInvoices
 */
class DownloadInvoices {
	/**
	 * @access public
	 * @var DownloadInvoicesRequest
	 */
	public $request;
}}

if (!class_exists("GetInvoiceData")) {
/**
 * GetInvoiceData
 */
class GetInvoiceData {
	/**
	 * @access public
	 * @var GetInvoiceDataRequest
	 */
	public $request;
}}

if (!class_exists("RequestBase")) {
/**
 * RequestBase
 */
class RequestBase {
	/**
	 * @access public
	 * @var string
	 */
	public $RequestId;
	/**
	 * @access public
	 * @var string
	 */
	public $SecurityToken;
}}

if (!class_exists("ConvertExternalInvoiceRequest")) {
/**
 * ConvertExternalInvoiceRequest
 */
class ConvertExternalInvoiceRequest extends RequestBase {
	/**
	 * @access public
	 * @var ConvertExternalInvoiceTransaction
	 */
	public $ConvertExternalInvoiceTransaction;
}}

if (!class_exists("DownloadInvoiceRequest")) {
/**
 * DownloadInvoiceRequest
 */
class DownloadInvoiceRequest extends RequestBase {
	/**
	 * @access public
	 * @var DownloadInvoiceQuery
	 */
	public $DownloadInvoiceQuery;
}}

if (!class_exists("DownloadInvoicesRequest")) {
/**
 * DownloadInvoicesRequest
 */
class DownloadInvoicesRequest extends RequestBase {
	/**
	 * @access public
	 * @var DownloadInvoicesQuery
	 */
	public $DownloadInvoicesQuery;
}}

if (!class_exists("GetInvoiceDataRequest")) {
/**
 * GetInvoiceDataRequest
 */
class GetInvoiceDataRequest extends RequestBase {
	/**
	 * @access public
	 * @var GetInvoiceDataQuery
	 */
	public $GetInvoiceDataQuery;
}}

if (!class_exists("ResponseBase")) {
/**
 * ResponseBase
 */
class ResponseBase {
	/**
	 * @access public
	 * @var string
	 */
	public $RequestId;
	/**
	 * @access public
	 * @var ResultCode
	 */
	public $RequestResult;
}}

if (!class_exists("TransactionBase")) {
/**
 * TransactionBase
 */
class TransactionBase {
	/**
	 * @access public
	 * @var string
	 */
	public $TransactionId;
}}

if (!class_exists("CreateInvoiceTransaction")) {
/**
 * CreateInvoiceTransaction
 */
class CreateInvoiceTransaction extends TransactionBase {
	/**
	 * @access public
	 * @var InvoiceHeaderBase
	 */
	public $Header;
	/**
	 * @access public
	 * @var ArrayOfInvoiceLine
	 */
	public $Lines;
	/**
	 * @access public
	 * @var boolean
	 */
	public $SendInvoiceToCustomer;
	/**
	 * @access public
	 * @var string
	 */
	public $SendInvoiceToEmailAddress;
	/**
	 * @access public
	 * @var integer
	 */
	public $InvoiceCourierTypeId;
	/**
	 * @access public
	 * @var ArrayOfClauseItem
	 */
	public $Clauses;
}}

if (!class_exists("InvoiceHeaderBase")) {
/**
 * InvoiceHeaderBase
 */
class InvoiceHeaderBase {
}}

if (!class_exists("InvoiceHeader")) {
/**
 * InvoiceHeader
 */
class InvoiceHeader extends InvoiceHeaderBase {
	/**
	 * @access public
	 * @var string
	 */
	public $AccountBlockPrefix;
	/**
	 * @access public
	 * @var BankBase
	 */
	public $Bank;
	/**
	 * @access public
	 * @var IssuerAddressBase
	 */
	public $IssuerAddress;
	/**
	 * @access public
	 * @var CustomerBase
	 */
	public $Customer;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $FulfillmentDate;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PaymentDueDate;
	/**
	 * @access public
	 * @var CurrencyBase
	 */
	public $Currency;
	/**
	 * @access public
	 * @var boolean
	 */
	public $InterEUVatExempt;
	/**
	 * @access public
	 * @var integer
	 */
	public $PaymentMethodId;
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceDescription;
	/**
	 * @access public
	 * @var string
	 */
	public $Notes;
	/**
	 * @access public
	 * @var string
	 */
	public $OrderNumber;
	/**
	 * @access public
	 * @var double
	 */
	public $LocalForeignCurrencyExchangeRate;
	/**
	 * @access public
	 * @var boolean
	 */
	public $IsDomesticDelivery;
	/**
	 * @access public
	 * @var boolean
	 */
	public $IsVatReasonAccepted;
	/**
	 * @access public
	 * @var integer
	 */
	public $InvoiceDocumentType;
	/**
	 * @access public
	 * @var string
	 */
	public $ReferenceInvoiceNumber;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $ReferenceInvoiceFulfillmentDate;
	/**
	 * @access public
	 * @var boolean
	 */
	public $InvoiceHasElectronicServiceInEU;
	/**
	 * @access public
	 * @var boolean
	 */
	public $ForceLocalForeignCurrencyExchangeRate;
}}

if (!class_exists("BankBase")) {
/**
 * BankBase
 */
class BankBase {
}}

if (!class_exists("DefaultBank")) {
/**
 * DefaultBank
 */
class DefaultBank extends BankBase {
	/**
	 * @access public
	 * @var string
	 */
	public $BankDescription;
}}

if (!class_exists("BankIdentifier")) {
/**
 * BankIdentifier
 */
class BankIdentifier extends BankBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Identifier;
	/**
	 * @access public
	 * @var string
	 */
	public $BankDescription;
}}

if (!class_exists("IssuerAddressBase")) {
/**
 * IssuerAddressBase
 */
class IssuerAddressBase {
}}

if (!class_exists("IssuerAddressIdentifier")) {
/**
 * IssuerAddressIdentifier
 */
class IssuerAddressIdentifier extends IssuerAddressBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Identifier;
}}

if (!class_exists("DefaultIssuerAddress")) {
/**
 * DefaultIssuerAddress
 */
class DefaultIssuerAddress extends IssuerAddressBase {
}}

if (!class_exists("CustomerBase")) {
/**
 * CustomerBase
 */
class CustomerBase {
}}

if (!class_exists("Customer")) {
/**
 * Customer
 */
class Customer extends CustomerBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Name;
	/**
	 * @access public
	 * @var string
	 */
	public $CustomerIdentifier;
	/**
	 * @access public
	 * @var string
	 */
	public $EUTaxNumber;
	/**
	 * @access public
	 * @var string
	 */
	public $TaxNumber;
	/**
	 * @access public
	 * @var string
	 */
	public $AddressPostalCode;
	/**
	 * @access public
	 * @var string
	 */
	public $AddressCity;
	/**
	 * @access public
	 * @var string
	 */
	public $AddressCountryId;
	/**
	 * @access public
	 * @var string
	 */
	public $AddressLine1;
	/**
	 * @access public
	 * @var string
	 */
	public $AddressLine2;
	/**
	 * @access public
	 * @var string
	 */
	public $AddressState;
	/**
	 * @access public
	 * @var string
	 */
	public $GroupIdentificationNumber;
}}

if (!class_exists("CustomerIdentifier")) {
/**
 * CustomerIdentifier
 */
class CustomerIdentifier extends CustomerBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Identifier;
}}

if (!class_exists("CurrencyBase")) {
/**
 * CurrencyBase
 */
class CurrencyBase {
}}

if (!class_exists("DefaultCurrency")) {
/**
 * DefaultCurrency
 */
class DefaultCurrency extends CurrencyBase {
}}

if (!class_exists("CurrencyShortName")) {
/**
 * CurrencyShortName
 */
class CurrencyShortName extends CurrencyBase {
	/**
	 * @access public
	 * @var string
	 */
	public $ShortName;
}}

if (!class_exists("InvoiceHeaderWithTotalAmount")) {
/**
 * InvoiceHeaderWithTotalAmount
 */
class InvoiceHeaderWithTotalAmount extends InvoiceHeader {
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceNumber;
	/**
	 * @access public
	 * @var double
	 */
	public $InvoiceNetTotalAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $InvoiceVatTotalAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $InvoiceGrossTotalAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $InvoiceLocalVatTotalAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $InvoiceLocalGrossTotalAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $InvoiceLocalNetTotalAmount;
}}

if (!class_exists("InvoiceLineBase")) {
/**
 * InvoiceLineBase
 */
class InvoiceLineBase {
}}

if (!class_exists("InvoiceLine")) {
/**
 * InvoiceLine
 */
class InvoiceLine extends InvoiceLineBase {
	/**
	 * @access public
	 * @var string
	 */
	public $ProductStatisticalCode;
	/**
	 * @access public
	 * @var string
	 */
	public $ProductName;
	/**
	 * @access public
	 * @var double
	 */
	public $NetUnitPrice;
	/**
	 * @access public
	 * @var double
	 */
	public $Quantity;
	/**
	 * @access public
	 * @var double
	 */
	public $VatPercentage;
	/**
	 * @access public
	 * @var string
	 */
	public $UnitIdentifier;
	/**
	 * @access public
	 * @var string
	 */
	public $ProductTextIdentifier;
	/**
	 * @access public
	 * @var double
	 */
	public $GrossUnitPrice;
	/**
	 * @access public
	 * @var boolean
	 */
	public $IsPeriodic;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodStartDate;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodEndDate;
	/**
	 * @access public
	 * @var ArrayOfClauseItem
	 */
	public $Clauses;
}}

if (!class_exists("ClauseItem")) {
/**
 * ClauseItem
 */
class ClauseItem {
	/**
	 * @access public
	 * @var string
	 */
	public $Id;
	/**
	 * @access public
	 * @var ArrayOfClauseParameterItem
	 */
	public $Parameters;
}}

if (!class_exists("ClauseParameterItemBase")) {
/**
 * ClauseParameterItemBase
 */
class ClauseParameterItemBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Name;
}}

if (!class_exists("ClauseStringParameterItem")) {
/**
 * ClauseStringParameterItem
 */
class ClauseStringParameterItem extends ClauseParameterItemBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Value;
}}

if (!class_exists("ClauseDateParameterItem")) {
/**
 * ClauseDateParameterItem
 */
class ClauseDateParameterItem extends ClauseParameterItemBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Value;
}}

if (!class_exists("ClauseBoolParameterItem")) {
/**
 * ClauseBoolParameterItem
 */
class ClauseBoolParameterItem extends ClauseParameterItemBase {
	/**
	 * @access public
	 * @var boolean
	 */
	public $Value;
}}

if (!class_exists("ClauseIntParameterItem")) {
/**
 * ClauseIntParameterItem
 */
class ClauseIntParameterItem extends ClauseParameterItemBase {
	/**
	 * @access public
	 * @var integer
	 */
	public $Value;
}}

if (!class_exists("ClauseDecimalParameterItem")) {
/**
 * ClauseDecimalParameterItem
 */
class ClauseDecimalParameterItem extends ClauseParameterItemBase {
	/**
	 * @access public
	 * @var double
	 */
	public $Value;
}}

if (!class_exists("InvoiceLine2")) {
/**
 * InvoiceLine2
 */
class InvoiceLine2 extends InvoiceLineBase {
	/**
	 * @access public
	 * @var string
	 */
	public $ProductStatisticalCode;
	/**
	 * @access public
	 * @var string
	 */
	public $ProductName;
	/**
	 * @access public
	 * @var double
	 */
	public $NetUnitPrice;
	/**
	 * @access public
	 * @var double
	 */
	public $Quantity;
	/**
	 * @access public
	 * @var string
	 */
	public $VatTaxRateCode;
	/**
	 * @access public
	 * @var string
	 */
	public $UnitIdentifier;
	/**
	 * @access public
	 * @var string
	 */
	public $ProductTextIdentifier;
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceLineNote;
	/**
	 * @access public
	 * @var double
	 */
	public $GrossUnitPrice;
	/**
	 * @access public
	 * @var boolean
	 */
	public $IsPeriodic;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodStartDate;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodEndDate;
	/**
	 * @access public
	 * @var ArrayOfClauseItem
	 */
	public $Clauses;
}}

if (!class_exists("InvoiceLineWithAmount")) {
/**
 * InvoiceLineWithAmount
 */
class InvoiceLineWithAmount extends InvoiceLine2 {
	/**
	 * @access public
	 * @var double
	 */
	public $NetAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $VatAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $GrossAmount;
}}

if (!class_exists("InvoiceLineIdentifier")) {
/**
 * InvoiceLineIdentifier
 */
class InvoiceLineIdentifier extends InvoiceLineBase {
	/**
	 * @access public
	 * @var string
	 */
	public $Identifier;
	/**
	 * @access public
	 * @var double
	 */
	public $Quantity;
	/**
	 * @access public
	 * @var boolean
	 */
	public $IsPeriodic;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodStartDate;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodEndDate;
	/**
	 * @access public
	 * @var ArrayOfClauseItem
	 */
	public $Clauses;
}}

if (!class_exists("InvoiceVatGroup")) {
/**
 * InvoiceVatGroup
 */
class InvoiceVatGroup {
	/**
	 * @access public
	 * @var string
	 */
	public $VatTaxRateCode;
	/**
	 * @access public
	 * @var double
	 */
	public $VatAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $NetAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $GrossAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $LocalVatAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $LocalGrossAmount;
	/**
	 * @access public
	 * @var double
	 */
	public $LocalNetAmount;
}}

if (!class_exists("QueryBase")) {
/**
 * QueryBase
 */
class QueryBase {
}}

if (!class_exists("DownloadInvoicesQuery")) {
/**
 * DownloadInvoicesQuery
 */
class DownloadInvoicesQuery extends QueryBase {
	/**
	 * @access public
	 * @var boolean
	 */
	public $CompressResult;
	/**
	 * @access public
	 * @var DownloadInvoicesFilterBase
	 */
	public $FilterSpecification;
}}

if (!class_exists("DownloadInvoicesFilterBase")) {
/**
 * DownloadInvoicesFilterBase
 */
class DownloadInvoicesFilterBase {
}}

if (!class_exists("DownloadInvoicesPeriodFilter")) {
/**
 * DownloadInvoicesPeriodFilter
 */
class DownloadInvoicesPeriodFilter extends DownloadInvoicesFilterBase {
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodStart;
	/**
	 * @access public
	 * @var dateTime
	 */
	public $PeriodEnd;
}}

if (!class_exists("GetInvoiceDataQuery")) {
/**
 * GetInvoiceDataQuery
 */
class GetInvoiceDataQuery extends QueryBase {
}}

if (!class_exists("ResultCode")) {
/**
 * ResultCode
 */
class ResultCode {
	/**
	 * @access public
	 * @var string
	 */
	public $Code;
	/**
	 * @access public
	 * @var boolean
	 */
	public $IsTransient;
}}

if (!class_exists("TransactionResult")) {
/**
 * TransactionResult
 */
class TransactionResult {
	/**
	 * @access public
	 * @var string
	 */
	public $TransactionId;
	/**
	 * @access public
	 * @var ResultCode
	 */
	public $ResultCode;
	/**
	 * @access public
	 * @var boolean
	 */
	public $RepeatedTransaction;
}}

if (!class_exists("CreateInvoiceTransactionResult")) {
/**
 * CreateInvoiceTransactionResult
 */
class CreateInvoiceTransactionResult extends TransactionResult {
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceNumber;
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceCourierUrl;
}}

if (!class_exists("QueryResultBase")) {
/**
 * QueryResultBase
 */
class QueryResultBase {
	/**
	 * @access public
	 * @var ResultCode
	 */
	public $ResultCode;
}}

if (!class_exists("GetInvoiceDataQueryResult")) {
/**
 * GetInvoiceDataQueryResult
 */
class GetInvoiceDataQueryResult extends QueryResultBase {
	/**
	 * @access public
	 * @var ArrayOfUnitsQueryResultItem
	 */
	public $Units;
	/**
	 * @access public
	 * @var ArrayOfAccountBlocksQueryResultItem
	 */
	public $AccountBlocks;
	/**
	 * @access public
	 * @var ArrayOfPaymentMethodsQueryResultItem
	 */
	public $PaymentMethods;
}}

if (!class_exists("UnitsQueryResultItem")) {
/**
 * UnitsQueryResultItem
 */
class UnitsQueryResultItem {
	/**
	 * @access public
	 * @var string
	 */
	public $UnitIdentifier;
	/**
	 * @access public
	 * @var string
	 */
	public $Name;
}}

if (!class_exists("AccountBlocksQueryResultItem")) {
/**
 * AccountBlocksQueryResultItem
 */
class AccountBlocksQueryResultItem {
	/**
	 * @access public
	 * @var string
	 */
	public $AccountBlockPrefix;
	/**
	 * @access public
	 * @var string
	 */
	public $CurrencyShortName;
}}

if (!class_exists("PaymentMethodsQueryResultItem")) {
/**
 * PaymentMethodsQueryResultItem
 */
class PaymentMethodsQueryResultItem {
	/**
	 * @access public
	 * @var string
	 */
	public $BankIdentifier;
	/**
	 * @access public
	 * @var string
	 */
	public $PaymentMeansType;
	/**
	 * @access public
	 * @var string
	 */
	public $BankAccountNumber;
}}

if (!class_exists("DownloadInvoicesQueryResult")) {
/**
 * DownloadInvoicesQueryResult
 */
class DownloadInvoicesQueryResult extends QueryResultBase {
	/**
	 * @access public
	 * @var ArrayOfDownloadInvoicesQueryResultItem
	 */
	public $Invoices;
}}

if (!class_exists("DownloadInvoicesQueryResultItem")) {
/**
 * DownloadInvoicesQueryResultItem
 */
class DownloadInvoicesQueryResultItem {
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceNumber;
	/**
	 * @access public
	 * @var base64Binary
	 */
	public $InvoiceDocument;
}}

if (!class_exists("char")) {
/**
 * char
 */
class char {
}}

if (!class_exists("duration")) {
/**
 * duration
 */
class duration {
}}

if (!class_exists("guid")) {
/**
 * guid
 */
class guid {
}}

if (!class_exists("CreateInvoiceResponse")) {
/**
 * CreateInvoiceResponse
 */
class CreateInvoiceResponse extends ResponseBase {
	/**
	 * @access public
	 * @var CreateInvoiceTransactionResult
	 */
	public $TransactionResult;
}}

if (!class_exists("ConvertExternalInvoiceResponse")) {
/**
 * ConvertExternalInvoiceResponse
 */
class ConvertExternalInvoiceResponse extends ResponseBase {
	/**
	 * @access public
	 * @var ConvertExternalInvoiceTransactionResult
	 */
	public $TransactionResult;
}}

if (!class_exists("DownloadInvoiceResponse")) {
/**
 * DownloadInvoiceResponse
 */
class DownloadInvoiceResponse extends ResponseBase {
	/**
	 * @access public
	 * @var DownloadInvoiceQueryResult
	 */
	public $QueryResult;
}}

if (!class_exists("DownloadInvoicesResponse")) {
/**
 * DownloadInvoicesResponse
 */
class DownloadInvoicesResponse extends ResponseBase {
	/**
	 * @access public
	 * @var DownloadInvoicesQueryResult
	 */
	public $QueryResult;
}}

if (!class_exists("GetInvoiceDataResponse")) {
/**
 * GetInvoiceDataResponse
 */
class GetInvoiceDataResponse extends ResponseBase {
	/**
	 * @access public
	 * @var GetInvoiceDataQueryResult
	 */
	public $QueryResult;
}}

if (!class_exists("CreateInvoiceRequest")) {
/**
 * CreateInvoiceRequest
 */
class CreateInvoiceRequest extends RequestBase {
	/**
	 * @access public
	 * @var CreateInvoiceTransaction
	 */
	public $CreateInvoiceTransaction;
}}

if (!class_exists("ConvertExternalInvoiceTransaction")) {
/**
 * ConvertExternalInvoiceTransaction
 */
class ConvertExternalInvoiceTransaction extends TransactionBase {
	/**
	 * @access public
	 * @var InvoiceHeaderWithTotalAmount
	 */
	public $Header;
	/**
	 * @access public
	 * @var ArrayOfInvoiceLineWithAmount
	 */
	public $Lines;
	/**
	 * @access public
	 * @var ArrayOfInvoiceVatGroup
	 */
	public $VatGroups;
	/**
	 * @access public
	 * @var boolean
	 */
	public $SendInvoiceToCustomer;
	/**
	 * @access public
	 * @var string
	 */
	public $SendInvoiceToEmailAddress;
	/**
	 * @access public
	 * @var integer
	 */
	public $InvoiceCourierTypeId;
	/**
	 * @access public
	 * @var ArrayOfClauseItem
	 */
	public $Clauses;
}}

if (!class_exists("DownloadInvoiceQuery")) {
/**
 * DownloadInvoiceQuery
 */
class DownloadInvoiceQuery extends QueryBase {
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceNumber;
}}

if (!class_exists("ConvertExternalInvoiceTransactionResult")) {
/**
 * ConvertExternalInvoiceTransactionResult
 */
class ConvertExternalInvoiceTransactionResult extends TransactionResult {
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceCourierUrl;
}}

if (!class_exists("DownloadInvoiceQueryResult")) {
/**
 * DownloadInvoiceQueryResult
 */
class DownloadInvoiceQueryResult extends QueryResultBase {
	/**
	 * @access public
	 * @var string
	 */
	public $InvoiceNumber;
	/**
	 * @access public
	 * @var base64Binary
	 */
	public $InvoiceDocument;
}}

if (!class_exists("InvoicingService")) {
/**
 * InvoicingService
 * @author WSDLInterpreter
 */
class InvoicingService extends SoapClient {
	/**
	 * Default class map for wsdl=>php
	 * @access private
	 * @var array
	 */
	private static $classmap = array(
		"CreateInvoice" => "CreateInvoice",
		"CreateInvoiceResponse" => "CreateInvoiceResponse",
		"ConvertExternalInvoice" => "ConvertExternalInvoice",
		"ConvertExternalInvoiceResponse" => "ConvertExternalInvoiceResponse",
		"DownloadInvoice" => "DownloadInvoice",
		"DownloadInvoiceResponse" => "DownloadInvoiceResponse",
		"DownloadInvoices" => "DownloadInvoices",
		"DownloadInvoicesResponse" => "DownloadInvoicesResponse",
		"GetInvoiceData" => "GetInvoiceData",
		"GetInvoiceDataResponse" => "GetInvoiceDataResponse",
		"CreateInvoiceRequest" => "CreateInvoiceRequest",
		"RequestBase" => "RequestBase",
		"ConvertExternalInvoiceRequest" => "ConvertExternalInvoiceRequest",
		"DownloadInvoiceRequest" => "DownloadInvoiceRequest",
		"DownloadInvoicesRequest" => "DownloadInvoicesRequest",
		"GetInvoiceDataRequest" => "GetInvoiceDataRequest",
		"ResponseBase" => "ResponseBase",
		"ConvertExternalInvoiceTransaction" => "ConvertExternalInvoiceTransaction",
		"TransactionBase" => "TransactionBase",
		"CreateInvoiceTransaction" => "CreateInvoiceTransaction",
		"InvoiceHeaderBase" => "InvoiceHeaderBase",
		"InvoiceHeader" => "InvoiceHeader",
		"BankBase" => "BankBase",
		"DefaultBank" => "DefaultBank",
		"BankIdentifier" => "BankIdentifier",
		"IssuerAddressBase" => "IssuerAddressBase",
		"IssuerAddressIdentifier" => "IssuerAddressIdentifier",
		"DefaultIssuerAddress" => "DefaultIssuerAddress",
		"CustomerBase" => "CustomerBase",
		"Customer" => "Customer",
		"CustomerIdentifier" => "CustomerIdentifier",
		"CurrencyBase" => "CurrencyBase",
		"DefaultCurrency" => "DefaultCurrency",
		"CurrencyShortName" => "CurrencyShortName",
		"InvoiceHeaderWithTotalAmount" => "InvoiceHeaderWithTotalAmount",
		"InvoiceLineBase" => "InvoiceLineBase",
		"InvoiceLine" => "InvoiceLine",
		"ClauseItem" => "ClauseItem",
		"ClauseParameterItemBase" => "ClauseParameterItemBase",
		"ClauseStringParameterItem" => "ClauseStringParameterItem",
		"ClauseDateParameterItem" => "ClauseDateParameterItem",
		"ClauseBoolParameterItem" => "ClauseBoolParameterItem",
		"ClauseIntParameterItem" => "ClauseIntParameterItem",
		"ClauseDecimalParameterItem" => "ClauseDecimalParameterItem",
		"InvoiceLine2" => "InvoiceLine2",
		"InvoiceLineWithAmount" => "InvoiceLineWithAmount",
		"InvoiceLineIdentifier" => "InvoiceLineIdentifier",
		"InvoiceVatGroup" => "InvoiceVatGroup",
		"DownloadInvoiceQuery" => "DownloadInvoiceQuery",
		"QueryBase" => "QueryBase",
		"DownloadInvoicesQuery" => "DownloadInvoicesQuery",
		"DownloadInvoicesFilterBase" => "DownloadInvoicesFilterBase",
		"DownloadInvoicesPeriodFilter" => "DownloadInvoicesPeriodFilter",
		"GetInvoiceDataQuery" => "GetInvoiceDataQuery",
		"ResultCode" => "ResultCode",
		"ConvertExternalInvoiceTransactionResult" => "ConvertExternalInvoiceTransactionResult",
		"TransactionResult" => "TransactionResult",
		"CreateInvoiceTransactionResult" => "CreateInvoiceTransactionResult",
		"DownloadInvoiceQueryResult" => "DownloadInvoiceQueryResult",
		"QueryResultBase" => "QueryResultBase",
		"GetInvoiceDataQueryResult" => "GetInvoiceDataQueryResult",
		"UnitsQueryResultItem" => "UnitsQueryResultItem",
		"AccountBlocksQueryResultItem" => "AccountBlocksQueryResultItem",
		"PaymentMethodsQueryResultItem" => "PaymentMethodsQueryResultItem",
		"DownloadInvoicesQueryResult" => "DownloadInvoicesQueryResult",
		"DownloadInvoicesQueryResultItem" => "DownloadInvoicesQueryResultItem",
		"char" => "char",
		"duration" => "duration",
		"guid" => "guid",
	);

	/**
	 * Constructor using wsdl location and options array
	 * @param string $wsdl WSDL location for this service
	 * @param array $options Options for the SoapClient
	 */
	public function __construct($wsdl="https://billzone.eu/billgate?wsdl", $options=array()) {
		foreach(self::$classmap as $wsdlClassName => $phpClassName) {
		    if(!isset($options['classmap'][$wsdlClassName])) {
		        $options['classmap'][$wsdlClassName] = $phpClassName;
		    }
		}
		parent::__construct($wsdl, $options);
	}

	/**
	 * Checks if an argument list matches against a valid argument type list
	 * @param array $arguments The argument list to check
	 * @param array $validParameters A list of valid argument types
	 * @return boolean true if arguments match against validParameters
	 * @throws Exception invalid function signature message
	 */
	public function _checkArguments($arguments, $validParameters) {
		$variables = "";
		foreach ($arguments as $arg) {
		    $type = gettype($arg);
		    if ($type == "object") {
		        $type = get_class($arg);
		    }
		    $variables .= "(".$type.")";
		}
		if (!in_array($variables, $validParameters)) {
		    throw new Exception("Invalid parameter types: ".str_replace(")(", ", ", $variables));
		}
		return true;
	}

	/**
	 * Service Call: CreateInvoice
	 * Parameter options:
	 * (CreateInvoice) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CreateInvoiceResponse
	 * @throws Exception invalid function signature message
	 */
	public function CreateInvoice($mixed = null) {
		$validParameters = array(
			"(CreateInvoice)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CreateInvoice", $args);
	}


	/**
	 * Service Call: ConvertExternalInvoice
	 * Parameter options:
	 * (ConvertExternalInvoice) parameters
	 * @param mixed,... See function description for parameter options
	 * @return ConvertExternalInvoiceResponse
	 * @throws Exception invalid function signature message
	 */
	public function ConvertExternalInvoice($mixed = null) {
		$validParameters = array(
			"(ConvertExternalInvoice)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("ConvertExternalInvoice", $args);
	}


	/**
	 * Service Call: DownloadInvoice
	 * Parameter options:
	 * (DownloadInvoice) parameters
	 * @param mixed,... See function description for parameter options
	 * @return DownloadInvoiceResponse
	 * @throws Exception invalid function signature message
	 */
	public function DownloadInvoice($mixed = null) {
		$validParameters = array(
			"(DownloadInvoice)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("DownloadInvoice", $args);
	}


	/**
	 * Service Call: DownloadInvoices
	 * Parameter options:
	 * (DownloadInvoices) parameters
	 * @param mixed,... See function description for parameter options
	 * @return DownloadInvoicesResponse
	 * @throws Exception invalid function signature message
	 */
	public function DownloadInvoices($mixed = null) {
		$validParameters = array(
			"(DownloadInvoices)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("DownloadInvoices", $args);
	}


	/**
	 * Service Call: GetInvoiceData
	 * Parameter options:
	 * (GetInvoiceData) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetInvoiceDataResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetInvoiceData($mixed = null) {
		$validParameters = array(
			"(GetInvoiceData)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetInvoiceData", $args);
	}


}}

?>