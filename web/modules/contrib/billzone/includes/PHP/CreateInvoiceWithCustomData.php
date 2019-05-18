<?php
// Számla létrehozása tetszőleges számlasor adatokkal


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
	

$Bank = new BankIdentifier();
$Bank -> Identifier = 'TESTBANK01_ATUTALAS';

$IssuerAdress = new IssuerAddressIdentifier();
$IssuerAdress -> Identifier = 'TESTTELEPHELY01';

//CurrencyShortName objektum létrehozása és feltöltése
$CurrencyShortName = new CurrencyShortName();
$CurrencyShortName -> ShortName = 'HUF';
	
//InvoiceHeader objektum létrehozása és feltöltése
$InvoiceHeader = new InvoiceHeader();
$InvoiceHeader -> AccountBlockPrefix = 'TEST';
$InvoiceHeader -> Bank = $Bank;
$InvoiceHeader -> IssuerAdress = $IssuerAdress;
$InvoiceHeader -> Currency = $CurrencyShortName;

$InvoiceHeader -> IssuerAddress = $IssuerAdress;
$InvoiceHeader -> Customer = $Customer;
$InvoiceHeader -> FulfillmentDate = date("Y-m-d",time());
$InvoiceHeader -> PaymentDueDate = date("Y-m-d",time());
$InvoiceHeader -> Currency = $CurrencyShortName;
$InvoiceHeader -> InterEUVatExempt = 0;
$InvoiceHeader -> InvoiceDescription = 'Invoice description';
$InvoiceHeader -> Notes = 'Notes';
$InvoiceHeader -> OrderNumber = 'ORD00012';
	
//InvoiceLine2 objektumok létrehozása és feltöltése
$InvoiceLine1 = new InvoiceLine2();
$InvoiceLine1 -> ProductStatisticalCode = '88.69.72';
$InvoiceLine1 -> ProductName = 'Teszt termék 1';
$InvoiceLine1 -> NetUnitPrice = 7990;
$InvoiceLine1 -> Quantity = 27.5;
$InvoiceLine1 -> VatTaxRateCode = '27';
$InvoiceLine1 -> UnitIdentifier = 'T1METER';
$InvoiceLine1 -> ProductTextIdentifier = 'TESZTT123';
$InvoiceLine1 -> FulfillmentBeginDate = date("2014-07-03",time());

$InvoiceLine2 = new InvoiceLine2();
$InvoiceLine2 -> ProductStatisticalCode = '77.69.72';
$InvoiceLine2 -> ProductName = 'Teszt termék 2';
$InvoiceLine2 -> NetUnitPrice = 6000;
$InvoiceLine2 -> Quantity = 1;
$InvoiceLine2 -> VatTaxRateCode = '27';
$InvoiceLine2 -> UnitIdentifier = 'T1METER';
$InvoiceLine2 -> ProductTextIdentifier = 'TESZTT124';
$InvoiceLine2 -> FulfillmentBeginDate = date("2014-07-03",time());


//CreateInvoiceTransaction objektum létrehozása és feltöltése
$CreateInvoiceTransaction = new CreateInvoiceTransaction();
$CreateInvoiceTransaction -> TransactionId = '8099144c-56d0-4c79-8cda-794b3fef3ce5';
$CreateInvoiceTransaction -> Header = $InvoiceHeader;
$CreateInvoiceTransaction -> Lines = array($InvoiceLine1, $InvoiceLine2);
$CreateInvoiceTransaction -> SendInvoiceToCustomer = 0;
	
//CreateInvoiceRequest objektum létrehozása és feltöltése
$CreateInvoiceRequest = new CreateInvoiceRequest();
$CreateInvoiceRequest -> CreateInvoiceTransaction = $CreateInvoiceTransaction; 
$CreateInvoiceRequest -> RequestId = '111';
$CreateInvoiceRequest -> SecurityToken = '9ICOPE3QYHT4LS3JM1ECZRJF47NLGN3GSLJ2R2WE';	
	
//CreateInvoice objektum létrehozása és feltöltése
$CreateInvoice = new CreateInvoice();
$CreateInvoice -> request = $CreateInvoiceRequest; 
	
//CreateInvoice függvény meghívása az előbbiekben beállított értékekkel
$CreateInvoiceResponse = new CreateInvoiceResponse();
$CreateInvoiceResponse = $InvoicingService -> CreateInvoice($CreateInvoice);


	
	
	
	//
	//Invoice letöltése
	//
	
	//InvoiceNumber értéke a válaszból
	$InvoiceNumber = $CreateInvoiceResponse -> CreateInvoiceResult -> TransactionResult -> InvoiceNumber;	
	
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


