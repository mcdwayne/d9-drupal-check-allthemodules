<?php

/**
 * @file
 * Views hook implementations for Contacts Events.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_views_data().
 */
function contacts_events_views_data() {
  $data['commerce_order_item__state']['table'] = [
    'group' => new TranslatableMarkup('Order item'),
    'join' => [
      'commerce_order_item' => [
        'left_field' => 'order_item_id',
        'field' => 'entity_id',
        'extra' => [
          [
            'field' => 'deleted',
            'value' => 0,
            'numberic' => TRUE,
          ],
        ],
      ],
    ],
    'entity type' => 'commerce_order_item',
  ];

  $data['commerce_order_item__state']['state_value'] = [
    'group' => new TranslatableMarkup('Order item'),
    'title' => new TranslatableMarkup('State'),
    'field' => [
      'id' => 'field',
    ],
    'filter' => [
      'id' => 'standard',
    ],
    'entity field' => 'state',
  ];

  $data['commerce_order_item__confirmed']['table'] = [
    'group' => new TranslatableMarkup('Order item'),
    'join' => [
      'commerce_order_item' => [
        'left_field' => 'order_item_id',
        'field' => 'entity_id',
        'extra' => [
          [
            'field' => 'deleted',
            'value' => 0,
            'numberic' => TRUE,
          ],
        ],
      ],
    ],
    'entity type' => 'commerce_order_item',
  ];

  $data['commerce_order_item__confirmed']['state_value'] = [
    'group' => new TranslatableMarkup('Order item'),
    'title' => new TranslatableMarkup('Confirmed'),
    'field' => [
      'id' => 'field',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'entity field' => 'confirmed',
  ];

  return $data;
}
