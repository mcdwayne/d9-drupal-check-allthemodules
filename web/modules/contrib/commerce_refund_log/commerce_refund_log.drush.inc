<?php

/**
 * @file
 * Drush commands for the Commerce Payment Refund Log module.
 */

/**
 * Implements hook_drush_command().
 */
function commerce_refund_log_drush_command() {
  $items = [];

  $items['commerce-refund-log-generate-missing'] = [
    'description' => 'Generate refund log entries for existing payments.',
    'examples' => [
      'drush commerce-refund-log-generate-missing',
    ],
  ];
  return $items;
}

/**
 * Drush command callback for commerce-refund-log-generate-missing.
 */
function drush_commerce_refund_log_generate_missing() {
  $operations = [
    ['drush_commerce_refund_log_generate_missing_process', []],
  ];
  batch_set(['operations' => $operations]);
  drush_backend_batch_process();
}

/**
 * Generates refund log entries for refunded payments, which don't have it.
 *
 * @param mixed $context
 *   Batch context.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_commerce_refund_log_generate_missing_process(&$context) {
  $entity_query = \Drupal::entityQuery('commerce_payment')
    ->condition('refunded_amount.number', '0', '>');

  if (empty($context['sandbox'])) {
    // Initiate multistep processing.
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_payment'] = 0;
    $count = clone $entity_query;
    $context['sandbox']['max'] = $count->count()->execute();
  }

  // Process the next 50 payments.
  $items_per_pass = 50;
  $ids = $entity_query
    ->condition('payment_id', $context['sandbox']['current_payment'], '>')
    ->sort('payment_id', 'ASC')
    ->range(0, $items_per_pass)
    ->execute();

  /** @var \Drupal\commerce_refund_log\RefundLogEntryStorageInterface $refund_log_storage */
  $refund_log_storage = \Drupal::entityTypeManager()->getStorage('commerce_refund_log_entry');
  /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
  $payments = \Drupal::entityTypeManager()
    ->getStorage('commerce_payment')
    ->loadMultiple($ids);

  foreach ($payments as $id => $payment) {
    if (!$refund_log_storage->loadMultipleByPayment($payment)) {
      // If the refunded payment has no refund log entries - create it.
      $refund_log_entry = $refund_log_storage->create([
        'payment_id' => $payment->id(),
        'amount' => $payment->getRefundedAmount(),
        'refund_time' => $payment->getCompletedTime(),
      ]);
      $refund_log_entry->save();
    }

    $context['sandbox']['progress']++;
    $context['sandbox']['current_payment'] = $id;
  }

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    $percentage = number_format($context['finished'] * 100, 2);
    drush_log('Processed ' . $context['sandbox']['progress'] . ' of ' . $context['sandbox']['max'] . ' (' . $percentage . '%).');
  }
}
