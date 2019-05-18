<?php

namespace Drupal\commerce_inventory\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sync an Inventory Item's quantity from its provider.
 *
 * @Action(
 *   id = "commerce_inventory_item_sync_from_provider",
 *   label = @Translation("Sync quantity from the Provider."),
 * )
 */
class InventoryItemSyncFromProvider extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The Inventory Item entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   */
  protected $inventoryItemStorage;

  /**
   * Constructs a new InventoryItemSyncFromProvider action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->inventoryItemStorage = $entity_type_manager->getStorage('commerce_inventory_item');
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
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->inventoryItemStorage->syncQuantityFromProvider($entities);
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
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    return $object->access('edit', $account, $return_as_object);
  }

}
