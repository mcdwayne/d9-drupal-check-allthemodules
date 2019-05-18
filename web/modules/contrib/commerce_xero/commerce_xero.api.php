<?php

/**
 * @file
 * Describe hooks provided by the Commerce Xero module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the Commerce Xero processor plugin definitions.
 *
 * @param array &$plugins
 *   An array of all the existing plugin definitions, based by reference, and
 *   keyed by the plugin ID.
 */
function hook_commerce_xero_processor_plugin_info_alter(array &$plugins) {
  $plugins['someplugin']['class'] = '\Drupal\my_module\Plugin\CommerceXero\processor\NewPlugin';
}

/**
 * Alters the Commerce Xero data_type plugin definitions.
 *
 * @param array &$plugins
 *   An array of all the existing plugin definitions, based by reference, and
 *   keyed by the plugin ID.
 */
function hook_commerce_xero_data_type_plugin_info_alter(array &$plugins) {
  $plugins['someplugin']['class'] = '\Drupal\my_module\Plugin\CommerceXero\type\NewPlugin';
}

/**
 * Alters the data type created when a payment is made.
 *
 * @param \Drupal\Core\TypedData\ComplexDataInterface $data
 *   The Xero data structure such as xero_bank_transaction or xero_invoice.
 * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
 *   The commerce_payment entity.
 * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategy $strategy
 *   The commerce_xero strategy entity.
 *
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function hook_commerce_xero_data_alter(\Drupal\Core\TypedData\ComplexDataInterface $data, \Drupal\commerce_payment\Entity\PaymentInterface $payment, \Drupal\commerce_xero\Entity\CommerceXeroStrategy $strategy) {
  if ($strategy->get('xero_type') === 'xero_bank_transaction' &&
      $strategy->get('revenue_account') === '200') {
    /** @var \Drupal\xero\Plugin\DataType\BankTransaction $data */
    $net30 = time() + 2592000;
    $due = date('Y-m-d', $net30);
    $data->set('DueDate', $due);
  }
}

/**
 * Alters the data type after it has been processed in the execution state.
 *
 * EXECUTION_STATE refers to one of "immediate", "process" or "send".
 *
 * @param \Drupal\Core\TypedData\ComplexDataInterface $data
 *   The xero data.
 * @param array $context
 *   An associative array with the following keys and values:
 *     - payment \Drupal\commerce_payment\Entity\PaymentInterface
 *     - strategy \Drupal\commerce_xero\Entity\CommerceXeroStrategy
 *     - success bool.
 *
 * @see \hook_commerce_xero_process_alter()
 */
function hook_commerce_xero_process_EXECUTION_STATE_alter(\Drupal\Core\TypedData\ComplexDataInterface $data, array $context) {
  // Do something after all processor plugins for a given execution state have
  // run for the given execution state only.
}

/**
 * Alters the data type after it has been processed.
 *
 * This is only useful if you want to act on _every_ execution state.
 *
 * @param \Drupal\Core\TypedData\ComplexDataInterface $data
 *   The xero data.
 * @param array $context
 *   An associative array with the following keys and values:
 *     - payment \Drupal\commerce_payment\Entity\PaymentInterface
 *     - strategy \Drupal\commerce_xero\Entity\CommerceXeroStrategy
 *     - success bool.
 *
 * @see \hook_commerce_xero_process_EXECUTION_STATE_alter()
 */
function hook_commerce_xero_process_alter(\Drupal\Core\TypedData\ComplexDataInterface $data, array $context) {
  // Do something after all processor plugins for a given execution state have
  // run for any execution state.
}

/**
 * @} End of "addtogroup hooks".
 */
