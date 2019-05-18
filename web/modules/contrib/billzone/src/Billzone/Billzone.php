<?php

namespace Drupal\billzone\Billzone;

use Drupal\Core\Config\ConfigFactoryInterface;

class Billzone
{
  // Contains the InvoicingService object
  private $invoicing_service;
  // Contains the InvoicingService object
  protected $billzone_settings;

  /**
   * Contructor
   *
   * Load the InvoicingService file and create an object from the InvoicingService class
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {

    // Load the InvoicingService file
    module_load_include('php', 'billzone', 'includes/PHP/InvoicingService');

    // Set WSDL
    $wsdl = 'billzone.eu/billgate?wsdl';
    $mode = $config_factory->get('billzone.settings')->get('mode');

    $params = array();

    if ( $mode == 'sandbox' ) {
      $wsdl = 'sandbox.' . $wsdl;
      $options = array('ssl' => array('verify_peer'=>false, 'verify_peer_name'=>false));
      $params = array ( 'stream_context' => stream_context_create($options) );
    }
    $wsdl = 'https://' . $wsdl;

    // Create InvoicingService Object
    $this->invoicing_service = new \InvoicingService($wsdl, $params);

    $billzone_settings = array(
      'security_token' => $config_factory->get('billzone.settings')->get('security_token'),
    );

    $this->billzone_settings = $billzone_settings;
  }
  
  /**
   * Get Billzone settings
   */
  protected function getBillzoneSettings() {
    return $this->billzone_settings;
  }

  /**
   * Create Customer object from the $customer array parameter
   *
   * @param $customer
   * @return \Customer
   */
  protected function createCustomer($customer) {
    $Customer = new \Customer();
    
    // Name
    if ( isset($customer['name']) ) { $Customer->Name = $customer['name']; }
    // CustomerIdentifier
    if ( isset($customer['customer_identifier']) ) { $Customer->CustomerIdentifier = $customer['customer_identifier']; }
    // EUTaxNumber
    if ( isset($customer['eu_tax_number']) ) { $Customer->EUTaxNumber = $customer['eu_tax_number']; }
    // TaxNumber
    if ( isset($customer['tax_number']) ) { $Customer->TaxNumber = $customer['tax_number']; }
    // AddressPostalCode
    if ( isset($customer['address_postal_code']) ) { $Customer->AddressPostalCode = $customer['address_postal_code']; }
    // AddressCity
    if ( isset($customer['address_city']) ) { $Customer->AddressCity = $customer['address_city']; }
    // AddressCountryId
    if ( isset($customer['address_country_id']) ) { $Customer->AddressCountryId = $customer['address_country_id']; }
    // AddressLine1
    if ( isset($customer['address_line_1']) ) { $Customer->AddressLine1 = $customer['address_line_1']; }
    // AddressLine2
    if ( isset($customer['address_line_2']) ) { $Customer->AddressLine2 = $customer['address_line_2']; }
    // AddressState
    if ( isset($customer['address_state']) ) { $Customer->AddressState = $customer['address_state']; }
    // GroupIdentificationNumber
    if ( isset($customer['group_identification_number']) ) { $Customer->GroupIdentificationNumber = $customer['group_identification_number']; }
    
    return $Customer;
  }

  /**
   * Create line item object
   *
   * @param $line_item
   * @return \InvoiceLine|\InvoiceLine2
   */
  protected function createLineItem($line_item) {

    if ( isset($line_item['vat_percentage']) && is_numeric($line_item['vat_percentage']) ) {
      // InvoiceLine
      $InvoiceLine = new \InvoiceLine();
    } else {
      // InvoiceLine2
      $InvoiceLine = new \InvoiceLine2();
    }

    
    // ProductName
    if ( isset($line_item['product_name']) ) { $InvoiceLine->ProductName = $line_item['product_name']; }
    // NetUnitPrice
    if ( isset($line_item['net_unit_price']) ) { $InvoiceLine->NetUnitPrice = $line_item['net_unit_price']; }
    // Quantity
    if ( isset($line_item['quantity']) ) { $InvoiceLine->Quantity = $line_item['quantity']; }

    if ( isset($line_item['vat_percentage']) && is_numeric($line_item['vat_percentage']) ) {
      // VatPercentage
      $InvoiceLine->VatPercentage = $line_item['vat_percentage'];
    } else {
      // VatTaxRateCode
      $InvoiceLine->VatTaxRateCode = $line_item['vat_percentage'];
    }

    // UnitIdentifier
    if ( isset($line_item['unit_identifier']) ) { $InvoiceLine->UnitIdentifier = $line_item['unit_identifier']; }
    // ProductTextIdentifier
    if ( isset($line_item['product_text_identifier']) ) { $InvoiceLine->ProductTextIdentifier = $line_item['product_text_identifier']; }
    // ProductStatisticalCode
    if ( isset($line_item['product_statistical_code']) ) { $InvoiceLine->ProductStatisticalCode = $line_item['product_statistical_code']; }
    // GrossUnitPrice
    if ( isset($line_item['gross_unit_price']) ) { $InvoiceLine->GrossUnitPrice = $line_item['gross_unit_price']; }
    // PeriodStartDate
    if ( isset($line_item['period_start_date']) ) { $InvoiceLine->PeriodStartDate = $line_item['period_start_date']; }
    // PeriodEndDate
    if ( isset($line_item['period_end_date']) ) { $InvoiceLine->PeriodEndDate = $line_item['period_end_date']; }
    
    return $InvoiceLine;
  }

  /**
   * Create Bank object
   *
   * @param $bank_id
   * @return \BankIdentifier
   */
  protected function createBank($bank_id) {
    $Bank = new \BankIdentifier();
    $Bank->Identifier = $bank_id;
    return $Bank;
  }

  /**
   * Create Currency object
   *
   * @param $currency
   * @return \CurrencyShortName
   */
  protected function createCurrency($currency) {
    $CurrencyShortName = new \CurrencyShortName();
    $CurrencyShortName->ShortName = $currency;
    return $CurrencyShortName;
  }

  /**
   * Create InvoceHeader object
   *
   * @param $invoice_header
   * @return \InvoiceHeader
   */
  protected function createInvoiceHeader($invoice_header){
    $InvoiceHeader = new \InvoiceHeader();
    
    // AccountBlockPrefix
    if ( isset($invoice_header['account_block_prefix']) ) { $InvoiceHeader->AccountBlockPrefix = $invoice_header['account_block_prefix']; }
    // Bank
    if ( isset($invoice_header['bank_id']) ) { $InvoiceHeader->Bank = $this->createBank($invoice_header['bank_id']); }
    // IssuerAddress
    if ( isset($invoice_header['issuer_address']) ) { 
      $InvoiceHeader->IssuerAddress = new \IssuerAddressIdentifier();
      $InvoiceHeader->IssuerAddress->Identifier = $invoice_header['issuer_address'];
    } else {
      $InvoiceHeader->IssuerAddress = new \DefaultIssuerAddress();
    }
    // Customer
    if ( isset($invoice_header['customer']) ) { $InvoiceHeader->Customer = $invoice_header['customer']; }
    // FulfillmentDate
    if ( isset($invoice_header['fulfillment_date']) ) { $InvoiceHeader->FulfillmentDate = $invoice_header['fulfillment_date']; }
    // PaymentDueDate
    if ( isset($invoice_header['payment_due_date']) ) { $InvoiceHeader->PaymentDueDate = $invoice_header['payment_due_date']; }
    // Currency
    if ( isset($invoice_header['currency']) ) { $InvoiceHeader->Currency = $this->createCurrency($invoice_header['currency']); }
    // InterEUVatExempt
    if ( isset($invoice_header['inter_eu_vat_exempt']) ) { $InvoiceHeader->InterEUVatExempt = $invoice_header['inter_eu_vat_exempt']; }
    // InvoiceDescription
    if ( isset($invoice_header['invoice_description']) ) { $InvoiceHeader->InvoiceDescription = $invoice_header['invoice_description']; }
    // Notes
    if ( isset($invoice_header['notes']) ) { $InvoiceHeader->Notes = $invoice_header['notes']; }
    // OrderNumber
    if ( isset($invoice_header['order_number']) ) { $InvoiceHeader->OrderNumber = $invoice_header['order_number']; }
    // LocalForeignCurrencyExchangeRate
    if ( isset($invoice_header['local_foreign_currency_exchange_rate']) ) { $InvoiceHeader->LocalForeignCurrencyExchangeRate = $invoice_header['local_foreign_currency_exchange_rate']; }
    // IsDomesticDelivery
    if ( isset($invoice_header['is_domestic_delivery']) ) { $InvoiceHeader->IsDomesticDelivery = $invoice_header['is_domestic_delivery']; }
    // IsVatReasonAccepted
    if ( isset($invoice_header['is_vat_reason_accepted']) ) { $InvoiceHeader->IsVatReasonAccepted = $invoice_header['is_vat_reason_accepted']; }
    /**
     * InvoiceDocumentType
     * 
     * 1 -- Invoice - Számla (alapértelmezett) -- Számla bizonylat.
     * 2 -- Void - Sztornó (érvénytelenítő) -- A számla bizonylat típusa Sztornó lehet, amennyiben a beküldött érvénytelenítő számlán, tételsoron nem történt változtatás.
     * 3 -- Credit Memo - Jóváíró (érvénytelenítő) -- A számla bizonylat típusa Jóváíró lehet csak 0 vagy annál kisebb végösszeg esetén, amennyiben a tételsoron történt változtatás.
     * 4 -- Debit Memo - Módosító -- A számla bizonylat típusa Módosító lehet csak 0-nál nagyobb végösszeg esetén, amennyiben a tételsoron történt változtatás.
     */
    if ( isset($invoice_header['invoice_document_type']) ) { $InvoiceHeader->InvoiceDocumentType = $invoice_header['invoice_document_type']; }
    // ReferenceInvoiceNumber
    if ( isset($invoice_header['reference_invoice_number']) ) { $InvoiceHeader->ReferenceInvoiceNumber = $invoice_header['reference_invoice_number']; }
    // ReferenceInvoiceFulfillmentDate
    if ( isset($invoice_header['reference_invoice_fulfillment_date']) ) { $InvoiceHeader->ReferenceInvoiceFulfillmentDate = $invoice_header['reference_invoice_fulfillment_date']; }
    // InvoiceHasElectronicServiceInEU
    if ( isset($invoice_header['invoice_has_electronic_service_in_eu']) ) { $InvoiceHeader->InvoiceHasElectronicServiceInEU = $invoice_header['invoice_has_electronic_service_in_eu']; }
    // ForceLocalForeignCurrencyExchang
    if ( isset($invoice_header['force_local_foreign_currency_exchang']) ) { $InvoiceHeader->ForceLocalForeignCurrencyExchang = $invoice_header['force_local_foreign_currency_exchang']; }
    
    return $InvoiceHeader;
  }
  
  /**
   * Create Invoice Transaction
   */
  protected function createInvoiceTransaction($create_invoice_transaction){
    $CreateInvoiceTransaction = new \CreateInvoiceTransaction();
    
    $CreateInvoiceTransaction -> TransactionId = md5('d8_billzone_' . time());
    
    // Header
    if ( isset($create_invoice_transaction['header']) ) { $CreateInvoiceTransaction->Header = $create_invoice_transaction['header']; }
    // Lines
    if ( isset($create_invoice_transaction['lines']) ) { $CreateInvoiceTransaction->Lines = $create_invoice_transaction['lines']; }
    // SendInvoiceToCustomer
    if ( isset($create_invoice_transaction['send_invoice_to_customer']) ) { $CreateInvoiceTransaction->SendInvoiceToCustomer = $create_invoice_transaction['send_invoice_to_customer']; }
    // SendInvoiceToEmailAddress
    if ( isset($create_invoice_transaction['send_invoice_to_email_address']) ) { $CreateInvoiceTransaction->SendInvoiceToEmailAddress = $create_invoice_transaction['send_invoice_to_email_address']; }
    /**
     * InvoiceCourierTypeId
     * 
     * Az InvoiceCourierTypeId három lehetséges értéket vehet fel:
     * 
     * - null: nem lesz kihatással a számlaértesítő kiküldésének módjára,
     * - 1: a számlaértesítő kiküldése emailben fog megtörténni,
     * - a számlaletöltési linkje a CreateInvoiceTransactionResult InvoiceCourierUrl tulajdonságában lesz visszaküldve.
     */
    if ( isset($create_invoice_transaction['invoice_courier_type_id']) ) { $CreateInvoiceTransaction->InvoiceCourierTypeId = $create_invoice_transaction['invoice_courier_type_id']; }
    // Clauses
    if ( isset($create_invoice_transaction['clauses']) ) { $CreateInvoiceTransaction->Clauses = $create_invoice_transaction['clauses']; }

    return $CreateInvoiceTransaction;
  }
  
  /**
   * Create Invoice Request
   */
  protected function createInvoiceRequest($CreateInvoiceTransaction) {
    $CreateInvoiceRequest = new \CreateInvoiceRequest();
    
    $CreateInvoiceRequest->CreateInvoiceTransaction = $CreateInvoiceTransaction; 
    $CreateInvoiceRequest->RequestId = 'd8_billzone_ci_' . time();
    $CreateInvoiceRequest->SecurityToken = $this->billzone_settings['security_token'];
    
    return $CreateInvoiceRequest;
  }
  
  /**
   * Create Invoice
   */
  public function createInvoice($invoice) {
    
    try {
      
      $CreateInvoice = new \CreateInvoice();
    
      // Create customer
      $invoice['invoice_header']['customer'] = $this->createCustomer($invoice['customer']);
      
      // Create Invoice line items
      $InvoiceLines = array();
      foreach($invoice['line_items'] as $line_item) {
        $InvoiceLines[] = $this->createLineItem($line_item);
      }
      
      // Create invoice header
      $InvoiceHeader = $this->createInvoiceHeader($invoice['invoice_header']);
      
      // Create Invoice Transaction
      $invoice['create_invoice_transaction']['header'] = $InvoiceHeader;
      $invoice['create_invoice_transaction']['lines'] = $InvoiceLines;
      $InvoiceTransaction = $this->createInvoiceTransaction($invoice['create_invoice_transaction']);
      
      // Create Invoice Request
      $InvoiceRequest = $this->createInvoiceRequest($InvoiceTransaction);
      
      // create CreateInvoice object
      $CreateInvoice = new \CreateInvoice();
      $CreateInvoice->request = $InvoiceRequest;
      
      //CreateInvoice
      $CreateInvoiceResponse = new \CreateInvoiceResponse();
      $CreateInvoiceResponse = $this->invoicing_service->CreateInvoice($CreateInvoice);
      
      // Error handling
      if ( $CreateInvoiceResponse->CreateInvoiceResult->RequestResult->Code != 61001) {
        $error_message = '<p>' . t("There was an error (in CreateInvoiceResult), when trying to create an invoice. Error number: @error_number", array(
          '@error_number' => $CreateInvoiceResponse->CreateInvoiceResult->RequestResult->Code,
        )) . '</p>';
        $error_message .= '<p>' . t("Invoice details") . ':</p>';
        $error_message .= '<pre>' . print_r($invoice, TRUE) . '</pre>';
        
        throw new \Exception($error_message);
      }
      
      if ( $CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->ResultCode->Code != 61001) {
        $error_message = '<p>' . t("There was an error (in TransactionResult), when trying to create an invoice. Error number: @error_number", array(
          '@error_number' => $CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->ResultCode->Code,
        )) . '</p>';
        $error_message .= '<p>' . t("Invoice details") . ':</p>';
        $error_message .= '<pre>' . print_r($invoice, TRUE) . '</pre>';
        
        throw new \Exception($error_message);
      }
      
      $output = array();
      
      // Add invoice number to the return value
      if ( !empty($CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->InvoiceNumber) ) {
        $output['invoice_number'] = $CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->InvoiceNumber;
      }
      
      // Add invoice courier URL to the return value
      if ( !empty($CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->InvoiceCourierUrl) ) {
        $output['invoice_courier_url'] = $CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->InvoiceCourierUrl;
      }
      
      // Add transaction ID to the return value
      if ( !empty($CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->TransactionId) ) {
        $output['transaction_id'] = $CreateInvoiceResponse->CreateInvoiceResult->TransactionResult->TransactionId;
      }
      
      // Add Request ID to the return value
      if ( !empty($CreateInvoiceResponse->CreateInvoiceResult->RequestId) ) {
        $output['request_id'] = $CreateInvoiceResponse->CreateInvoiceResult->RequestId;
      }
      
      $message = '<p>' . t("An invoice was successfully created.") . '</p>';
      $message .= '<p>' . t("Invoice details") . ':</p>';
      $message .= '<pre>' . print_r($output, TRUE) . '</pre>';
      \Drupal::logger('billzone')->notice($message);
      
      return $output;
      
    } catch (\Exception $e) {
      
      $error_message = $e->getMessage();
      \Drupal::logger('billzone')->error($error_message);
      return FALSE;
    }
    
  }
  
  /**
   * Download invoice
   */
  public function downloadInvoice($invoice_number){
    
    try {
      
      // Create DownloadInvoiceQuery object
      $DownloadInvoiceQuery = new \DownloadInvoiceQuery();
      $DownloadInvoiceQuery->InvoiceNumber = $invoice_number;
      
      // Create DownloadInvoiceRequest object
      $DownloadInvoiceRequest = new \DownloadInvoiceRequest();
      $DownloadInvoiceRequest->DownloadInvoiceQuery = $DownloadInvoiceQuery;
      $DownloadInvoiceRequest->RequestId = 'd8_billzone_di_' . time();
      $DownloadInvoiceRequest->SecurityToken = $this->billzone_settings['security_token'];
      
      // Create DownloadInvoice object
      $DownloadInvoice = new \DownloadInvoice();
      $DownloadInvoice->request = $DownloadInvoiceRequest;
      
      // Download the PDF
      $DownloadInvoiceResponse = $this->invoicing_service->DownloadInvoice($DownloadInvoice);
      
      // Error handling
      if ( $DownloadInvoiceResponse->DownloadInvoiceResult->RequestResult->Code != 61001) {
        
        $error_message = '<p>' . t("There was an error (in DownloadInvoiceResult), when trying to create an invoice. Error number: @error_number", array(
          '@error_number' => $DownloadInvoiceResponse->DownloadInvoiceResult->RequestResult->Code,
        )) . '</p>';
        $error_message .= '<p>' . t("Invoice details") . ':</p>';
        $error_message .= '<pre>' . print_r($invoice_number, TRUE) . '</pre>';
        
        throw new \Exception($error_message);
      }
      
      if ( $DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->ResultCode->Code != 61001) {
        
        $error_message = '<p>' . t("There was an error (in DownloadInvoiceResult), when trying to create an invoice. Error number: @error_number", array(
          '@error_number' => $DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->ResultCode->Code,
        )) . '</p>';
        $error_message .= '<p>' . t("Invoice details") . ':</p>';
        $error_message .= '<pre>' . print_r($invoice_number, TRUE) . '</pre>';
        
        throw new \Exception($error_message);
      }
      
      $InvoiceDocument = $DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->InvoiceDocument;
      
      $output = array();
      
      // Add invoice number to the return value
      if ( !empty($DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->InvoiceNumber) ) {
        $output['invoice_number'] = $DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->InvoiceNumber;
      }
      
      // Add invoice document to the return value
      if ( !empty($DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->InvoiceDocument) ) {
        $output['invoice_document'] = $DownloadInvoiceResponse->DownloadInvoiceResult->QueryResult->InvoiceDocument;
      }
      
      // Add Request ID to the return value
      if ( !empty($DownloadInvoiceResponse->DownloadInvoiceResult->RequestId) ) {
        $output['request_id'] = $DownloadInvoiceResponse->DownloadInvoiceResult->RequestId;
      }
      
      return $output;
      
    } catch (\Exception $e) {
      
      $error_message = $e->getMessage();
      \Drupal::logger('billzone')->error($error_message);
      return FALSE;
    }
  }
}