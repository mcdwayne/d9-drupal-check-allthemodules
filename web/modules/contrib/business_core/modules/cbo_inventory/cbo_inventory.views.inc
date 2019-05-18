<?php

/**
 * @file
 * Provide views data for subinventory.module.
 */

/**
 * Implements hook_views_data().
 */
function cbo_inventory_views_data() {
  $data['subinventory_quantity']['table']['group'] = t('Subinventory quantities');

  $data['subinventory_quantity']['table']['base'] = [
    'title' => t('Subinventory quantities'),
  ];

  $data['subinventory_quantity']['sid'] = [
    'title' => t('Subinventory'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'subinventory',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('Subinventory'),
      'base' => 'subinventory',
      'base field' => 'sid',
      'id' => 'standard',
    ],
  ];

  $data['subinventory_quantity']['iid'] = [
    'title' => t('Item'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('Item'),
      'base' => 'item',
      'base field' => 'iid',
      'id' => 'standard',
    ],
  ];

  $data['subinventory_quantity']['table']['join'] = [
    'subinventory' => [
      'left_field' => 'sid',
      'field' => 'sid',
    ],
    'item' => [
      'left_field' => 'iid',
      'field' => 'iid',
    ],
  ];

  $data['subinventory_quantity']['quantity'] = [
    'title' => t('Quantity'),
    'field' => [
      'id' => 'numeric',
      'float' => TRUE,
      'click sortable' => TRUE,
    ],
  ];

  return $data;
}
