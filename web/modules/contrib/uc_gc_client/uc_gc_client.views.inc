<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_gc_client_views_data() {

  $data['uc_orders']['gc_client'] = [
    'relationship' => [
      'title' => t('Go Cardless Client'),
      'help' => t('Relate GoCardless products to an order. This relationship will create one record for each GoCardless product ordered.'),
      'id' => 'standard',
      'base' => 'uc_gc_client',
      'base field' => 'ucid',
      'relationship field' => 'order_id',
      'label' => t('GoCardless Client'),
    ],
  ];

  $data['uc_gc_client']['table']['group'] = t('GoCardless Client');

  $data['uc_gc_client']['next_payment'] = [
    'title' => t('Next payment'),
    'help' => t('The time of next payment.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  $data['uc_gc_client']['type'] = [
    'title' => t('Payment type'),
    'help' => t('Either subscription or one-off payments.'),
    'field' => [
      'id' => 'gc_payment_type',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'standard',
    ],
  ];
  return $data;
}
