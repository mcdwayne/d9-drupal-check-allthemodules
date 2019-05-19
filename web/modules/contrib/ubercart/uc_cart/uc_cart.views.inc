<?php

/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_views_data().
 */
function uc_cart_views_data() {
  // Cart items table.
  $data['uc_cart_products']['table']['group'] = t('Cart item');
  $data['uc_cart_products']['table']['base'] = [
    'field' => 'cart_item_id',
    'title' => t('Cart items'),
    'help' => t('Products in customer carts.'),
  ];

  $data['uc_cart_products']['nid'] = [
    'title' => t('Nid'),
    'help' => t('The node ID of a product in the cart.'),
    'field' => [
      'id' => 'node',
      'click sortable' => TRUE,
    ],
    'relationship' => [
      'title' => t('Node'),
      'help' => t('Relate cart item to node.'),
      'id' => 'standard',
      'base' => 'node',
      'field' => 'nid',
      'label' => t('node'),
    ],
    'argument' => [
      'id' => 'node_nid',
      'name field' => 'title',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['uc_cart_products']['cart_id'] = [
    'title' => t('Cart ID'),
    'help' => t('The ID of the cart (user ID for authenticated users, session ID for anonymous users).'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'argument' => [
      'id' => 'user_uid',
      'name field' => 'name',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'standard',
    ],
  ];

  $data['uc_cart_products']['qty'] = [
    'title' => t('Quantity'),
    'help' => t('The quantity to be ordered.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['uc_cart_products']['changed'] = [
    'title' => t('Last modified'),
    'help' => t('The time the cart item was last modified.'),
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
