<?php

namespace Drupal\commerce_inventory\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\core_extend\Entity\Routing\SettingsFormRouteTrait;
use Drupal\core_extend\Entity\Routing\StatusFormRouteTrait;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Inventory Location entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class InventoryLocationHtmlRouteProvider extends AdminHtmlRouteProvider {

  use SettingsFormRouteTrait {getSettingsFormRoute as traitSettingsFormRoute;
  }
  use StatusFormRouteTrait;

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    if ($status_form_route = $this->getStatusFormRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.status_form", $status_form_route);
    }

    if ($inventory_route = $this->getInventoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.inventory", $inventory_route);
    }

    if ($inventory_add_confirm_route = $this->getInventoryAddConfirmRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.inventory_add_confirm", $inventory_add_confirm_route);
    }

    if ($inventory_edit_multiple_route = $this->getInventoryEditMultipleRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.inventory_edit_multiple", $inventory_edit_multiple_route);
    }

    return $collection;
  }

  /**
   * Gets the Inventory route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryRoute(EntityTypeInterface $entity_type) {
    $inventory_item = $this->entityTypeManager->getDefinition('commerce_inventory_item');
    if ($entity_type->hasLinkTemplate('inventory') && $inventory_item->hasListBuilderClass() && $route = new Route($entity_type->getLinkTemplate('inventory'))) {

      $route_defaults['_entity_list'] = $inventory_item->id();
      $route_defaults['_title'] = "{$inventory_item->getCollectionLabel()}";

      $route_options['_admin_route'] = TRUE;
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';

      $route_requirements['_entity_access'] = 'commerce_inventory_location.inventory';
      $route_requirements['commerce_inventory_location'] = '\d+';

      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options)
        ->setRequirements($route_requirements);

      return $route;
    }
  }

  /**
   * Gets the Inventory - Add Confirm route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryAddConfirmRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('inventory-add-confirm') && $route = new Route($entity_type->getLinkTemplate('inventory-add-confirm'))) {

      $route_defaults['_entity'] = $entity_type->id();
      $route_defaults['_form'] = 'Drupal\commerce_inventory\Form\InventoryItemCreateConfirmForm';
      $route_defaults['_title'] = 'Add inventory';

      $route_options['_admin_route'] = TRUE;
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';

      $route_requirements['_entity_access'] = 'commerce_inventory_location.inventory_modify';
      $route_requirements['commerce_inventory_location'] = '\d+';

      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options)
        ->setRequirements($route_requirements);

      return $route;
    }
  }

  /**
   * Gets the Inventory - Edit Multiple route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getInventoryEditMultipleRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('inventory-edit-multiple') && $route = new Route($entity_type->getLinkTemplate('inventory-edit-multiple'))) {

      $route_defaults['_entity'] = $entity_type->id();
      $route_defaults['_form'] = 'Drupal\commerce_inventory\Form\InventoryItemEditMultipleForm';
      $route_defaults['_title'] = 'Edit inventory';

      $route_options['_admin_route'] = TRUE;
      $route_options['parameters']['commerce_inventory_location']['type'] = 'entity:commerce_inventory_location';

      $route_requirements['_entity_access'] = 'commerce_inventory_location.inventory_modify';
      $route_requirements['commerce_inventory_location'] = '\d+';

      $route
        ->setDefaults($route_defaults)
        ->setOptions($route_options)
        ->setRequirements($route_requirements);

      return $route;
    }
  }

}
