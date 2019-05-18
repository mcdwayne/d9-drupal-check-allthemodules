<?php

namespace Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Entity\OrderType;

/**
 * Provides the Default rental instance selector.
 *
 * @RentalInstanceSelector(
 *   id = "default",
 *   name = @Translation("Default"),
 * )
 */
class DefaultSelector extends RentalInstanceSelectorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function selectOrderItemInstance(OrderItemInterface $order_item) {

    /** @var \Drupal\commerce_rental_reservation\RentalInstanceStorageInterface $instance_storage */
    $instance_storage = $this->entityTypeManager->getStorage('commerce_rental_instance');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $reservation_storage */
    $reservation_storage = $this->entityTypeManager->getStorage('commerce_rental_reservation');

    /** @var \Drupal\commerce_product\Entity\ProductVariation $product_variation */
    $variation_id = $order_item->getPurchasedEntity()->id();
    $instances = $instance_storage->loadMultipleByState(['available', 'maintenance', 'out'], $variation_id);

    $sort = [];
    /** @var \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface $instance */
    foreach ($instances as $key => $instance) {
      $sort['changed'][$key] = $instance->getChangedTime();
      $sort['state'][$key] = $this->workflowHelper->getStatePriority($instance->getState());
    }
    array_multisort($sort['state'], SORT_ASC, $sort['changed'], SORT_ASC, $instances);

    // whether or not the start and end dates should be based on the order period or the order items rental period.
    $order = $order_item->getOrder();
    $order_type = OrderType::load($order->bundle());
    $order_period_enabled = $order_type->getThirdPartySetting('commerce_rental_reservation', 'enable_order_period');
    $rental_start = $order_period_enabled ? strtotime($order->get('order_period')->value) : NULL;
    $rental_end = $order_period_enabled ? strtotime($order->get('order_period')->end_value) : NULL;

    // @TODO: Should the states that are considered 'on order' be configurable?
    // Try to return an instance that isnt tied to an order first.
    foreach ($instances as $instance) {
      $reserve_count = $reservation_storage->getQuery()
        ->condition('instance', $instance->id(), '=')
        ->condition('state', ['draft','active'], 'IN')
        ->count()->execute();
      if ($reserve_count == 0) {
        return $instance;
      }
    }

    // Try to return an instance that is not tied to an active reservation with overlapping rental period.
    foreach ($instances as $instance) {
      $reservations = $reservation_storage->loadByProperties([
        'state' => ['draft','active'],
        'instance' => $instance->id(),
      ]);
      foreach ($reservations as $reservation) {
        if (!$reservation->get('period')->isEmpty()) {
          $res_start = strtotime($reservation->get('period')->value);
          $res_end = strtotime($reservation->get('period')->end_value);
          if (!($res_start <= $rental_end && $res_end >= $rental_start)) {
            return $instance;
          }
        }
      }
    }

    // Couldn't find an available instance
    return NULL;
  }
}