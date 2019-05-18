<?php

namespace Drupal\erf_commerce;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Gets the registrations associated with the current Commerce order.
 *
 */
class OrderRegistrationManager {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderRegistrationManager service object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Finds registrations for a given commerce order.
   *
   * @param $order Drupal\commerce_order\Entity\OrderInterface
   *
   * @return Array An array of registrations, keyed by their ID.
   */
  public function getOrderRegistrations(OrderInterface $order) {
    $registrations = NULL;
    $order_item_ids = [];
    $order_items = $order->getItems();

    foreach ($order_items as $order_item) {
      $order_item_ids[] = $order_item->id();
    }

    $registrations = $this->entityTypeManager->getStorage('registration')->loadByProperties([
      'commerce_order_item_id' => $order_item_ids,
    ]);

    return $registrations;
  }
}
