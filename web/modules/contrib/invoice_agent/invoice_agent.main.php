<?php

/**
 * @file
 * Main functions for Invoice Agent.
 */

use Drupal\file\Entity\File;
use Drupal\commerce_order\Entity\Order;

/**
 * Main cron job.
 *
 * Performs three operations:
 * - Processes absolute new orders (invoice_status is NULL).
 * - Processes orders that are completed, but not paided. (invoice_status is P).
 * - Processes faulty orders. (invoice_status is E).
 *
 * Parameters:
 *
 * - boolean $programmatically: If this is true, then called programmatically.
 *   In this case ignores the configuration settings about cron processing.
 *
 * Return (boolean) TRUE if has not been processed any orders.
 */
function invoice_agent__cron($programmatically) {
  $processed_orders = 0;
  if ($programmatically || \Drupal::config('invoice_agent.settings')->get('by_cron')) {
    if (invoice_agent__process(invoice_agent__get_new_orders(), $processed_orders)) {
      if (invoice_agent__process(invoice_agent__get_orders_by_invoice_status('P'), $processed_orders)) {
        invoice_agent__process(invoice_agent__get_orders_by_invoice_status('E'), $processed_orders);
      }
    }
  }
  return $processed_orders == 0;
}

/**
 * Common order insert or update hook.
 *
 * This function invokes by on insert or update each order entity. We don't have
 * to deal with it until the getCompletedTime() is empty. The payment may have
 * been made in the same step, but it may not.
 */
function invoice_agent__entity_common(Order $order) {
  if (\Drupal::config('invoice_agent.settings')->get('on_save')) {
    if ($order->getCompletedTime() || $order->isPaid()) {
      invoice_agent__process_order($order);
    }
  }
}

/**
 * Process a result array of orders.
 *
 * Iterate on result array, load the Order entity and process it.
 *
 * Parameters:
 * - array $orders
 *   The result array, contains the order ids.
 * - integer &$processed_orders
 *   Previously processed order items.
 *
 * Return (boolean) TRUE, if we have not yet reached the batch_size value
 *   specified in the configuration. If the configured value is 0, then always
 *   TRUE.
 */
function invoice_agent__process($orders, &$processed_orders) {
  $batch_size = \Drupal::config('invoice_agent.settings')->get('batch_size');
  foreach ($orders as $order) {
    if ($entity = Order::load($order->order_id)) {
      if (invoice_agent__process_order($entity)) {
        if ($batch_size && ++$processed_orders >= $batch_size) {
          break;
        }
      }
    }
  }
  return !$batch_size || $processed_orders < $batch_size;
}

/**
 * Process each order.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 *
 * Return (boolean). TRUE that there was a need for invoice generation.
 *
 * This function is called by hook_cron or an order insert or update hook.
 * This creates the required invoice type, if necessary.
 */
function invoice_agent__process_order(Order $order) {

  // Gets the required invoice type from the configuration.
  if ($invoice_type = invoice_agent__get_required_invoice_type($order)) {

    // Add a log entry about invoice processing.
    \Drupal::logger('invoice_agent')
      ->notice('@type is generating for order #@id.', [
        '@type' => invoice_agent__invoice_types_full()[$invoice_type],
        '@id' => $order->id(),
      ]
    );

    // Sets the invoice_status to (E)rror. This will be overwritten if the
    // invoice has been successfully generated.
    invoice_agent__set_invoice_status($order->id(), 'E');

    // If we do not need to produce any invoice type based on the configuration,
    // then we will skip the invoice creation process.
    if ($invoice_type != 'nothing') {

      // Process the invoice.
      $result = invoice_agent__call_agent(
        invoice_agent__get_cookie(),
        invoice_agent__generate_xml(
          invoice_agent__get_placeholders($order, $invoice_type)
        )
      );

      // Take the final steps.
      invoice_agent__set_cookie($result->cookie);
      invoice_agent__save_document($order, $invoice_type, $result);
      invoice_agent__notify_customer($order, $invoice_type, $result);
    }

    // Update invoice status based on order payed state.
    invoice_agent__set_invoice_status($order->id(), $order->isPaid() ? 'C' : 'P');

    // Add a log entry about invoice processing.
    \Drupal::logger('invoice_agent')
      ->notice('@type is generated for order #@id.', [
        '@type' => invoice_agent__invoice_types_full()[$invoice_type],
        '@id' => $order->id(),
      ]
    );
  }
  return !empty($invoice_type);
}

/**
 * Call Szamla Agent.
 *
 * Parameters:
 * - string $cookie
 *   The cookie from the last request or an empty string.
 * - string $xml
 *   The prepared XML string.
 *
 * Return (array). The result array.
 */
function invoice_agent__call_agent($cookie, $xml) {
  // Default value, may be overriden after the request.
  $result['cookie'] = $cookie;

  // Save the XML as temporary file.
  $filename = \Drupal::service('uuid')->generate();
  $file = File::create([
    'uid' => 0,
    'filename' => $filename,
    'filesize' => strlen($xml),
    'uri' => "temporary://$filename",
    'filemime' => 'text/plain',
    'status' => 0,
  ]);
  $file->save();
  file_put_contents($file->getFileUri(), $xml);

  // CURL setup.
  $ch = curl_init('https://www.szamlazz.hu/szamla/');
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, TRUE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action-xmlagentxmlfile' => new CURLFile(drupal_realpath($file->getFileUri())),
  ]);
  curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);
  curl_setopt($ch, CURLOPT_COOKIE, $cookie);

  // Execute request.
  $agent_response = curl_exec($ch);

  // Detect HTTP errors.
  if ($http_error = curl_error($ch)) {
    throw new Exception($http_error);
  }

  // Process the result.
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $agent_header = substr($agent_response, 0, $header_size);
  $result['document'] = substr($agent_response, $header_size);
  curl_close($ch);
  $header_array = explode("\n", $agent_header);
  foreach ($header_array as $val) {
    if (substr($val, 0, strlen('szlahu_error:')) === 'szlahu_error:') {
      throw new Exception(urldecode(substr($val, strlen('szlahu_error:'))));
    }
    if (substr($val, 0, strlen('szlahu_szamlaszam: ')) === 'szlahu_szamlaszam: ') {
      $result['invoice_no'] = substr($val, strlen('szlahu_szamlaszam: '));
    }
    // Set-Cookie: JSESSIONID=66AA5215602A2BABC6A9860035A9ACA2.sas;
    // Path=/szamla/; Secure; HttpOnly.
    if (substr($val, 0, strlen('Set-Cookie: JSESSIONID')) === 'Set-Cookie: JSESSIONID') {
      $result['cookie'] = trim(explode(':', explode(';', $val)[0])[1]);
    }
  }

  return (object) $result;
}

/**
 * Gets he cookie from the last request.
 *
 * Return (string). The cookie from the last request or an empty string.
 */
function invoice_agent__get_cookie() {
  $cookie = '';
  if ($matched_files = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['filename' => 'szamlazz.hu.cookie'])) {
    $cookie = file_get_contents(reset($matched_files)->getFileUri());
  }
  return $cookie;
}

/**
 * Sets the cookie from the last request.
 *
 * Parameters:
 * - string $cookie
 *   The cookie from the last request.
 */
function invoice_agent__set_cookie($cookie) {
  if ($matched_files = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['filename' => 'szamlazz.hu.cookie'])) {
    $file = reset($matched_files);
    $file->setSize(strlen($cookie));
  }
  else {
    $file = File::create([
      'uid' => 0,
      'filename' => 'szamlazz.hu.cookie',
      'filesize' => strlen($cookie),
      'uri' => "temporary://szamlazz.hu.cookie",
      'filemime' => 'text/plain',
      'status' => 0,
    ]);
  }
  $file->save();
  file_put_contents($file->getFileUri(), $cookie);
}

/**
 * Save the document, if required.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 * - string $invoice_type
 *   The required invoice type.
 * - object $result
 *   The result object.
 */
function invoice_agent__save_document(Order $order, $invoice_type, $result) {
  if (\Drupal::config('invoice_agent.settings')->get("{$invoice_type}_store")) {
    $order->get('field_invoice')->appendItem([
      'target_id' => invoice_agent__create_media(
        "{$result->invoice_no}.pdf",
        $order->getCustomer()->id(),
        $result->invoice_no,
        'application/pdf',
        $result->document
      ),
    ]);
    $order->save();
  }
}

/**
 * Notify customer, if required.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 * - string $invoice_type
 *   The required invoice type.
 * - object $result
 *   The result object.
 */
function invoice_agent__notify_customer($order, $invoice_type, $result) {
  if (\Drupal::config('invoice_agent.settings')->get("{$invoice_type}_notification") == 's') {
    $params = [
      'subject' => \Drupal::config('invoice_agent.settings')
        ->get("{$invoice_type}_notification_subject"),
      'body' => \Drupal::config('invoice_agent.settings')
        ->get("{$invoice_type}_notification_body"),
    ];

    if (\Drupal::config('invoice_agent.settings')->get("{$invoice_type}_attach")) {
      $filename = $result->invoice_no;
      $file = File::create([
        'uid' => 0,
        'filename' => $filename,
        'filesize' => strlen($result->document),
        'uri' => "temporary://$filename",
        'filemime' => 'application/pdf',
        'status' => 0,
      ]);
      $file->save();
      file_put_contents($file->getFileUri(), $result->document);
      $params['attachments'] = [(object) [
        'uri' => $file->getFileUri(),
        'filename' => $file->getFilename(),
        'filemime' => $file->getMimeType(),
      ],
      ];
    }

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if (!\Drupal::service('plugin.manager.mail')
      ->mail('invoice_agent', 'notify_customer', $order->getEmail(), $langcode, $params, NULL, TRUE)
    ) {
      throw new Exception('There was a problem sending the message.');
    }
  }
}
