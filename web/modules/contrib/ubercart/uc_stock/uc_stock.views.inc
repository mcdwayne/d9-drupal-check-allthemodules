<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_stock_views_data() {
  $data['uc_product_stock']['table']['group'] = t('Stock');

  // Attach stock data to nodes.
  $data['uc_product_stock']['table']['join']['node_field_data'] = [
    'left_field' => 'nid',
    'field' => 'nid',
  ];

  $data['uc_product_stock']['sku'] = [
    'title' => t('SKU'),
    'help' => t('The model or SKU of the stock level.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['uc_product_stock']['active'] = [
    'title' => t('Active'),
    'help' => t('Whether or not stock is currently being tracked.'),
    'field' => [
      'id' => 'boolean',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['uc_product_stock']['stock'] = [
    'title' => t('Level'),
    'help' => t('The current stock level.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['uc_product_stock']['threshold'] = [
    'title' => t('Threshold'),
    'help' => t('The level at which a stock warning can be sent.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['uc_product_stock']['below_threshold'] = [
    'title' => t('Is below threshold'),
    'help' => t('Filter the node based on whether its stock level is below the threshold for the SKU.'),
    'filter' => [
      'id' => 'uc_stock_below_threshold',
      'label' => t('Is below threshold'),
      'type' => 'yes-no',
    ],
  ];

  return $data;
}
