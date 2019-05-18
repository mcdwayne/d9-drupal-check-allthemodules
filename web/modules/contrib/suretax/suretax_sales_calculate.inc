<?php

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Creates SureTax Sales Calculation / Deletion based on request.
 * 
 * @param type $order
 *   Complete Order Details.
 * @param type $line_item
 *   LineItem Details.
 * @param type $uid
 *   uid of User that created Order.
 * @param type $request
 *   Request that has POST / CANCEL process.
 */
function suretax_sales_request_tax($order_load, $uid) {
  // Fetch Suretax Details.
  $suretax_config = \Drupal::config('suretax.settings');
  $mode = $suretax_config->get('suretax_mode');
  $ClientNumber = $suretax_config->get('suretax_client_id_' . $mode);
  $ValidationKey = $suretax_config->get('suretax_validation_key_' . $mode);
  // Fetch LineItems
  $line_items = $order_load->getLineItems();
  $tax_total = 0;
  $complete_array = '';
  foreach ($line_items as $line_item) {
    // Load Lineitem to get Details of each.
    //$line_item = commerce_line_item_load($line_item_id->line_item_id->value);
    // If type is product variation.
    if ($line_item->type->target_id == 'product_variation') {
      // Calculate only product price for Order Total.
      $tax_total += ($line_item->unit_price->amount * $line_item->quantity->value);
      $array = '{
        "LineNumber":"' . $line_item->getTitle() . '",
        "InvoiceNumber":"' . $order_load->getOrderNumber() . '",
        "CustomerNumber":"1",
        "OrigNumber":"8585260000",
        "TermNumber":"8585260000",
        "BillToNumber":"8585260000",
        "Zipcode":"",
        "Plus4":"",
        "P2PZipcode":"",
        "P2PPlus4":"",
        "ShipFromZipcode":"85256",
    		"ShipFromPlus4":"0000",
    		"OrderPlacementZipcode":"85546",
    		"OrderPlacementPlus4":"0000",
    		"OrderApprovalZipcode":"85638",
    		"OrderApprovalPlus4":"0000",
        "TransDate":"2001-01-01",
        "Revenue":' . number_format(($line_item->unit_price->amount), 2) . ',
        "Units":1.00,
        "UnitType":"00",
        "Seconds":55,
        "TaxIncludedCode":"0",
        "TaxSitusRule":"01",
        "TransTypeCode":"010101",
        "SalesTypeCode":"R",
        "RegulatoryCode":"99",
        "TaxExemptionCodeList":["00","00"],
        "Geocode":"",
    		"AuxRevenue":"0",
    		"AuxRevenueType":"01",
    		"FreightOnBoard":"D",
    		"ShipFromPOB":"1",
    		"MailOrder":"1",
    		"CommonCarrier":"1",
    		"OriginCountryCode":"US",
    		"DestCountryCode":"US",
    		"UDF":7521449127
    }';
      $complete_array .= $array . ',';
    }
  }
  $stan = $suretax_config->get('suretax_stan');
  // Array of lineitems.
  $complete_array = rtrim($complete_array, ',');
  // JSON Request for Suretax
  $get_request = '{
"ClientNumber":"' . $ClientNumber . '",
"BusinessUnit":"000000697",
"ValidationKey":"' . $ValidationKey . '",
"DataYear":"2015",
"DataMonth":"10",
"TotalRevenue":"' . $tax_total . '",
"ReturnFileCode":"0",
"ClientTracking":"track",
"IndustryExemption":"",
"ResponseType":"D",
"ResponseGroup":"03",
"STAN": "' . $stan . '",
"ItemList":[
    ' . $complete_array . '
    ]}';
  $api_url = $suretax_config->get('suretax_api_' . $mode);
  // Initialize curl to post data.
  $api_session = curl_init();
  curl_setopt($api_session, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzipped'));
  curl_setopt($api_session, CURLOPT_ENCODING, 'gzip');
  $post_string = gzencode("request=$get_request");
  curl_setopt($api_session, CURLOPT_POSTFIELDS, $post_string);
  curl_setopt($api_session, CURLOPT_POST, 1);
  curl_setopt($api_session, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($api_session, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($api_session, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($api_session, CURLOPT_URL, $api_url);
  curl_setopt($api_session, CURLOPT_TIMEOUT, 20);
  $response = curl_exec($api_session);
  $curl_data = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $response);
  $curl_data = str_replace('<string xmlns="http://tempuri.org/">', '', $curl_data);
  $curl_data = str_replace('</string>', '', $curl_data);
  curl_close($api_session);
  $result = json_decode($curl_data, TRUE);
  if ($result) {
    // Insert results into Suretax table.
    db_merge('suretax')
        ->key(array(
          'user_id' => $uid,
          'transaction_id' => $result['TransId'],
          'success' => 'Yes',
          'order_id' => $order_load->getOrderNumber(),
        ))
        ->fields(array(
          'user_id' => $uid,
          'transaction_id' => $result['TransId'],
          'total_tax' => $result['TotalTax'],
          'response_code' => $result['ResponseCode'],
          'success' => $result['Successful'],
          'api_response' => $curl_data,
          'order_id' => $order_load->getOrderNumber(),
        ))
        ->execute();
  }
  \Drupal::logger('suretax')->notice('<pre>' . print_r($result, true) . '</pre>');
  // If response is success then return suretax total. 
  if ($result['Successful'] == 'Y') {
    return $result['TotalTax'];
  }
  else {
    return FALSE;
  }
}

/**
 * Implements suretax delete for order..
 */
function suretax_delete_tax($order_id) {
  // Get last transaction ID for Order.
  $query = db_select('suretax', 's');
  $query->fields('s', array('transaction_id'));
  $query->condition('order_id', $order_id, '=');
  $query->orderby('transaction_id', 'DESC')->range(0, 1);
  $TransId = $query->execute()->fetchField();
  // Get Suretax Details.
  $mode = $suretax_config->get('suretax_mode');
  $ClientNumber = $suretax_config->get('suretax_client_id_' . $mode);
  $ValidationKey = $suretax_config->get('suretax_validation_key_' . $mode);
  // Post fields for Cancel Order Suretax.
  $post_fields = '{
"ClientNumber":"' . $ClientNumber . '",
"ClientTracking":"test",
"TransId":"' . $TransId . '",
"ValidationKey":"' . $ValidationKey . '"
}';
//Initiate cURL request.
  $ch = curl_init();
// SureTax Cancel request URL.
  $suretax_url = $suretax_config->get('suretax_cancel_api_' . $mode);
// Set Headers.
  curl_setopt($ch, CURLOPT_POSTFIELDS, "requestCancel=$post_fields"); // Assign POST Data
  curl_setopt($ch, CURLOPT_POST, 1); // Select POST as transfer type
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_URL, $suretax_url); // The Web service URL
  curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Set timeout
  $response = curl_getinfo($ch);
  // Close curl.
  curl_close($ch);
}

/**
 * Implements for suretax delete transaction.
 */
function suretax_request_tax_delete($order_id) {
  // Delete transactions in suretax table.
  if ($order_id) {
    db_delete('suretax')
        ->condition('order_id', $order_id, '=')
        ->execute();
  }
}
