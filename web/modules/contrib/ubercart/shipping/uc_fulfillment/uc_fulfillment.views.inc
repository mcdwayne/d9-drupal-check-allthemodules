<?php

/**
 * @file
 * Views hooks for Ubercart shipping.
 */

/**
 * Implements hook_views_data().
 */
function uc_fulfillment_views_data() {
  $data['uc_shipments']['table']['group'] = t('Shipment');

  // Allow base tables of shipments.
  $data['uc_shipments']['table']['base'] = [
    // This is the identifier field for the view.
    'field' => 'sid',
    'title' => t('Ubercart shipments'),
    'help' => t('Ubercart shipments contain shipping information for orders and can be related to orders and packages.'),
  ];

  // Shipment relationship for orders.
  $data['uc_orders']['shipments'] = [
    'relationship' => [
      'title' => t('Shipments'),
      'help' => t('Relate shipments to an order. This relationship will create one record for each shipment.'),
      'id' => 'standard',
      'base' => 'uc_shipments',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('shipment'),
    ],
  ];

  // Order relationship for shipments.
  $data['uc_shipments']['order'] = [
    'relationship' => [
      'title' => t('Order'),
      'help' => t('Relate an order to a shipment. Use this relationship to get order information for a shipment.'),
      'id' => 'standard',
      'base' => 'uc_orders',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('order'),
    ],
  ];

  // Expose packages to their shipments as a relationship.
  $data['uc_shipments']['packages'] = [
    'relationship' => [
      'title' => t('Packages'),
      'help' => t('Relate packages to a shipment. This relationship will create one record for each shipped package.'),
      'id' => 'standard',
      'base' => 'uc_packages',
      'base field' => 'sid',
      'relationship field' => 'sid',
      'label' => t('package'),
    ],
  ];

  // Shipment ID field.
  $data['uc_shipments']['sid'] = [
    'title' => t('Shipment ID'),
    'help' => t('The shipment ID.'),
    'field' => [
      'handler' => 'uc_fulfillment_handler_field_shipment_id',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'sid',
    ],
  ];

  // Order ID field.
  $data['uc_shipments']['order_id'] = [
    'title' => t('Order ID'),
    'help' => t('The order ID.'),
    'field' => [
      'id' => 'uc_order',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'order_id',
    ],
  ];

  // Carrier field.
  $data['uc_shipments']['carrier'] = [
    'title' => t('Carrier'),
    'help' => t('The company making the delivery.'),
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

  // Shipment transaction ID field.
  $data['uc_shipments']['transaction_id'] = [
    'title' => t('Transaction ID'),
    'help' => t("The carrier's shipment identifier."),
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

  // Shipment tracking number field.
  $data['uc_shipments']['tracking_number'] = [
    'title' => t('Tracking number'),
    'help' => t('The number used by the carrier to locate the shipment while it is in transit.'),
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

  $data['uc_shipments']['ship_date'] = [
    'title' => t('Ship date'),
    'help' => t('The date when the shipment left the origin address.'),
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

  $data['uc_shipments']['expected_delivery'] = [
    'title' => t('Expected delivery date'),
    'help' => t('The expected date of delivery.'),
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

  $data['uc_shipments']['changed'] = [
    'title' => t('Last modified'),
    'help' => t('The time the shipment was last modified.'),
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

  $data['uc_shipments']['cost'] = [
    'title' => t('Cost'),
    'help' => t('The cost of the shipment.'),
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

  $addresses = [
    'o' => t('Origin address'),
    'd' => t('Delivery address'),
  ];

  $fields = [
    'first_name' => t('First name'),
    'last_name' => t('Last name'),
    'company' => t('Company'),
    'street1' => t('Street address 1'),
    'street2' => t('Street address 2'),
    'city' => t('City'),
    'postal_code' => t('Postal code'),
  ];

  foreach ($addresses as $prefix => $address) {
    $group = t('Shipment: @address_type', ['@address_type' => $address]);

    foreach ($fields as $field => $label) {
      $data['uc_shipments'][$prefix . '_' . $field] = [
        'group' => $group,
        'title' => $label,
        'help' => t('The @field of the @address of the shipment.', ['@field' => mb_strtolower($label), '@address' => mb_strtolower($address)]),
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
    }
    // The uc_order module is required by uc_fulfillment, so
    // it is safe to use a uc_order handler.
    $data['uc_shipments'][$prefix . '_full_name'] = [
      'group' => $group,
      'title' => t('Full name'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('full name'), '@address' => mb_strtolower($address)]),
      'field' => [
        'additional fields' => [
          $prefix . '_first_name',
          $prefix . '_last_name',
        ],
        'handler' => 'uc_order_handler_field_order_fullname',
        'prefix' => $prefix,
      ],
    ];

    $data['uc_shipments_' . $prefix . '_countries']['table']['group'] = $group;
    $data['uc_shipments_' . $prefix . '_countries']['table']['join']['uc_shipments'] = [
      'table' => 'uc_countries',
      'left_field' => $prefix . '_country',
      'field' => 'country_id',
    ];
    $data['uc_shipments_' . $prefix . '_countries']['country_id'] = [
      'title' => t('ISO country code (numeric)'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('numeric ISO country code'), '@address' => mb_strtolower($address)]),
      'argument' => [
        'id' => 'numeric',
        'name field' => 'country_iso_code_2',
        'numeric' => TRUE,
        'validate type' => 'country_id',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
    ];
    $data['uc_shipments_' . $prefix . '_countries']['country_name'] = [
      'title' => t('Country'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('country name'), '@address' => mb_strtolower($address)]),
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
    $data['uc_shipments_' . $prefix . '_countries']['country_iso_code_2'] = [
      'title' => t('ISO country code (2 characters)'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('ISO country code'), '@address' => mb_strtolower($address)]),
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
    $data['uc_shipments_' . $prefix . '_countries']['country_iso_code_3'] = [
      'title' => t('ISO country code (3 characters)'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('ISO country code'), '@address' => mb_strtolower($address)]),
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

    $data['uc_shipments_' . $prefix . '_zones']['table']['group'] = $group;
    $data['uc_shipments_' . $prefix . '_zones']['table']['join']['uc_shipments'] = [
      'table' => 'uc_countries_zones',
      'left_field' => $prefix . '_zone',
      'field' => 'zone_id',
    ];
    $data['uc_shipments_' . $prefix . '_zones']['zone_name'] = [
      'title' => t('State/Province'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('state or province'), '@address' => mb_strtolower($address)]),
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
    $data['uc_shipments_' . $prefix . '_zones']['zone_code'] = [
      'title' => t('State/Province code'),
      'help' => t('The @field of the @address of the shipment.', ['@field' => t('state or province code'), '@address' => mb_strtolower($address)]),
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
  }

  // Expose packages.
  $data['uc_packages']['table']['group'] = t('Package');

  // Allow base tables of packages.
  $data['uc_packages']['table']['base'] = [
    'field' => 'sid', // This is the identifier field for the view.
    'title' => t('Ubercart packages'),
    'help' => t('Ubercart packages contain physical characteristics, tracking, and label information.  They can be related to their orders, shipments, and contents.'),
  ];

  // Expose packages to their order as a relationship.
  // Packages can exists without a shipment so this relationship may be useful.
  $data['uc_orders']['packages'] = [
    'relationship' => [
      'title' => t('Packages'),
      'help' => t('Relate packages to an order. This relationship will create one record for each package of an order.'),
      'id' => 'standard',
      'base' => 'uc_packages',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('package'),
    ],
  ];

  // Order relationship for packages.
  $data['uc_packages']['order'] = [
    'relationship' => [
      'title' => t('Order'),
      'help' => t('Relate an order to a package. Use this relationship to get order information for a package.'),
      'id' => 'standard',
      'base' => 'uc_orders',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('order'),
    ],
  ];

  // Expose packaged products to their package as a relationship.
  $data['uc_packages']['packaged_products'] = [
    'relationship' => [
      'title' => t('Products'),
      'help' => t('Relate packaged products to a package. This relationship will create one record for each packaged product.'),
      'id' => 'standard',
      'base' => 'uc_packaged_products',
      'base field' => 'package_id',
      'relationship field' => 'package_id',
      'label' => t('product'),
    ],
  ];

  // Expose shipments to their packages as a relationship.
  $data['uc_packages']['shipment'] = [
    'relationship' => [
      'title' => t('Shipment'),
      'help' => t('Relate shipment to package.  Use this relationship to get shipping information for the package.  Note that this relationship might not exist.'),
      'id' => 'standard',
      'base' => 'uc_shipments',
      'base field' => 'sid',
      'relationship field' => 'sid',
      'label' => t('shipment'),
    ],
  ];

  // Package ID field.
  // We don't redirect to the shipment page because
  // a package can exist without a shipment.
  $data['uc_packages']['package_id'] = [
    'title' => t('Package ID'),
    'help' => t('The package ID.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'sid',
    ],
  ];

  // Shipment ID field.
  $data['uc_packages']['sid'] = [
    'title' => t('Shipment ID'),
    'help' => t('The shipment ID.'),
    'field' => [
      'handler' => 'uc_fulfillment_handler_field_shipment_id',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'sid',
    ],
  ];

  // Order ID field.
  $data['uc_packages']['order_id'] = [
    'title' => t('Order ID'),
    'help' => t('The order ID.'),
    'field' => [
      'id' => 'uc_order',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'order_id',
    ],
  ];

  // Shipment type field.
  $data['uc_packages']['shipping_type'] = [
    'title' => t('Shipment type'),
    'help' => t('The basic type of shipment, e.g.: small package, freight, etc.'),
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

  // Package type.
  $data['uc_packages']['pkg_type'] = [
    'title' => t('Package type'),
    'help' => t('The type of packaging.'),
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

  // Package value.
  $data['uc_packages']['value'] = [
    'title' => t('Value'),
    'help' => t('The monetary value of the package contents.'),
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

  // Package tracking number field.
  $data['uc_packages']['tracking_number'] = [
    'title' => t('Tracking number'),
    'help' => t('The number used by the carrier to locate the package while it is in transit.'),
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

  // Package length field.
  $data['uc_packages']['length'] = [
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

  // Package width field.
  $data['uc_packages']['width'] = [
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

  // Package height field.
  $data['uc_packages']['height'] = [
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

  // Package weight field.
  $data['uc_packages']['weight'] = [
    'title' => t('Weight'),
    'help' => t('The physical weight of package.'),
    'field' => [
      'additional fields' => [
        'package_id',
      ],
      'handler' => 'uc_fulfillment_handler_field_package_weight',
      'click sortable' => FALSE,
    ],
  ];

  // Expose packaged products.
  $data['uc_packaged_products']['table']['group'] = t('Package: Product');

  // Expose packaged products to the ordered product as a relationship.
  // By using a relation and not expose fields directly we make sure that
  // when ordered products will be fieldable entities all their custom fields,
  // the one not stored in the schema, will get loaded.
  $data['uc_packaged_products']['uc_order_products'] = [
    'relationship' => [
      'title' => t('Ordered product'),
      'help' => t('Relate packaged product to the ordered product.'),
      'id' => 'standard',
      'base' => 'uc_order_products',
      'base field' => 'order_product_id',
      'relationship field' => 'order_product_id',
      'label' => t('ordered product'),
    ],
  ];

  // Packaged quantity field.
  $data['uc_packaged_products']['qty'] = [
    'title' => t('Quantity'),
    'help' => t('The quantity packaged.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}
