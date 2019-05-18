<?php

/**
 * @file
 * Views hook implementations.
 */

/**
 * Implements hook_views_data().
 */
function commerce_abandoned_carts_views_data() {

  $data['commerce_abandoned_carts']['table']['group'] = t('Abandoned Carts');
  $data['commerce_abandoned_carts']['table']['base'] = [
  // This is the identifier field for the view.
    'field' => 'order_id',
    'title' => t('Order ID'),
    'help' => t('Abandoned Cart Order ID.'),
    'weight' => -10,
  ];

  $data['commerce_abandoned_carts']['table']['join'] = [
    'commerce_order' => [
      'left_field' => 'order_id',
      'field' => 'order_id',
    ],
  ];

  $data['commerce_abandoned_carts']['status'] = [
    'title' => t('Email Sent'),
    'help' => t('Show if an Abandoned Cart email been sent for this order'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'boolean',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'boolean',
      // Override the generic field title, so that the filter uses a different
      // label in the UI.
      'label' => t('Published'),
      // Override the default BooleanOperator filter handler's 'type' setting,
      // to display this as a "Yes/No" filter instead of a "True/False" filter.
      'type' => 'yes-no',
      // Override the default Boolean filter handler's 'use_equal' setting, to
      // make the query use 'boolean_field = 1' instead of 'boolean_field <> 0'.
      'use_equal' => TRUE,
    ],
  ];

  $data['commerce_abandoned_carts']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('Timestamp when the email was sent.'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'date',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'date',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'date',
    ],
  ];
  return $data;
}
