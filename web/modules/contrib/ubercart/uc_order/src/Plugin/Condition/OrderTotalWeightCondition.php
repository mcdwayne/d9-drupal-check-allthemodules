<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\uc_order\OrderInterface;

/**
 * Provides 'Order total weight' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_products_weight",
 *   label = @Translation("Check an order's total weight"),
 *   description = @Translation("Compare the weight of all of the products, or the weight of just one type in the order."),
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
 *     "weight_units" = @ContextDefinition("string",
 *       label = @Translation("Unit of measurement"),
 *       list_options_callback = "weightUnitsOptions"
 *     ),
 *     "product_weight_value" = @ContextDefinition("float",
 *       label = @Translation("Product weight value")
 *     ),
 *     "product_weight_comparison" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       list_options_callback = "comparisonOptions"
 *     )
 *   }
 * )
 */
class OrderTotalWeightCondition extends OrderConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's total weight");
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
   * Weight units options callback.
   */
  public function weightUnitsOptions() {
    return [
      'lb' => $this->t('Pounds'),
      'kg' => $this->t('Kilograms'),
      'oz' => $this->t('Ounces'),
      'g' => $this->t('Grams'),
    ];
  }

  /**
   * Checks the weight of the order's products.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   * @param string[] $products
   *   The order products.
   * @param string $weight_units
   *   The weight units.
   * @param float $weight_value
   *   The weight value.
   * @param string $operator
   *   The comparison operator.
   *
   * @return bool
   *   TRUE if the order's weight meets the specified conditions.
   */
  protected function doEvaluate(OrderInterface $order, array $products, $weight_units, $weight_value, $operator) {
    $totals = ['all' => 0];
    $total = 0;
    foreach ($order->products as $product) {
      $unit_conversion = uc_weight_conversion($product->weight_units, $weight_units);
      $totals['all'] += $product->qty * $product->weight * $unit_conversion;
      $totals[$product->nid] = $product->qty * $product->weight * $unit_conversion;
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
    return $this->compareComparisonOptions($total, $operator, $weight_value);
  }

}
