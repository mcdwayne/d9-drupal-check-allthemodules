<?php

/**
 * @file
 * Utility functions for Invoice Agent.
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\commerce_order\Entity\Order;

/**
 * Gets the current configuration.
 *
 * Return (array) - The current configuration array.
 */
function invoice_agent__get_config() {
  $return = [];
  foreach (invoice_agent__config_items() as $key => $element) {
    $return["@{$key}"] = \Drupal::config('invoice_agent.settings')->get($key);
  }
  return $return;
}

/**
 * Removes technical elements from field definitions.
 *
 * Parameters:
 * - array $element
 *   The full configuration array.
 *
 * Return (array) - The configuration array without technical elements.
 */
function invoice_agent__clean_form_item($element) {
  foreach (array_keys($element) as $key) {
    if (strpos($key, '#') === FALSE) {
      unset($element[$key]);
    }
  }
  return $element;
}

/**
 * Gets the required invoice type by the configured order completed behavior.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 *
 * Return (string). The required invoice type.
 */
function invoice_agent__get_required_invoice_type(Order $order) {
  $invoice_type = '';

  // Gets the current invoice status field.
  switch (invoice_agent__get_invoice_status($order->id())) {

    // New items, or it was a mistake before.
    case '':
    case 'E':
      $invoice_type = \Drupal::config('invoice_agent.settings')
        ->get($order->isPaid() ? 'paid' : 'unpaid');
      break;

    // We just waiting for payment. If not payed, then skip this order.
    case 'P':
      if ($order->isPaid()) {
        $invoice_type = \Drupal::config('invoice_agent.settings')
          ->get('close');
      }
      break;

  }
  return $invoice_type;
}

/**
 * Adds a new element to the placeholders array.
 *
 * Parameters:
 * - array $placeholders
 *   The filled placeholder's array.
 * - string $pattern
 *   The new pattern.
 * - string $replacement
 *   The new replacement.
 *
 * Return: none. This just extends the placeholder's array.
 */
function invoice_agent__add_placeholder(&$placeholders, $pattern, $replacement) {
  $placeholders['patterns'][] = "/@{$pattern}/";
  $placeholders['replacements'][] = $replacement;
}

/**
 * Prepares the placeholders array.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 * - string $invoice_type
 *   The required invoice type.
 *
 * Return (array). The prepared placeholders array.
 */
function invoice_agent__get_placeholders(Order $order, $invoice_type) {

  // Initialize required variables.
  $placeholders = [];
  $config_items = invoice_agent__config_items();
  // Avoid PHP notice: Only variables should be passed by reference...
  $address_array = $order->billing_profile->entity->address->getValue();
  $address = reset($address_array);
  $notification = \Drupal::config('invoice_agent.settings')
    ->get("{$invoice_type}_notification");
  $deadline = $order->isPaid() ? 0 : \Drupal::config('invoice_agent.settings')
    ->get("{$invoice_type}_deadline");

  // Add the placeholder elements from configuration.
  foreach ($config_items as $key => $element) {
    if (array_key_exists('placeholder', $element)) {
      invoice_agent__add_placeholder($placeholders, $key,
        \Drupal::config('invoice_agent.settings')->get($key));
    }
  }

  // Add the placeholder elements for invoice types.
  foreach (array_keys(invoice_agent__invoice_types()) as $type) {
    invoice_agent__add_placeholder($placeholders, "is_{$type}",
      $invoice_type == $type ? 'true' : 'false');
  }

  // Sets the required other placeholders.
  invoice_agent__add_placeholder($placeholders, 'is_e_invoice',
    \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_e_invoice") ? 'true' : 'false');
  invoice_agent__add_placeholder($placeholders, 'is_download',
    \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_attach") ||
      \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_store") ? 'true' : 'false');
  invoice_agent__add_placeholder($placeholders, 'dated',
    gmdate('Y-m-d'));
  invoice_agent__add_placeholder($placeholders, 'completion',
    gmdate('Y-m-d'));
  invoice_agent__add_placeholder($placeholders, 'deadline',
    gmdate('Y-m-d', strtotime("+{$deadline} days")));
  invoice_agent__add_placeholder($placeholders, 'payment',
    invoice_agent__get_payment_mode($order, $invoice_type));
  invoice_agent__add_placeholder($placeholders, 'currency',
    $order->getTotalPrice()->getCurrencyCode());
  invoice_agent__add_placeholder($placeholders, 'language',
    \Drupal::languageManager()->getCurrentLanguage()->getId());
  invoice_agent__add_placeholder($placeholders, 'invoice_note',
    \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_note"));
  invoice_agent__add_placeholder($placeholders, 'order',
    $order->id());
  invoice_agent__add_placeholder($placeholders, 'prefix',
    \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_prefix"));
  invoice_agent__add_placeholder($placeholders, 'is_payed',
    $order->isPaid() ? 'true' : 'false');
  invoice_agent__add_placeholder($placeholders, 'subject',
    $notification == 't'
      ? ''
      : \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_notification_subject"));
  invoice_agent__add_placeholder($placeholders, 'body',
    $notification == 't'
      ? ''
      : \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_notification_body"));
  invoice_agent__add_placeholder($placeholders, 'customer_name',
    trim("{$address['family_name']} {$address['given_name']}"));
  invoice_agent__add_placeholder($placeholders, 'customer_zip',
    $address['postal_code']);
  invoice_agent__add_placeholder($placeholders, 'customer_city',
    $address['locality']);
  invoice_agent__add_placeholder($placeholders, 'customer_address',
    trim("{$address['address_line1']} {$address['address_line2']}"));
  invoice_agent__add_placeholder($placeholders, 'customer_address',
    trim("{$address['address_line1']} {$address['address_line2']}"));
  invoice_agent__add_placeholder($placeholders, 'customer_email',
    $order->getEmail());
  invoice_agent__add_placeholder($placeholders, 'is_email',
    strpos('th', $notification) === FALSE ? 'false' : 'true');
  invoice_agent__add_placeholder($placeholders, 'post_xml',
    invoice_agent__get_post_block($placeholders, $invoice_type));
  invoice_agent__add_placeholder($placeholders, 'items_xml',
    invoice_agent__get_items_block($order, $invoice_type));
  invoice_agent__add_placeholder($placeholders, 'adjustments_xml',
    invoice_agent__get_adjustments_block($order, $invoice_type));

  return $placeholders;
}

/**
 * Gets the post block or an empty string depend on configuration.
 *
 * Parameters:
 * - array $placeholders
 *   The filled placeholder's array.
 * - string $invoice_type
 *   The required invoice type.
 *
 * Return (string). The prepared XML string about post information or an
 *   empty string, if there is no post required.
 */
function invoice_agent__get_post_block($placeholders, $invoice_type) {
  return preg_replace($placeholders['patterns'], $placeholders['replacements'],
    \Drupal::config('invoice_agent.settings')->get("{$invoice_type}_post")
      ? file_get_contents(drupal_get_path('module', 'invoice_agent') . '/xml/xmlpost.xml')
      : '');
}

/**
 * Gets the items from the order.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 * - string $invoice_type
 *   The required invoice type.
 *
 * Return (string). The prepared XML string about ordered items or an
 *   empty string, if there is no item to be paid.
 */
function invoice_agent__get_items_block(Order $order, $invoice_type) {

  // Initialize the return value.
  $xml = '';

  // Iterate on order items.
  foreach ($order->getItems() as $order_item) {

    // Skip free items if required.
    if (\Drupal::config('invoice_agent.settings')->get("{$invoice_type}_remove")) {
      if (intval($order_item->getAdjustedTotalPrice()->getNumber()) == 0) {
        continue;
      }
    }

    // Initialize placeholders.
    $placeholders = [];

    // Sets order item properties.
    invoice_agent__add_placeholder($placeholders, 'title',
      $order_item->getTitle());
    invoice_agent__add_placeholder($placeholders, 'quantity',
      $order_item->getQuantity());
    invoice_agent__add_placeholder($placeholders, 'adjustedtotalprice',
      intval($order_item->getAdjustedTotalPrice()->getNumber()));

    // If the seller is AAM, then sets the full (taxed) prices.
    // Otherwise sets the normal prices.
    if (\Drupal::config('invoice_agent.settings')->get('taxfree')) {
      invoice_agent__add_placeholder($placeholders, 'unitprice',
        intval($order_item->getAdjustedUnitPrice()->getNumber()));
      invoice_agent__add_placeholder($placeholders, 'totalprice',
        intval($order_item->getAdjustedTotalPrice()->getNumber()));
      invoice_agent__add_placeholder($placeholders, 'tax_rate',
        'AAM');
      invoice_agent__add_placeholder($placeholders, 'tax',
        0);
    }
    else {
      invoice_agent__add_placeholder($placeholders, 'unitprice',
        intval($order_item->getUnitPrice()->getNumber()));
      invoice_agent__add_placeholder($placeholders, 'totalprice',
        intval($order_item->getTotalPrice()->getNumber()));

      // Finds the tax and the tax rate adjustments.
      $hasTax = FALSE;
      foreach ($order_item->getAdjustments() as $adjustment) {
        if ($adjustment->getType() == 'tax') {
          $hasTax = TRUE;
          invoice_agent__add_placeholder($placeholders, 'tax_rate',
            intval(100 * $adjustment->getPercentage()));
          invoice_agent__add_placeholder($placeholders, 'tax',
            intval($order_item->getTotalPrice()->getNumber() * $adjustment->getPercentage()));
        }
      }

      // No tax adjustment found.
      if (!$hasTax) {
        invoice_agent__add_placeholder($placeholders, 'tax_rate', 0);
        invoice_agent__add_placeholder($placeholders, 'tax', 0);
      }
    }

    // Invoke other modules to set the item's 'note' field and sets a blank note
    // if no module has set it.
    \Drupal::moduleHandler()->invokeAll('alter_order_item_xml', [$order_item, $placeholders]);
    if (!array_key_exists('@note', $placeholders['patterns'])) {
      invoice_agent__add_placeholder($placeholders, 'note', '');
    }

    // Expand the return value with this item.
    $xml .= preg_replace($placeholders['patterns'], $placeholders['replacements'],
      file_get_contents(drupal_get_path('module', 'invoice_agent') . '/xml/xmlitem.xml'));
  }

  return $xml;
}

/**
 * Gets the adjustments from the order.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 * - string $invoice_type
 *   The required invoice type.
 *
 * Return (string). The prepared XML string about ordered items or an
 *   empty string, if there is no item to be paid.
 */
function invoice_agent__get_adjustments_block(Order $order, $invoice_type) {

  // Initialize the return value.
  $xml = '';

  foreach ($order->getAdjustments() as $adjustment) {

    // Skip free items if required.
    if (\Drupal::config('invoice_agent.settings')->get("{$invoice_type}_remove")) {
      if (intval($adjustment->getAmount()->getNumber()) == 0) {
        continue;
      }
    }

    // Skip included adjustments.
    if (intval($adjustment->isIncluded())) {
      continue;
    }

    // Initialize placeholders.
    $placeholders = [];

    // Sets order item properties.
    invoice_agent__add_placeholder($placeholders, 'title',
      $adjustment->getLabel());
    invoice_agent__add_placeholder($placeholders, 'quantity',
      1);
    invoice_agent__add_placeholder($placeholders, 'adjustedtotalprice',
      intval($adjustment->getAmount()->getNumber()));
    invoice_agent__add_placeholder($placeholders, 'unitprice',
      intval($adjustment->getAmount()->getNumber()));
    invoice_agent__add_placeholder($placeholders, 'totalprice',
      intval($adjustment->getAmount()->getNumber()));
    invoice_agent__add_placeholder($placeholders, 'tax_rate',
      0);
    invoice_agent__add_placeholder($placeholders, 'tax',
      0);

    // Invoke other modules to set the item's 'note' field and sets a blank note
    // if no module has set it.
    \Drupal::moduleHandler()->invokeAll('alter_adjustment_xml', [$adjustment, $placeholders]);
    if (!array_key_exists('@note', $placeholders['patterns'])) {
      invoice_agent__add_placeholder($placeholders, 'note', '');
    }

    // Expand the return value with this item.
    $xml .= preg_replace($placeholders['patterns'], $placeholders['replacements'],
      file_get_contents(drupal_get_path('module', 'invoice_agent') . '/xml/xmlitem.xml'));
  }

  return $xml;
}

/**
 * Simply replaces the placeholders in xmlmain.xml.
 *
 * Parameters:
 * - array $placeholders
 *   The filled placeholder's array.
 *
 * Return (string). The full prepared XML string.
 */
function invoice_agent__generate_xml($placeholders) {
  return preg_replace($placeholders['patterns'], $placeholders['replacements'],
    file_get_contents(drupal_get_path('module', 'invoice_agent') . '/xml/xmlmain.xml'));
}

/**
 * Gets the payment mode from the order.
 *
 * Parameters:
 * - Order $order
 *   The loaded Order entity.
 * - string $invoice_type
 *   The required invoice type.
 *
 * Return (string). The payment gateway of the order.
 */
function invoice_agent__get_payment_mode(Order $order, $invoice_type) {

  // Sets the default value from configuration.
  $payment = \Drupal::config('invoice_agent.settings')
    ->get("{$invoice_type}_payment");

  // If the order is paided, then returns the gateway.
  if ($order->isPaid()) {
    // https://drupal.stackexchange.com/questions/250863/how-do-i-get-information-about-the-payment-method-from-the-order-object-programm
    // TODO: $order->get('payment_gateway') is empty. Why?
    // $payment = $order->get('payment_gateway')->first()->entity->label();
    // *** Temporary solution ***
    // Remove function invoice_agent__get_payment_gateway too if solved.
    $payment_machine = invoice_agent__get_payment_gateway($order->id());
    $payment = \Drupal::config('commerce_payment.commerce_payment_gateway.' . $payment_machine)
      ->get('label');
    // *** End ***.
  }
  return $payment;
}

/**
 * Create and save a Drupal\file\Entity\File object.
 */
function invoice_agent__create_file($filename, $field, $uid, $filemime, $content) {

  $settings = $field->getDataDefinition()->getSettings();
  $directory = \Drupal::token()
    ->replace("{$settings['uri_scheme']}://{$settings['file_directory']}");
  file_prepare_directory($directory, FILE_CREATE_DIRECTORY);

  $file = File::create([
    'uid' => $uid,
    'filename' => $filename,
    'filesize' => strlen($content),
    'uri' => "{$directory}/{$filename}",
    'filemime' => $filemime,
    'status' => FILE_STATUS_PERMANENT,
  ]);

  $file->save();
  file_put_contents($file->getFileUri(), $content);

  return $file->id();
}

/**
 * Create and save a Drupal\media\Entity\Media object.
 */
function invoice_agent__create_media($filename, $uid, $name, $filemime, $content) {

  $media = Media::create([
    'bundle' => 'invoice',
    'uid'    => $uid,
    'name'   => $name,
  ]);

  $media->set('field_media_invoice', [
    'target_id' => invoice_agent__create_file($filename, $media->field_media_invoice, $uid, $filemime, $content),
  ]);

  $media->save();
  return $media->id();
}
