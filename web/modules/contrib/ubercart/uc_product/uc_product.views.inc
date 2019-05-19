<?php

/**
 * @file
 * Views hooks for Ubercart products.
 */

/**
 * Implements hook_views_data().
 */
function uc_product_views_data() {
  $data['uc_products']['table']['group'] = t('Product');

  $data['uc_products']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'nid',
      'field' => 'nid',
    ],
// @todo Get revisions to work.
//    'node_revisions' => [
//      'left_field' => 'vid',
//      'field' => 'vid',
//    ],
  ];

  $data['uc_products']['model'] = [
    'title' => t('SKU'),
    'help' => t('The model number.'),
    'field' => [
      'id' => 'node',
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

  $data['uc_products']['cost'] = [
    'title' => t('Cost'),
    'help' => t('The cost of the product to the store.'),
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

  $data['uc_products']['price'] = [
    'title' => t('Price'),
    'help' => t('The price for which the product is sold.'),
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

  $data['uc_products']['display_price'] = [
    'title' => t('Display price'),
    'help' => t('The price for which the product is sold, altered for display (e.g. with inclusive taxes).'),
    'field' => [
      'id' => 'uc_product_display_price',
      'real field' => 'nid',
      'click sortable' => TRUE,
    ],
  ];

  $data['uc_products']['weight'] = [
    'title' => t('Weight'),
    'help' => t('The physical weight.'),
    'field' => [
      'additional fields' => [
        'field' => 'weight_units',
      ],
      'id' => 'uc_weight',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['uc_products']['length'] = [
    'title' => t('Length'),
    'help' => t('The physical length.'),
    'field' => [
      'additional fields' => [
        'field' => 'length_units',
      ],
      'id' => 'uc_length',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['uc_products']['width'] = [
    'title' => t('Width'),
    'help' => t('The physical width.'),
    'field' => [
      'additional fields' => [
        'field' => 'length_units',
      ],
      'id' => 'uc_length',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];
  $data['uc_products']['height'] = [
    'title' => t('Height'),
    'help' => t('The physical height.'),
    'field' => [
      'additional fields' => [
        'field' => 'length_units',
      ],
      'id' => 'uc_length',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['uc_products']['pkg_qty'] = [
    'title' => t('Package quantity'),
    'help' => t('The number of items that fit in the largest package.'),
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

  $data['uc_products']['shippable'] = [
    'title' => t('Shippable'),
    'help' => t('Is the product shippable?'),
    'field' => [
      'id' => 'boolean',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
  ];

  $data['uc_products']['is_product'] = [
    'title' => t('Is a product'),
    'help' => t('Check for the fields provided by the Ubercart product module.'),
    'group' => t('Content'),
    'filter' => [
      'id' => 'uc_product_is_product',
      'label' => t('Is a product'),
    ],
  ];

  $data['uc_products']['add_to_cart'] = [
    'title' => t('Add to cart'),
    'help' => t("Form to put the product in the customer's cart."),
    'group' => t('Product'),
    'field' => [
      'id' => 'uc_product_add_to_cart',
      'real field' => 'nid',
    ],
  ];

  $data['uc_products']['buy_it_now'] = [
    'title' => t('Buy it now'),
    'help' => t('A button to add a product to the cart without quantity or attribute fields.'),
    'group' => t('Product'),
    'field' => [
      'id' => 'uc_product_buy_it_now',
      'real field' => 'nid',
    ],
  ];

  return $data;
}
