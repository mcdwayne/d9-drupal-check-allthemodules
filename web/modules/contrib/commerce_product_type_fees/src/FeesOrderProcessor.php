<?php

namespace Drupal\commerce_product_type_fees;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Adjustment;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Applies custom fees to orders during the order refresh process.
 */
class FeesOrderProcessor implements OrderProcessorInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $order->setAdjustments([]);
    $hasCustomFee = FALSE;
    // Only variables should be pass by reference.
    $orderObj = &$order;
    $orderItems = $orderObj->getItems();
    foreach ($orderItems as $orderItem) {
      $product_variation = $orderItem->getPurchasedEntity();
      if (!empty($product_variation)) {
        $product = $product_variation->getProduct();
        $product_type = $product->bundle();
        if ($product_type) {
          $config = $this->configFactory->get('commerce_product_type_fees.settings');
          $custom_fees = $config->get($product_type . '_fees');
          if (isset($custom_fees) && !empty($custom_fees)) {
            // Fees is added into backend for this product types.
            $hasCustomFee = TRUE;
          }
          break;
        }
      }
    }

    if ($hasCustomFee) {
      // Apply all fees for that product type on order subtotal.
      $subtotal = $order->getSubtotalPrice();
      // Remove currency symbol.
      $t = explode(' ', $subtotal);
      $subtotalNumber = $t[0];
      $subtotalCurrency = $t[1];
      foreach ($custom_fees as $fee) {
        if (isset($fee['percentage']) && !empty($fee['percentage'])) {
          $percentage = $fee['percentage'] / 100;
          $new_adjustment = sprintf("%.2f", $subtotalNumber * $percentage);
          // Apply custom adjustment.
          $this->addFee($order, 'custom_fee', $fee['fee']['label'], new Price($new_adjustment, $subtotalCurrency));
        }
      }
    }
  }

  /**
   * Function to add an adjustment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param mixed $type
   *   The adjustment type.
   * @param mixed $label
   *   The adjustment label.
   * @param \Drupal\commerce_price\Price $price
   *   The adjustment price.
   */
  protected function addFee(OrderInterface $order, $type, $label, Price $price) {
    $order->addAdjustment(new Adjustment([
      'type' => $type,
      'label' => $label,
      'amount' => $price,
    ]));
  }

}
