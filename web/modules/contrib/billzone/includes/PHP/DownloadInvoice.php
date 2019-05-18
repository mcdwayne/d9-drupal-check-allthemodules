<?php
// Egy számla letöltése


	//Betöltjük a generált InvoicingService.php-t
	require_once 'InvoicingService.php';

	//InvoicingService objektum létrehozása
	$InvoicingService = new InvoicingService();

	
	//InvoiceNumber értéke, az előzőekben megadott
	$InvoiceNumber = 'TEST0001';	
	
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


