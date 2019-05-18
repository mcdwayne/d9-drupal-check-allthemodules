<?php

namespace Drupal\commerce_inventory\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\core_extend\Controller\EntityController;
use Drupal\core_extend\Entity\Routing\StatusFormRouteTrait;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Inventory Item entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class InventoryItemHtmlRouteProvider extends AdminHtmlRouteProvider {

  use StatusFormRouteTrait;

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($adjust_form_route = $this->getAdjustFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.adjust_form", $adjust_form_route);
    }

    if ($status_form_route = $this->getStatusFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.status_form", $status_form_route);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCanonicalRoute($entity_type)) {
      $route_options = $route->getOptions() ?: [];
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';
      $route
        ->setDefault('_title_callback', EntityController::class . '::title')
        ->setOptions($route_options)
        ->setRequirement('_location_owns_entity', 'commerce_inventory_item');
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      $route_options = $route->getOptions() ?: [];
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';
      $route
        ->setDefault('_title_callback', EntityController::class . '::editTitle')
        ->setOptions($route_options)
        ->setRequirement('_location_owns_entity', 'commerce_inventory_item');
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      $route_options = $route->getOptions() ?: [];
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';
      $route
        ->setDefault('_title_callback', EntityController::class . '::deleteTitle')
        ->setOptions($route_options)
        ->setRequirement('_location_owns_entity', 'commerce_inventory_item');
      return $route;
    }
  }

  /**
   * Gets the adjustment-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAdjustFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('adjust-form')) {
      $entity_type_id = $entity_type->id();
      $operation = 'adjust';
      $route = new Route($entity_type->getLinkTemplate('adjust-form'));

      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          '_title' => 'Adjust quantity',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.adjust")
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

}
