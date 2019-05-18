<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\uc_order\OrderInterface;

/**
 * Provides 'Order has specific product(s)' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_has_products",
 *   label = @Translation("Check an order's products"),
 *   category = @Translation("Order: Product"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "products" = @ContextDefinition("string",
 *       label = @Translation("Products"),
 *       list_options_callback = "hasProductsOptions",
 *       multiple = TRUE,
 *       assignment_restriction  = "input"
 *     ),
 *     "required" = @ContextDefinition("boolean",
 *       label = @Translation("Require all selected products"),
 *       description = @Translation("Select to require that order must contain all selected products. Otherwise, order must contain at least one of the selected products."),
 *       list_options_callback = "booleanOptions"
 *     ),
 *     "forbidden" = @ContextDefinition("boolean",
 *       label = @Translation("Forbid other products"),
 *       list_options_callback = "booleanOptions"
 *     )
 *   }
 * )
 */
class OrderHasProductsCondition extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's products");
  }

  /**
   * Options callback.
   *
   * @return array
   *   An array of product SKUs.
   */
  public function hasProductsOptions() {
    $options = [];
    $result = db_query('SELECT nid FROM {uc_products}');
    foreach ($result as $row) {
      $options += uc_product_get_models($row->nid, FALSE);
    }
    asort($options);

    return $options;
  }

  /**
   * Returns a TRUE/FALSE option set for boolean types.
   *
   * @return array
   *   A TRUE/FALSE options array.
   */
  public function booleanOptions() {
    return [
      0 => $this->t('False'),
      1 => $this->t('True'),
    ];
  }

  /**
   * Checks that the order has the selected combination of products.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   * @param \Drupal\node\NodeInterface[] $products
   *   TRUE if the product is required to be in the order.
   * @param bool $required
   *   TRUE if the product is required to be in the order.
   * @param bool $forbidden
   *   TRUE if the product is forbidden to be in the order.
   *
   * @return bool
   *   TRUE if the order has the selected combination of products.
   */
  protected function doEvaluate(OrderInterface $order, array $products, $required, $forbidden) {
    $order_products = [];
    foreach ($order->products as $product) {
      $order_products[] = $product->model;
    }
    $required_products = array_intersect($products, $order_products);
    if ($required) {
      $required_check = $required_products == $products;
    }
    else {
      $required_check = (bool) count($required_products);
    }
    if ($forbidden) {
      $forbidden_products = array_diff($order_products, $products);
      $forbidden_check = (bool) count($forbidden_products);
    }
    else {
      $forbidden_check = FALSE;
    }
    return $required_check && !$forbidden_check;
  }

}
