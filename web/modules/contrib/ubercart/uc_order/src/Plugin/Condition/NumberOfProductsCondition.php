<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\uc_order\OrderInterface;

/**
 * Provides 'Count of order products' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_count_products",
 *   label = @Translation("Check an order's number of products"),
 *   category = @Translation("Order: Product"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "products" = @ContextDefinition("string",
 *       label = @Translation("Products"),
 *       list_options_callback = "productsOptions",
 *       multiple = TRUE,
 *       assignment_restriction = "input"
 *     ),
 *     "product_count_value" = @ContextDefinition("integer",
 *       label = @Translation("Product count value")
 *     ),
 *     "product_count_comparison" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       list_options_callback = "comparisonOptions"
 *     )
 *   }
 * )
 */
class NumberOfProductsCondition extends OrderConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's number of products");
  }

  /**
   * Product options callback.
   *
   * @return array
   *   An array of product SKUs.
   */
  public function productsOptions() {
    $options = ['all' => $this->t('- All products -')];
    $options += db_query('SELECT nid, model FROM {uc_products} ORDER BY model')->fetchAllKeyed();

    return $options;
  }

  /**
   * Checks that the order has the selected number of products.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   * @param \Drupal\node\NodeInterface[] $products
   *   The order products.
   * @param int $count
   *   The count.
   * @param string $operation
   *   The order.
   *
   * @return bool
   *   TRUE if the order has the selected number of products.
   */
  protected function doEvaluate(OrderInterface $order, array $products, $count, $operation) {
    $totals = ['all' => 0];
    $total = 0;
    foreach ($order->products as $product) {
      $totals['all'] += $product->qty;
      if (isset($totals[$product->nid])) {
        $totals[$product->nid] += $product->qty;
      }
      else {
        $totals[$product->nid] = $product->qty;
      }
    }
    if (in_array('all', $products)) {
      $total = $totals['all'];
    }
    else {
      foreach ($products as $product) {
        if (isset($totals[$product])) {
          $total += $totals[$product];
        }
      }
    }
    return $this->compareComparisonOptions($total, $operation, $count);
  }

}
