<?php

/**
 * @file
 * Views hooks for Ubercart Cart Links statistics.
 */

/**
 * Implements hook_views_data().
 */
function uc_cart_links_views_data() {
  $data['uc_cart_link_clicks']['table']['group'] = t('Cart Links');
  $data['uc_cart_link_clicks']['table']['base'] = [
    'field' => 'cart_link_id',
    'title' => t('Cart Links'),
    'help' => t('Cart Links click statistics.'),
    'access query tag' => 'view cart links report',
  ];

  $data['uc_cart_link_clicks']['cart_link_id'] = [
    'title' => t('ID'),
    'help' => t('The cart link identifier.'),
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
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['uc_cart_link_clicks']['clicks'] = [
    'title' => t('Clicks'),
    'help' => t('The number of times this cart link was clicked.'),
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

  $data['uc_cart_link_clicks']['last_click'] = [
    'title' => t('Last click'),
    'help' => t('Date and time of the last click on this cart link.'),
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
