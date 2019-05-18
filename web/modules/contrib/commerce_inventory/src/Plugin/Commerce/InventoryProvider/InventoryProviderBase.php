<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider;

use Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface;
use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Inventory Provider plugins.
 */
abstract class InventoryProviderBase extends ContextAwarePluginBase implements ContainerFactoryPluginInterface, InventoryProviderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Inventory Adjustment entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface
   */
  protected $inventoryAdjustmentStorage;

  /**
   * The Commerce Inventory logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The inventory quantity on-hand manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityOnHandManager;

  /**
   * Constructs the Square object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets the Inventory Adjustment entity storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorageInterface|\Drupal\Core\Entity\EntityStorageInterface
   *   The Inventory Adjustment entity storage instance.
   */
  protected function getInventoryAdjustmentStorage() {
    if (is_null($this->inventoryAdjustmentStorage)) {
      $this->inventoryAdjustmentStorage = $this->entityTypeManager->getStorage('commerce_inventory_adjustment');
    }
    return $this->inventoryAdjustmentStorage;
  }

  /**
   * The inventory quantity on-hand manager.
   *
   * @return \Drupal\commerce_inventory\QuantityManagerInterface
   *   The inventory quantity on-hand manager instance.
   */
  protected function getQuantityOnHandManager() {
    if (is_null($this->quantityOnHandManager)) {
      $this->quantityOnHandManager = \Drupal::service('commerce_inventory.quantity_on_hand');
    }
    return $this->quantityOnHandManager;
  }

  /**
   * The Commerce Inventory logger channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The commerce inventory logger channel instance.
   */
  protected function getLogger() {
    if (is_null($this->logger)) {
      $this->logger = \Drupal::logger('commerce_inventory');
    }
    return $this->logger;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function onAdjustmentPreSave(InventoryAdjustmentInterface $adjustment) {}

  /**
   * {@inheritdoc}
   */
  public function onAdjustmentPostSave(InventoryAdjustmentInterface $adjustment) {}

  /**
   * {@inheritdoc}
   */
  public function isItemConfigurationRequired() {
    return ($this->pluginDefinition['item_configuration_required']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateItemConfiguration(InventoryItemInterface $inventory_item) {
    if ($this->isItemRemoteIdRequired()) {
      if (!($inventory_item->getRemoteId())) {
        return FALSE;
      }
    }
    if ($this->isLocationRemoteIdRequired()) {
      if (!$inventory_item->getLocation() || !($inventory_item->getLocation()->getRemoteId())) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function onItemPostCreate(InventoryItemInterface $inventory_item) {}

  /**
   * {@inheritdoc}
   */
  public function onItemPreSave(InventoryItemInterface $inventory_item) {}

  /**
   * {@inheritdoc}
   */
  public function onItemPostSave(InventoryItemInterface $inventory_item, $update = TRUE) {}

  /**
   * {@inheritdoc}
   */
  public function isItemRemoteIdRequired() {
    return ($this->pluginDefinition['item_remote_id_required']);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemRemoteQuantity(InventoryItemInterface $inventory_item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onLocationPostCreate(InventoryLocationInterface $inventory_location) {}

  /**
   * {@inheritdoc}
   */
  public function onLocationPreSave(InventoryLocationInterface $inventory_location) {}

  /**
   * {@inheritdoc}
   */
  public function onLocationPostSave(InventoryLocationInterface $inventory_location, $update = TRUE) {}

  /**
   * {@inheritdoc}
   */
  public function isLocationRemoteIdRequired() {
    return ($this->pluginDefinition['location_remote_id_required']);
  }

  /**
   * {@inheritdoc}
   */
  public function adjustProviderQuantity(InventoryItemInterface $inventory_item, $quantity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderQuantity(InventoryItemInterface $inventory_item) {
    return $this->getQuantityOnHandManager()->getQuantity($inventory_item->id());
  }

  /**
   * {@inheritdoc}
   */
  public function syncProviderQuantity(InventoryItemInterface $inventory_item, $update_provider_from_local = TRUE) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteIdOptions($q, $entity_type_id, array $contexts = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateRemoteId($value, $entity_type_id, array $contexts = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Override bundleFieldDefinitionsAlter instead.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function bundleFieldDefinitionsAlter(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->entityTypeManager = NULL;
    $this->inventoryAdjustmentStorage = NULL;
    $this->logger = NULL;
    $this->quantityOnHandManager = NULL;

    parent::__sleep();
  }

}
