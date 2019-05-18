<?php

namespace Drupal\commerce_inventory\Plugin\Action;

use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\core_extend\Plugin\Action\EntityEditMultiple;

/**
 * Add purchasable entity to a location's inventory.
 *
 * @Action(
 *   id = "purchasable_entity_create_inventory_item_action",
 *   label = @Translation("Add purchasable item to a location's inventory"),
 * )
 */
class CreateInventoryItem extends EntityEditMultiple implements ContainerFactoryPluginInterface {

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
    return 'commerce_inventory_item_create_multiple';
  }

  /**
   * Inventory Item entity storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   *   The Inventory Item entity storage instance.
   */
  protected function getInventoryItemStorage() {
    return $this->entityTypeManager->getStorage('commerce_inventory_item');
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
      if (!is_null($entity_id) && $entity = $this->getInventoryLocationStorage()->load($entity_id)) {
        $this->location = $entity;
      }
      elseif ($entity = $this->routeMatch->getParameter('commerce_inventory_location')) {
        if ($entity instanceof InventoryLocationInterface) {
          $this->location = $entity;
        }
        elseif ($entity = $this->getInventoryLocationStorage()->load($entity)) {
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
    /** @var \Drupal\commerce\PurchasableEntityInterface[] $entities */
    $entity_ids = array_map(function ($entity) {
      return $entity->id();
    }, $entities);
    $entity_type_id = current($entities)->getEntityTypeId();

    // Exit early if Location isn't set.
    if (is_null($this->getLocation())) {
      drupal_set_message(t('Location required.'), 'error');
      return NULL;
    }
    elseif (!$this->getLocation()->access('inventory_modify')) {
      drupal_set_message(t('Permission required to add inventory to this location.'), 'error');
    }
    // Use confirm form if commerce_inventory_item has required plugin fields.
    elseif ($this->getLocation()->isItemConfigurationRequired()) {
      $this->pluginDefinition['confirm_form_route_name'] = $this->getLocation()->toUrl('inventory-add-confirm')->getRouteName();

      $data = [
        'entity_ids' => $entity_ids,
        'entity_type_id' => $entity_type_id,
        'location_id' => $this->getLocation()->id(),
      ];

      $this->tempStore->set($this->currentUser->id(), $data);
    }
    else {
      // Create inventory items from purchasable entities and location.
      $inventory_items = $this->getInventoryItemStorage()->createMultiple($this->getLocation(), $entity_type_id, $entity_ids);
      // Save inventory items.
      $count = 0;
      foreach ($inventory_items as $inventory_item) {
        switch ($inventory_item->save()) {
          case 0:
            drupal_set_message(t('Error adding @item to @location', ['@item' => $inventory_item->label(), '@location' => $inventory_item->label()]), 'error');
            break;

          default:
            $count++;
            break;
        }
      }
      // Log action and notify user.
      $message = $this->formatPlural($count, 'Added 1 purchasable item to @location.', 'Added @count purchasable items to @location.', ['@location' => $this->location->label()]);
      \Drupal::logger('commerce_inventory')->notice($message->render());
      drupal_set_message($message);
      Cache::invalidateTags(['commerce_inventory_item_list']);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('use-for-inventory', $account, $return_as_object);
  }

}
