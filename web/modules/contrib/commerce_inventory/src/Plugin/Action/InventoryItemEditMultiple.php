<?php

namespace Drupal\commerce_inventory\Plugin\Action;

use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\core_extend\Plugin\Action\EntityEditMultiple;

/**
 * Edit multiple Inventory Items of an Inventory Location.
 *
 * @Action(
 *   id = "commerce_inventory_item_edit_multiple_at_location",
 *   label = @Translation("Edit multiple inventory items"),
 * )
 */
class InventoryItemEditMultiple extends EntityEditMultiple implements ContainerFactoryPluginInterface {

  /**
   * The current location to create inventory at.
   *
   * @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface|null
   */
  protected $location = NULL;

  /**
   * {@inheritdoc}
   */
  protected function getTempStoreCollectionId() {
    return 'commerce_inventory_item_edit_multiple_at_location';
  }

  /**
   * Inventory Location entity storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryLocationStorageInterface
   *   The Inventory Location entity storage instance.
   */
  protected function getInventoryLocationStorage() {
    return $this->entityTypeManager->getStorage('commerce_inventory_location');
  }

  /**
   * Get location entity.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryLocationInterface
   *   The Inventory location entity.
   */
  protected function getLocation() {
    if (is_null($this->location)) {
      $entity_id = NestedArray::getValue($this->configuration, ['arguments', 'commerce_inventory_location']);
      $storage = $this->getInventoryLocationStorage();
      if (!is_null($entity_id) && $entity = $storage->load($entity_id)) {
        $this->location = $entity;
      }
      elseif ($entity = $this->routeMatch->getParameter('commerce_inventory_location')) {
        if ($entity instanceof InventoryLocationInterface) {
          $this->location = $entity;
        }
        elseif ($entity = $storage->load($entity)) {
          $this->location = $entity;
        }
      }
    }
    return $this->location;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // Exit early if Location isn't set.
    if (is_null($this->getLocation())) {
      drupal_set_message(t('Location required.'), 'error');
      return NULL;
    }
    elseif (!$this->getLocation()->access('inventory_modify')) {
      drupal_set_message(t('Permission required to edit inventory of this location.'), 'error');
    }

    // Filter items of this location.
    $location_id = $this->getLocation()->id();
    $entities = array_filter(array_values($entities), function ($entity) use ($location_id) {
      return ($entity instanceof InventoryItemInterface && $entity->getLocationId() == $location_id);
    });

    // Override to a location-specific route.
    $this->pluginDefinition['confirm_form_route_name'] = $this->getLocation()->toUrl('inventory-edit-multiple')->getRouteName();

    parent::executeMultiple($entities);
  }

}
