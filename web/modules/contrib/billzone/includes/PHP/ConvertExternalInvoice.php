<?php
// ConvertExternalInvoice meghívása


//Betöltjük a generált InvoicingService.php-t
require_once 'InvoicingService.php';

//InvoicingService objektum létrehozása
$InvoicingService = new InvoicingService();

//Customer objektum létrehozása és feltöltése
$Customer = new Customer();
$Customer -> Name = 'Polisz Bt.';
$Customer -> CustomerIdentifier = '12312123sdf';
$Customer -> EUTaxNumber = 'HU33441234';
$Customer -> TaxNumber = '12345678-1-12';
$Customer -> AddressPostalCode = '1131';
$Customer -> AddressCity = 'Budapest';
$Customer -> AddressCountryId = 'HU';
$Customer -> AddressLine1 = 'Teve utca 1';
$Customer -> AddressLine2 = 'B épület, 12. em.';
$Customer -> AddressState = 'Budapest';


//InvoiceLineWithAmount objektum létrehozása és feltöltése
$InvoiceLineWithAmount = new InvoiceLineWithAmount();
$InvoiceLineWithAmount -> ProductName = 'Teszt Termék';
$InvoiceLineWithAmount -> NetUnitPrice = '1000';
$InvoiceLineWithAmount -> Quantity = '1';
$InvoiceLineWithAmount -> VatTaxRateCode = '27';
$InvoiceLineWithAmount -> UnitIdentifier = 'DARAB';
$InvoiceLineWithAmount -> ProductTextIdentifier = 'TT-1';
$InvoiceLineWithAmount -> InvoiceLineNote = 'Teszt megjegyzés';
$InvoiceLineWithAmount -> GrossUnitPrice = '1270'; //A GrossUnitPrice használata opcionális
$InvoiceLineWithAmount -> NetAmount = 1000;
$InvoiceLineWithAmount -> VatAmount = 270;
$InvoiceLineWithAmount -> GrossAmount = 1270;
$InvoiceLineWithAmount -> FulfillmentBeginDate = date('2014-06-26');
$InvoiceLineWithAmount -> FulfillmentEndDate = date('2014-06-30'); //A FulfillmentBeginDate és FulfillmentEndDate használata opcionális


$InvoiceVatGroup = new InvoiceVatGroup();
$InvoiceVatGroup -> NetAmount = 1000;
$InvoiceVatGroup -> VatAmount = 270;
$InvoiceVatGroup -> GrossAmount = 1270;
$InvoiceVatGroup ->VatTaxRateCode = '27';

$Bank = new BankIdentifier();
$Bank -> Identifier = 'TESTBANK01_ATUTALAS';

//InvoiceHeader objektum létrehozása és feltöltése
$InvoiceHeaderWithTotalAmount = new InvoiceHeaderWithTotalAmount();
$InvoiceHeaderWithTotalAmount -> AccountBlockPrefix = 'UNMT';
$InvoiceHeaderWithTotalAmount -> Bank = $Bank;
$InvoiceHeaderWithTotalAmount -> IssuerAddress = new DefaultIssuerAddress();
$InvoiceHeaderWithTotalAmount -> Customer = $Customer;
$InvoiceHeaderWithTotalAmount -> FulfillmentDate = date("Y-m-d",time());
$InvoiceHeaderWithTotalAmount -> PaymentDueDate = date("Y-m-d",time());
$InvoiceHeaderWithTotalAmount -> Currency = new DefaultCurrency();
$InvoiceHeaderWithTotalAmount -> InterEUVatExempt = 0;

$InvoiceHeaderWithTotalAmount -> InvoiceDescription = 'Invoice description';
$InvoiceHeaderWithTotalAmount -> Notes = 'Notes';
$InvoiceHeaderWithTotalAmount -> OrderNumber = 'ORDER012';
$InvoiceHeaderWithTotalAmount -> InvoiceVatTotalAmount = 270;
$InvoiceHeaderWithTotalAmount -> InvoiceGrossTotalAmount = 1270;
$InvoiceHeaderWithTotalAmount -> InvoiceNetTotalAmount = 1000;
$InvoiceHeaderWithTotalAmount -> InvoiceNumber = 'XX0001';

//ConvertExternalInvoiceTransaction objektum létrehozása és feltöltése
$ConvertExternalInvoiceTransaction = new ConvertExternalInvoiceTransaction();
$ConvertExternalInvoiceTransaction -> TransactionId = '8099144c-56d0-4c79-8cda-794b3fef3ce5';
$ConvertExternalInvoiceTransaction -> Header = $InvoiceHeaderWithTotalAmount;
$ConvertExternalInvoiceTransaction -> Lines = array($InvoiceLineWithAmount);
$ConvertExternalInvoiceTransaction -> VatGroups = array($InvoiceVatGroup);
$ConvertExternalInvoiceTransaction -> SendInvoiceToCustomer =0;


//ConvertExternalInvoiceRequest objektum létrehozása és feltöltése
$ConvertExternalInvoiceRequest = new ConvertExternalInvoiceRequest();
$ConvertExternalInvoiceRequest -> ConvertExternalInvoiceTransaction = $ConvertExternalInvoiceTransaction; 
$ConvertExternalInvoiceRequest -> RequestId = '49992';
$ConvertExternalInvoiceRequest -> SecurityToken = '9ICOPE3QYHT4LS3JM1ECZRJF47NLGN3GSLJ2R2WE';

//ConvertExternalInvoice objektum létrehozása és feltöltése
$ConvertExternalInvoice = new ConvertExternalInvoice();
$ConvertExternalInvoice -> request = $ConvertExternalInvoiceRequest; 

//ConvertExternalInvoiceResponse függvény meghívása az előbbiekben beállított értékekkel
$ConvertExternalInvoiceResponse = new ConvertExternalInvoiceResponse();
$ConvertExternalInvoiceResponse = $InvoicingService -> ConvertExternalInvoice($ConvertExternalInvoice);	


	
	
	
	//
	//Invoice letöltése
	//
	
	//InvoiceNumber értéke, az előzőekben megadott
	$InvoiceNumber = 'XX0001';	
	
	//DownloadInvoiceQuery objektum létrehozása és feltöltése
	$DownloadInvoiceQuery = new DownloadInvoiceQuery();
	$DownloadInvoiceQuery -> InvoiceNumber	= $InvoiceNumber;	
	
	//DownloadInvoiceRequest objektum létrehozása és feltöltése
	$DownloadInvoiceRequest = new DownloadInvoiceRequest();
	$DownloadInvoiceRequest -> DownloadInvoiceQuery = $DownloadInvoiceQuery;
	$DownloadInvoiceRequest -> RequestId = '222';
	$DownloadInvoiceRequest -> SecurityToken = '9ICOPE3QYHT4LS3JM1ECZRJF47NLGN3GSLJ2R2WE';	
	
	//DownloadInvoice objektum létrehozása és feltöltése
	$DownloadInvoice = new DownloadInvoice();
	$DownloadInvoice -> request = $DownloadInvoiceRequest;		
	
	//DownloadInvoice függvény meghívása az előbbiekben beállított értékekkel
	$DownloadInvoiceResponse = $InvoicingService -> DownloadInvoice($DownloadInvoice);
	
	//InvoiceDocument értéke a válaszból
	$InvoiceDocument = $DownloadInvoiceResponse -> DownloadInvoiceResult -> QueryResult -> InvoiceDocument;
		
	//InvoiceDocument fájlba mentese
	$myInvoice = "myInvoice.pdf";
	$fh = fopen($myInvoice, 'w');
	fwrite($fh, $InvoiceDocument);
	fclose($fh);
?>


