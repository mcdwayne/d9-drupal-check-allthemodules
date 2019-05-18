<?php

namespace Drupal\uc_order;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the uc_order entity type.
 */
class OrderViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['uc_orders']['order_status']['filter']['id'] = 'uc_order_status';

    $data['uc_orders']['uid']['help'] = $this->t('The user ID that the order belongs to.');
    $data['uc_orders']['uid']['filter']['id'] = 'user_name';
    $data['uc_orders']['uid']['relationship']['title'] = $this->t('Customer');
    $data['uc_orders']['uid']['relationship']['help'] = $this->t('Relate an order to the user who placed it.');
    $data['uc_orders']['uid']['relationship']['label'] = $this->t('customer');

    $data['uc_orders']['order_total']['field']['id'] = 'uc_price';

    $data['uc_orders']['actions'] = [
      'title' => $this->t('Actions'),
      'help' => $this->t('Clickable links to actions a user may perform on an order.'),
      'field' => [
        'id' => 'uc_order_actions',
        'real field' => 'order_id',
        'click sortable' => FALSE,
      ],
    ];

    $data['uc_orders']['billing_country']['filter']['id'] = 'in_operator';
    $data['uc_orders']['billing_country']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::countryOptionsCallback';
    $data['uc_orders']['delivery_country']['filter']['id'] = 'in_operator';
    $data['uc_orders']['delivery_country']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::countryOptionsCallback';

    $data['uc_orders']['billing_country_name'] = [
      'title' => $this->t('Billing country name'),
      'help' => $this->t('The country name where the bill will be sent.'),
      'field' => [
        'id' => 'uc_country',
        'real field' => 'billing_country',
      ],
    ];

    $data['uc_orders']['delivery_country_name'] = [
      'title' => $this->t('Delivery country name'),
      'help' => $this->t('The country name of the delivery location.'),
      'field' => [
        'id' => 'uc_country',
        'real field' => 'delivery_country',
      ],
    ];

    $data['uc_orders']['billing_zone']['filter']['id'] = 'in_operator';
    $data['uc_orders']['billing_zone']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::zoneOptionsCallback';
    $data['uc_orders']['delivery_zone']['filter']['id'] = 'in_operator';
    $data['uc_orders']['delivery_zone']['filter']['options callback'] = 'Drupal\uc_country\Controller\CountryController::zoneOptionsCallback';

    $data['uc_orders']['billing_zone_name'] = [
      'title' => $this->t('Billing state/province name'),
      'help' => $this->t('The state/zone/province ID where the bill will be sent.'),
      'field' => [
        'id' => 'uc_zone',
        'real field' => 'billing_zone',
        'additional fields' => [
          'country' => [
            'field' => 'billing_country',
          ],
        ],
      ],
    ];

    $data['uc_orders']['delivery_zone_name'] = [
      'title' => $this->t('Delivery state/province name'),
      'help' => $this->t('The state/zone/province ID of the delivery location.'),
      'field' => [
        'id' => 'uc_zone',
        'real field' => 'delivery_zone',
        'additional fields' => [
          'country' => [
            'field' => 'delivery_country',
          ],
        ],
      ],
    ];

    $data['uc_orders']['billing_full_name'] = [
      'title' => $this->t('Billing full name'),
      'help' => $this->t('The full name of the person paying for the order.'),
      'field' => [
        'id' => 'uc_order_full_name',
        'real field' => 'billing_first_name',
        'additional fields' => [
          'last_name' => [
            'field' => 'billing_last_name',
          ],
        ],
      ],
    ];

    $data['uc_orders']['delivery_full_name'] = [
      'title' => $this->t('Delivery full name'),
      'help' => $this->t('The full name of the person receiving shipment.'),
      'field' => [
        'id' => 'uc_order_full_name',
        'real field' => 'delivery_first_name',
        'additional fields' => [
          'last_name' => [
            'field' => 'delivery_last_name',
          ],
        ],
      ],
    ];

    $data['uc_orders']['total_weight'] = [
      'title' => $this->t('Total weight'),
      'help' => $this->t('The physical weight of all the products (weight * quantity) in the order.'),
      'real field' => 'weight',
      'field' => [
        'handler' => 'uc_order_handler_field_order_weight_total',
        'additional fields' => [
          'order_id' => 'order_id',
        ],
      ],
    ];

    // Expose the uid as a relationship to users.
    $data['users_field_data']['uc_orders'] = [
      'title' => $this->t('Orders'),
      'help' => $this->t('Relate a user to the orders they have placed. This relationship will create one record for each order placed by the user.'),
      'relationship' => [
        'title' => $this->t('Order'),
        'label' => $this->t('Order'),
        'base' => 'uc_orders',
        'base field' => 'uid',
        'relationship field' => 'uid',
        'id' => 'standard',
      ],
    ];

    // Ordered products.
    // Get the standard EntityAPI Views data table.
    // $data['uc_order_products'] =  entity_views_table_definition('uc_order_product');
    // // Remove undesirable fields
    // foreach (['data'] as $bad_field) {
    //   if (isset($data['uc_order_products'][$bad_field])) {
    //     unset($data['uc_order_products'][$bad_field]);
    //   }
    // }
    // // Fix incomplete fields.
    // $data['uc_order_products']['weight_units']['title'] = $this->t('Weight units');

    $data['uc_order_products']['table']['group'] = $this->t('Ordered product');
    $data['uc_order_products']['table']['base'] = [
      'field' => 'order_product_id',
      'title' => $this->t('Ordered products'),
      'help' => $this->t('Products that have been ordered in your Ubercart store.'),
    ];

    // Expose products to their orders as a relationship.
    $data['uc_orders']['products'] = [
      'relationship' => [
        'title' => $this->t('Products'),
        'help' => $this->t('Relate products to an order. This relationship will create one record for each product ordered.'),
        'id' => 'standard',
        'base' => 'uc_order_products',
        'base field' => 'order_id',
        'relationship field' => 'order_id',
        'label' => $this->t('products'),
      ],
    ];

    // Expose nodes to ordered products as a relationship.
    $data['uc_order_products']['nid'] = [
      'title' => $this->t('Nid'),
      'help' => $this->t('The nid of the ordered product. If you need more fields than the nid: Node relationship'),
      'relationship' => [
        'title' => $this->t('Node'),
        'help' => $this->t('Relate product to node.'),
        'id' => 'standard',
        'base' => 'node',
        'field' => 'nid',
        'label' => $this->t('node'),
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'node_nid',
      ],
      'field' => [
        'id' => 'node',
      ],
    ];

    // Expose orders to ordered products as a relationship.
    $data['uc_order_products']['order_id'] = [
      'title' => $this->t('Order ID'),
      'help' => $this->t('The order ID of the ordered product. If you need more fields than the order ID: Order relationship'),
      'relationship' => [
        'title' => $this->t('Order'),
        'help' => $this->t('Relate product to order.'),
        'id' => 'standard',
        'base' => 'uc_orders',
        'field' => 'order_id',
        'label' => $this->t('order'),
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'field' => [
        'id' => 'uc_order',
      ],
    ];

    $data['uc_order_products']['model'] = [
      'title' => $this->t('SKU'),
      'help' => $this->t('The product model/SKU.'),
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

    $data['uc_order_products']['qty'] = [
      'title' => $this->t('Quantity'),
      'help' => $this->t('The quantity ordered.'),
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

    $data['uc_order_products']['price'] = [
      'title' => $this->t('Price'),
      'help' => $this->t('The price paid for one product.'),
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

    $data['uc_order_products']['total_price'] = [
      'title' => $this->t('Total price'),
      'help' => $this->t('The price paid for all the products (price * quantity).'),
      'real field' => 'price',
      'field' => [
        'handler' => 'uc_order_handler_field_money_total',
        'click sortable' => TRUE,
      ],
      'sort' => [
        'handler' => 'uc_order_handler_sort_total',
      ],
      'filter' => [
        'handler' => 'uc_order_handler_filter_total',
      ],
    ];

    $data['uc_order_products']['cost'] = [
      'title' => $this->t('Cost'),
      'help' => $this->t('The cost to the store for one product.'),
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

    $data['uc_order_products']['total_cost'] = [
      'title' => $this->t('Total cost'),
      'help' => $this->t('The cost to the store for all the products (cost * quantity).'),
      'real field' => 'cost',
      'field' => [
        'handler' => 'uc_order_handler_field_money_total',
        'click sortable' => TRUE,
      ],
      'sort' => [
        'handler' => 'uc_order_handler_sort_total',
      ],
      'filter' => [
        'handler' => 'uc_order_handler_filter_total',
      ],
    ];

    $data['uc_order_products']['weight'] = [
      'title' => $this->t('Weight'),
      'help' => $this->t('The physical weight of one product.'),
      'field' => [
        'additional fields' => [
          'weight_units' => [
            'field' => 'weight_units',
          ],
        ],
        'id' => 'uc_weight',
      ],
    ];

    $data['uc_order_products']['total_weight'] = [
      'title' => $this->t('Total weight'),
      'help' => $this->t('The physical weight of all the products (weight * quantity).'),
      'real field' => 'weight',
      'field' => [
        'additional fields' => [
          'weight_units' => [
            'field' => 'weight_units',
          ],
        ],
        'handler' => 'uc_order_handler_field_weight_total',
      ],
    ];

    $data['uc_order_products']['title'] = [
      'title' => $this->t('Title'),
      'help' => $this->t('The title of the product.'),
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

    // Order comments table.
    // @todo Refactor this into a groupwise max relationship.
    $data['uc_order_comments']['table']['group'] = $this->t('Order comments');
    $data['uc_order_comments']['table']['join'] = [
      'uc_orders' => [
        'left_field' => 'order_id',
        'field' => 'order_id',
      ],
      'uc_order_products' => [
        'left_table' => 'uc_orders',
        'left_field' => 'order_id',
        'field' => 'order_id',
      ],
    ];

    $data['uc_order_comments']['message'] = [
      'title' => $this->t('Comment'),
      'help' => $this->t('The comment body.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
    ];

    // Support for any module's line item, if new modules defines other line
    // items the views cache will have to be rebuilt.
    // Although new line items views support should be defined on each module,
    // I don't think this wider apporach would harm. At most, it will duplicate
    // line items.
    $line_items = [];
    $definitions = \Drupal::service('plugin.manager.uc_order.line_item')->getDefinitions();
    foreach ($definitions as $line_item) {
      if (!in_array($line_item['id'], ['subtotal', 'tax_subtotal', 'total', 'generic']) && $line_item['stored']) {
        $line_items[$line_item['id']] = $line_item['title'];
      }
    }
    foreach ($line_items as $line_item_id => $line_item_desc) {
      $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_orders'] = [
        'table' => 'uc_order_line_items',
        'left_field' => 'order_id',
        'field' => 'order_id',
        'extra' => [
          [
            'field' => 'type',
            'value' => $line_item_id,
          ],
        ],
      ];
      $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_order_products'] = $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_orders'];
      $data['uc_order_line_items_' . $line_item_id]['table']['join']['uc_order_products']['left_table'] = 'uc_orders';

      $data['uc_order_line_items_' . $line_item_id]['table']['group'] = $this->t('Order: Line item');
      $data['uc_order_line_items_' . $line_item_id]['title'] = [
        'title' => $this->t('@line_item title', ['@line_item' => $line_item_desc]),
        'help' => $this->t('@line_item order line item', ['@line_item' => $line_item_desc]),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'string',
        ],
      ];

      $data['uc_order_line_items_' . $line_item_id]['amount'] = [
        'title' => $this->t('@line_item amount', ['@line_item' => $line_item_desc]),
        'help' => $this->t('@line_item order line item', ['@line_item' => $line_item_desc]),
        'field' => [
          'id' => 'uc_price',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'numeric',
        ],
      ];
    }

    return $data;
  }

}
