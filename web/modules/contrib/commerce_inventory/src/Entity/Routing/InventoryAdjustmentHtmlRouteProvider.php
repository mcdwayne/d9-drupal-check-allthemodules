<?php

namespace Drupal\commerce_inventory\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for Inventory Adjustment entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class InventoryAdjustmentHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    return $collection;
  }

}
