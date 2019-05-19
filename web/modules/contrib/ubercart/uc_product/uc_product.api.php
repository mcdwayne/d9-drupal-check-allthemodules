<?php

/**
 * @file
 * Hooks provided by the Product module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\node\NodeInterface;

/**
 * Make alterations to a specific variant of a product node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The product node to be altered.
 */
function hook_uc_product_alter(NodeInterface $node) {
  if (isset($node->data['attributes']) && is_array($node->data['attributes'])) {
    $options = _uc_cart_product_get_options($node);
    foreach ($options as $option) {
      $node->cost += $option['cost'];
      $node->price += $option['price'];
      $node->weight += $option['weight'];
    }

    $combination = [];
    foreach ($node->data['attributes'] as $aid => $value) {
      if (is_numeric($value)) {
        $attribute = uc_attribute_load($aid, $node->id(), 'product');
        if ($attribute && ($attribute->display == 1 || $attribute->display == 2)) {
          $combination[$aid] = $value;
        }
      }
    }
    ksort($combination);

    $model = db_query("SELECT model FROM {uc_product_adjustments} WHERE nid = :nid AND combination LIKE :combo", [':nid' => $node->id(), ':combo' => serialize($combination)])->fetchField();

    if (!empty($model)) {
      $node->model = $model;
    }
  }
}

/**
 * Returns a structured array representing the given product's description.
 *
 * Modules that add data to cart items when they are selected should display it
 * with this hook. The return values from each implementation will be
 * sent through to hook_uc_product_description_alter() implementations and then
 * all descriptions are rendered using drupal_render().
 *
 * @param $product
 *   Product. Usually one of the values of the array returned by
 *   uc_cart_get_contents().
 *
 * @return array
 *   A render array that can be fed into drupal_render().
 */
function hook_uc_product_description($product) {
  $description = [
    'attributes' => [
      '#product' => [
        '#type' => 'value',
        '#value' => $product,
      ],
      '#theme' => 'uc_product_attributes',
      '#weight' => 1,
    ],
  ];

  $desc =& $description['attributes'];

  // Cart version of the product has numeric attribute => option values so we
  // need to retrieve the right ones.
  $weight = 0;
  if (empty($product->order_id)) {
    foreach (_uc_cart_product_get_options($product) as $option) {
      if (!isset($desc[$option['aid']])) {
        $desc[$option['aid']]['#attribute_name'] = $option['attribute'];
        $desc[$option['aid']]['#options'] = [$option['name']];
      }
      else {
        $desc[$option['aid']]['#options'][] = $option['name'];
      }
      $desc[$option['aid']]['#weight'] = $weight++;
    }
  }
  else {
    foreach ((array) $product->data['attributes'] as $attribute => $option) {
      $desc[] = [
        '#attribute_name' => $attribute,
        '#options' => $option,
        '#weight' => $weight++,
      ];
    }
  }

  return $description;
}

/**
 * Alters the given product description.
 *
 * @param array $description
 *   Description array reference.
 * @param $product
 *   The product being described.
 */
function hook_uc_product_description_alter(&$description, $product) {
  $description['attributes']['#weight'] = 2;
}

/**
 * Notifies core of any SKUs your module adds to a given node.
 *
 * NOTE: DO NOT map the array keys, as the possibility for numeric SKUs exists,
 * and this will conflict with the behavior of
 * \Drupal::moduleHandler()->invokeAll(), specifically array_merge_recursive().
 *
 * Code lifted from uc_attribute.module.
 *
 * @param int $nid
 *   The product id.
 *
 * @return array
 *   Array of product SKUs for this product id.
 */
function hook_uc_product_models($nid) {
  // Get all the SKUs for all the attributes on this node.
  $models = db_query("SELECT DISTINCT model FROM {uc_product_adjustments} WHERE nid = :nid", [':nid' => $nid])->fetchCol();

  return $models;
}

/**
 * Lists node types which should be considered products.
 *
 * Trusts the duck philosophy of object identification: if it walks like a duck,
 * quacks like a duck, and has feathers like a duck, it's probably a duck.
 * Products are nodes with prices, SKUs, and everything else Ubercart expects
 * them to have.
 *
 * @return array
 *   Array of node type ids.
 */
function hook_uc_product_types() {
  return ['product_kit'];
}

/**
 * @} End of "addtogroup hooks".
 */
