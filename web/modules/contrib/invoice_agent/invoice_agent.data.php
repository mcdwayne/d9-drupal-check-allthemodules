<?php

/**
 * @file
 * Database functions for Invoice Agent.
 */

/**
 * Gets the new orders from the database.
 */
function invoice_agent__get_new_orders() {
  return \Drupal::database()->select('commerce_order', 't')
    ->fields('t', ['order_id'])
    ->isNull('invoice_status')
    ->condition('state', 'completed')
    ->orderBy('order_id')
    ->execute()
    ->fetchAll();
}

/**
 * Gets the orders from the database by invoice_status.
 */
function invoice_agent__get_orders_by_invoice_status($invoice_status) {
  return \Drupal::database()->select('commerce_order', 't')
    ->fields('t', ['order_id'])
    ->condition('invoice_status', $invoice_status)
    ->condition('state', 'completed')
    ->orderBy('order_id')
    ->execute()
    ->fetchAll();
}

/**
 * Gets the order's invoice_status by order_id.
 */
function invoice_agent__get_invoice_status($order_id) {
  $result = \Drupal::database()->select('commerce_order', 't')
    ->fields('t', ['invoice_status'])
    ->condition('order_id', $order_id)
    ->execute()
    ->fetchAll();
  return reset($result)->invoice_status;
}

/**
 * Sets the order's invoice_status by order_id.
 */
function invoice_agent__set_invoice_status($order_id, $invoice_status) {
  \Drupal::database()->update('commerce_order')
    ->fields(['invoice_status' => $invoice_status])
    ->condition('order_id', $order_id)
    ->execute();
}

/**
 * Gets the order's first payment gateway.
 */
function invoice_agent__get_payment_gateway($order_id) {
  $result = \Drupal::database()->select('commerce_payment', 't')
    ->fields('t', ['payment_gateway'])
    ->condition('order_id', $order_id)
    ->range(0, 1)
    ->execute()
    ->fetchAll();
  return reset($result)->payment_gateway;
}
