<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository\Product;

use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * The default product type.
 */
class Product extends ProductBase {

  /**
   * Create new self with given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   The order item.
   *
   * @return \Drupal\commerce_paytrail\Repository\Product\Product
   *   The populated instance.
   */
  public static function createFromOrderItem(OrderItemInterface $item) {
    $object = new static();
    $object->setTitle($item->getTitle())
      ->setQuantity((int) $item->getQuantity())
      ->setPrice($item->getUnitPrice());

    if ($purchasedEntity = $item->getPurchasedEntity()) {
      $object->setItemId($purchasedEntity->id());
    }
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() : int {
    return 1;
  }

}
