<?php
// Egy sadott periódusban kiállított számlák letöltése


	//Betöltjük a generált InvoicingService.php-t
	require_once 'InvoicingService.php';

	//InvoicingService objektum létrehozása
	$InvoicingService = new InvoicingService();

	
	//$DownloadInvoicesFilterBase szűrő objektum létrehozása
	$DownloadInvoicesFilterBase	= new DownloadInvoicesPeriodFilter();
	$DownloadInvoicesFilterBase -> PeriodStart = date('2014-01-01');
	$DownloadInvoicesFilterBase -> PeriodEnd = date('2014-12-31');

	//DownloadInvoicesQuery objektum létrehozása és feltöltése
	$DownloadInvoicesQuery = new DownloadInvoicesQuery();
	$DownloadInvoicesQuery -> CompressResult = false;
	$DownloadInvoicesQuery -> FilterSpecification = $DownloadInvoicesFilterBase;

	//DownloadInvoiceRequest objektum létrehozása és feltöltése
	$DownloadInvoicesRequest = new DownloadInvoicesRequest();
	$DownloadInvoicesRequest -> DownloadInvoicesQuery = $DownloadInvoicesQuery;
	$DownloadInvoicesRequest -> RequestId = '48888';
	$DownloadInvoicesRequest -> SecurityToken = '9ICOPE3QYHT4LS3JM1ECZRJF47NLGN3GSLJ2R2WE';

	//DownloadInvoices objektum létrehozása és feltöltése
	$DownloadInvoices = new DownloadInvoices();
	$DownloadInvoices -> request = $DownloadInvoicesRequest;		

	//DownloadInvoices függvény meghívása az előbbiekben beállított értékekkel
	$DownloadInvoicesResponse = $InvoicingService -> DownloadInvoices($DownloadInvoices);

	//Számlák értéke a válaszból
	foreach ($DownloadInvoicesResponse -> DownloadInvoicesResult-> QueryResult-> Invoices as $value) {
   		foreach($value as $invoices){
			$InvoiceDocument = $invoices -> InvoiceDocument;
			
			//InvoiceDocument fájlba mentése
			$myInvoice = $invoices -> InvoiceNumber.".pdf";
			$fh = fopen($myInvoice, 'w');
			fwrite($fh, $InvoiceDocument);
			fclose($fh);
		}
	}

?>


