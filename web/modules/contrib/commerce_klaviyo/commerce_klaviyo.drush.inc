<?php

/**
 * @file
 * Provides orders historical batch process.
 */

use Drupal\commerce_klaviyo\CustomerProperties;
use Drupal\commerce_klaviyo\OrderProperties;
use Drupal\commerce_klaviyo\Util\KlaviyoRequestInterface;

/**
 * Implements hook_drush_command().
 */
function commerce_klaviyo_drush_command() {
  $items = [];

  $items['commerce-klaviyo-import-orders'] = [
    'description' => 'Import historical orders to Klaviyo.',
    'options' => [
      'before-order-id' => [
        'description' => 'Apply only for orders with the id less than specified',
        'example-value' => 1000,
      ],
    ],
    'examples' => [
      'drush commerce-klaviyo-import-orders',
    ],
  ];
  return $items;
}

/**
 * Drush command callback for commerce-klaviyo-import-orders.
 */
function drush_commerce_klaviyo_import_orders() {
  $args = [drush_get_option('before-order-id')];
  $operations[] = ['commerce_klaviyo_sync_order_data', $args];
  batch_set([
    'operations' => $operations,
    'finished' => 'commerce_klaviyo_batch_finished',
  ]);
  drush_backend_batch_process();
}

/**
 * Defines a batch process that syncs commerce_klaviyo historical data.
 */
function commerce_klaviyo_sync_order_data($before_order_id, &$context) {
  $storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
  $entity_query = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('state', ['fulfillment', 'completed'], 'in');

  if ($before_order_id) {
    $entity_query->condition('order_id', $before_order_id, '<');
  }

  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_id'] = 0;
    $count = clone $entity_query;
    $context['sandbox']['max'] = $count->count()->execute();
  }

  $limit = 50;
  $ids = $entity_query
    ->condition('order_id', $context['sandbox']['current_id'], '>')
    ->sort('order_id', 'ASC')
    ->range(0, $limit)
    ->execute();

  $context['message'] = 'Running next batch of 50 orders';

  if ($ids) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $entities */
    $entities = $storage->loadMultiple($ids);
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = \Drupal::service('config.factory');
    /** @var \Drupal\commerce_klaviyo\Util\KlaviyoRequestInterface $klaviyo */
    $klaviyo = \Drupal::service('commerce_klaviyo.klaviyo_request');

    foreach ($entities as $id => $order) {
      // Sync order and all order items.
      if (!empty($order)) {
        $user = $order->getCustomer();

        if (!empty($user)) {
          $customer_properties = CustomerProperties::createFromUser($user);
        }
        elseif ($order->getEmail()) {
          $customer_properties = CustomerProperties::createFromOrder($order);
        }

        $properties = new OrderProperties($config_factory, $order);

        /** @var \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary */
        $orderTotalSummary = \Drupal::service('commerce_order.order_total_summary');
        $totals = $orderTotalSummary->buildTotals($order);
        $adjustments_total = 0;

        foreach ($totals['adjustments'] as $adjustment) {
          if ('promotion' == $adjustment['type']) {
            /** @var \Drupal\commerce_price\Price $amount */
            $amount = $adjustment['amount'];
            $adjustments_total += $amount->getNumber();
          }
        }

        $properties->setProperty('Discount Value', abs($adjustments_total));

        $timestamp = $order->getCreatedTime();
        $klaviyo->identify($customer_properties);
        $klaviyo->track(KlaviyoRequestInterface::PLACED_ORDER_EVENT, $customer_properties, $properties, $timestamp);
        $context['message'] = 'Synchronizing order ' . $id;
        $context['results'][] = $id;
      }

      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $id;
    }
  }
  else {
    $context['sandbox']['progress'] = $context['sandbox']['max'];
  }

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    drush_log('Exported historical orders to Klaviyo: ' . $context['sandbox']['progress'] . ' of ' . $context['sandbox']['max'] . ' (' . number_format($context['finished'] * 100, 2) . '%).');
  }
}

/**
 * Provides a batch summary result.
 */
function commerce_klaviyo_batch_finished($success, $results, $operations) {
  if ($success) {
    $message = \Drupal::translation()->formatPlural(
      count($results),
      'One order processed.', '@count orders processed.'
    );
  }
  else {
    $message = t('Finished with an error.');
  }
  drupal_set_message($message);
}
