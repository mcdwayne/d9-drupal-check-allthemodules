<?php
// Törzsadatok letöltése

	//Betöltjük a generált InvoicingService.php-t
	include 'InvoicingService.php';

	//InvoicingService objektum létrehozása
	$InvoicingService = new InvoicingService();

	
	//GetInvoiceDataQuery objektum létrehozása és feltöltése
	$GetInvoiceDataQuery = new GetInvoiceDataQuery();
	

	
	//GetInvoiceDataRequest objektum létrehozása és feltöltése
	$GetInvoiceDataRequest = new GetInvoiceDataRequest();
	$GetInvoiceDataRequest -> GetInvoiceDataQuery = $GetInvoiceDataQuery;
	$GetInvoiceDataRequest -> RequestId = '22266';
	$GetInvoiceDataRequest -> SecurityToken = '9ICOPE3QYHT4LS3JM1ECZRJF47NLGN3GSLJ2R2WE';	
	
	//GetInvoiceData objektum létrehozása és feltöltése
	$GetInvoiceData = new GetInvoiceData();
	$GetInvoiceData -> request = $GetInvoiceDataRequest;		

	
	//GetInvoiceData függvény meghívása az előbbiekben beállított értékekkel
	$GetInvoiceDataResponse = $InvoicingService -> GetInvoiceData($GetInvoiceData);
	
	//GetInvoiceData értéke a válaszból
	print_r ($GetInvoiceDataResponse -> GetInvoiceDataResult -> QueryResult -> Units);
	echo "<br>";
	print_r ($GetInvoiceDataResponse -> GetInvoiceDataResult -> QueryResult -> AccountBlocks);
	echo "<br>";
	print_r ($GetInvoiceDataResponse -> GetInvoiceDataResult -> QueryResult -> PaymentMethods);	
?>


