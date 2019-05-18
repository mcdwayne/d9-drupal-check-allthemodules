<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_payment_views_data() {
  $data['uc_orders']['payment_method'] = [
    'title' => t('Payment method'),
    'help' => t('The method of payment.'),
    'field' => [
      'id' => 'uc_payment_method',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'uc_payment_method',
    ],
  ];

  $data['uc_orders']['payments'] = [
    'relationship' => [
      'title' => t('Payments'),
      'help' => t('Relate payments to an order. This relationship will create one record for each payment received.'),
      'id' => 'standard',
      'base' => 'uc_payment_receipts',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('payments'),
    ],
  ];

  $data['uc_payment_receipts']['table']['group'] = t('Payment');

  $data['uc_payment_receipts']['method'] = [
    'title' => t('Payment method'),
    'help' => t('The method of payment.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  $data['uc_payment_receipts']['amount'] = [
    'title' => t('Amount'),
    'help' => t('The amount paid.'),
    'field' => [
      'id' => 'uc_price',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['uc_payment_receipts']['uid'] = [
    'title' => t('User'),
    'help' => t('Relate a payment to the user who made it.'),
    'relationship' => [
      'base' => 'users',
      'field' => 'uid',
      'id' => 'standard',
      'label' => t('user'),
    ],
  ];

  $data['uc_payment_receipts']['comment'] = [
    'title' => t('Comment'),
    'help' => t('Any remarks that were included with the payment.'),
    'field' => [
      'id' => 'xss',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  $data['uc_payment_receipts']['received'] = [
    'title' => t('Receipt date'),
    'help' => t('The date and time the payment was received.'),
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

  return $data;
}
