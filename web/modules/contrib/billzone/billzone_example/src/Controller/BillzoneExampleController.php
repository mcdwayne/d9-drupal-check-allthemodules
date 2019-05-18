<?php

namespace Drupal\billzone_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\billzone\Billzone\Billzone;

/**
 * @file This controller demonstrates the usage of the Billzone module
 * @author David Czinege <czinege.david.89@gmail.com>
 */

/**
 * Class BillzoneExampleController.
 *
 * @package Drupal\billzone_example\Controller
 */
class BillzoneExampleController extends ControllerBase {

  /**
   * Drupal\billzone\Billzone\Billzone definition.
   *
   * @var \Drupal\billzone\Billzone\Billzone
   */
  protected $billzone;

  /**
   * {@inheritdoc}
   */
  public function __construct(Billzone $billzone) {
    $this->billzone = $billzone;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('billzone')
    );
  }

  /**
   * Create invoice example
   *
   * @return string
   *   Return the $invoice variable structure as string.
   */
  public function createInvoice() {

    // Get the billzone's configuration (Notice: Use dependency injection instead of this solution.)
    $billzone_config = \Drupal::config('billzone.settings');

    // This variable demonstrates how data should be structured to use the invoice creating method
    $invoice = array(
      // Customer information
      'customer' => array(
        'name'                  => 'John Doe',                 // Customer name
        'address_country_id'    => 'HU',                       // Customer's address - Country code (ISO 3166-1-alpha-2)
        //'address_state'         => 'Budapest',               // Customer's address - State (optional)
        'address_postal_code'   => 1136,                       // Customer's address - Postal code
        'address_city'          => 'Budapest',                 // Customer's address - City
        'address_line_1'        => 'Balzac utca 15',           // Customer's address - Address line 1
        //'address_line_2'      => '',                         // Customer's address - Address line 2 (optional)
        //'group_identification_number' => '11111111-1-11"',   // Group identification number (optional)
        //'customer_identifier' => 1234,                       // If you upload a customer on the Billzone website, you can set its identifier here, but it is not needed (optional)
        //'eu_tax_number'       => 'HU12345678',               // EU tax number (optional)
        //'tax_number'          => '12345678-1-22',            // Tax number (optional)
      ),
      // Line item list - This is a list of arrays
      'line_items' => array(
        array(
          'product_name'        => 'Product 1',                                 // Product name
          'net_unit_price'      => 1000,                                        // Net unit price (You can use this or gross unit price)
          //'gross_unit_price'  => 1270,                                        // Gross unit price (You can use this or net unit price)
          'quantity'            => 5,                                           // Quantity of the product
          'vat_percentage'      => '27',                                        // This vat code must be set on Billzone's system
          'unit_identifier'     => 'DARAB',                                     // This unit identifier must be set on Billzone's system
          'product_text_identifier' => 'SKU-1234',                              // The product's unique identifier
          //'product_statistical_code' => '88.69.72',                           // The product's customs number or service registry numbers (magyarul: VTSZ/SZJ) (optional)
          //'period_start_date' => date("Y-m-d",time()),                        // Period start date (optional)
          //'period_end_date' => date("Y-m-d",time() + 30 * 24 * 3600),         // Period end date (optional)
        ),
      ),
      // Invoice header information
      'invoice_header' => array(
        'account_block_prefix' => $billzone_config->get('default_account_block_prefix'),  // This account block prefix must be set on Billzone's system
        'bank_id' => 'budapest_bank',                                                     // This bank id or payment method id must be set on Billzone's system
        'fulfillment_date' => date("Y-m-d",time()),                                       // Fulfillment date
        'payment_due_date' => date("Y-m-d",time() + ($billzone_config->get('payment_deadline') * 24 * 3600)), // Payment due end / payment deadline
        'currency' => 'HUF',                                                              // Currency code
        'inter_eu_vat_exempt' => FALSE,                                                   // VAT free inside EU
        'order_number' => 1,                                                              // Order number
        'invoice_document_type' => 1,                                                     // Invoice document type (1 = default invoice, 2 = void invoice, 3 = credit memo, 4 = debit memo)
        'invoice_description' => $billzone_config->get('invoice_description'),            // Invoice description (optional)
        'notes' => $billzone_config->get('notes'),                                        // Notes (optional)
        //'issuer_address' => '',                                                         // You can modify here the issuer address. If you don't use this, the default issuer address will be used.
        //'local_foreign_currency_exchange_rate' => TRUE,                                 // Local foreign currency exchange rate - If invoice currency is different than the invoice issuer's default currency (optional)
        //'is_domestic_delivery' => TRUE,                                                 // Is it a domestic delivery? (optional)
        //'is_vat_reason_accepted' => TRUE,                                               // Is VAT reason accepted? (optional)
        //'reference_invoice_number' => 'TEST0001',                                       // Reference invoice number (optional)
        //'reference_invoice_fulfillment_date' => '2017-03-13',                           // Reference invoice's fulfillment date (optional)
        //'invoice_has_electronic_service_in_eu' => FALSE,                                // Does the invoice have electronic service in EU?
        //'force_local_foreign_currency_exchang' => TRUE,                                 // It shows a currency exchange box on the invoice
      ),
    );

    // Create invoice
    $this->billzone->createInvoice($invoice);

    /*
    Return value is FALSE if something went wrong, and it logs an error message (/admin/reports/dblog)

    If it was a successful invoice creation, the returning value is like this:

    array(
      'invoice_number' => 'TEST0001',
      'transaction_id' => '735c3f4946b22a974ed7d5aa233b8d30',
      'request_id' => 'd8_billzone_ci_1489421333',
    )
    */

    return [
      '#type' => 'markup',
      '#markup' => '<pre>' . print_r($invoice, TRUE) . '</pre>',
    ];
  }

  /**
   * Create invoice example
   *
   * @return string
   *   Return the $invoice variable structure as string.
   */
  public function downloadInvoice() {
    // Get a generated invoice's number
    $invoice_number = 'TEST0001';

    // Download the invoice
    $downloaded_invoice = $this->billzone->downloadInvoice($invoice_number);

    // Set a name for the file
    $invoice_file_name = $downloaded_invoice['invoice_number'] . '.pdf';

    // Create a file object from the downloaded invoice
    $file = file_save_data($downloaded_invoice['invoice_document'], 'private://' . $invoice_file_name, FILE_EXISTS_REPLACE);

    /*
    The code above creates a PDF file inside the private file directory and it creates a corresponding Drupal file object
    You can use the file object's ID if you want to attach this file to a content entity.

    For example:

    // Load node object
    $node = Node::load(123);

    // Set its field
    $node->field_uc_billzone_invoice->setValue(array(
      'target_id' => $file->id(),
    ));

    // Save it
    $node->save();

    */

    return [
      '#type' => 'markup',
      '#markup' => $this->t("See this controller's code here: billzone/billzone_example/src/Controller/BillzoneExampleController.php")
    ];
  }

}
