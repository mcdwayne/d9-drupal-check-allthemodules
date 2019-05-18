<?php

namespace Drupal\commerce_inventory\Entity\Storage;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\commerce_inventory\InventoryProviderManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the storage handler class for Commerce Inventory Item entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce Inventory Item entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryItemStorage extends CommerceContentEntityStorage implements InventoryItemStorageInterface {

  /**
   * The Inventory Location entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryLocationStorageInterface
   */
  protected $inventoryLocationStorage;

  /**
   * The inventory provider manager.
   *
   * @var \Drupal\commerce_inventory\InventoryProviderManager
   */
  protected $inventoryProviderManager;

  /**
   * Loaded inventory provider instances, keyed by bundle.
   *
   * @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface[]
   */
  protected $inventoryProviders = [];

  /**
   * The Commerce Log entity storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructs a SqlContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_inventory\InventoryProviderManager $inventory_provider_manager
   *   The inventory provider manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EventDispatcherInterface $event_dispatcher, InventoryProviderManager $inventory_provider_manager) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $memory_cache, $event_dispatcher);

    $this->inventoryProviderManager = $inventory_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.commerce_inventory_provider')
    );
  }

  /**
   * Gets the location storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryLocationStorageInterface
   *   The Inventory Location storage instance.
   */
  protected function getLocationStorage() {
    if (is_null($this->inventoryLocationStorage)) {
      $this->inventoryLocationStorage = $this->entityManager->getStorage('commerce_inventory_location');;
    }
    return $this->inventoryLocationStorage;
  }

  /**
   * Gets the Commerce Log entity storage.
   *
   * @return \Drupal\commerce_log\LogStorageInterface
   *   The Commerce Log entity storage instance.
   */
  protected function getLogStorage() {
    if (is_null($this->logStorage)) {
      $this->logStorage = $this->entityManager->getStorage('commerce_log');
    }
    return $this->logStorage;
  }

  /**
   * Loads a provider by bundle ID.
   *
   * @param string $bundle
   *   An entity's bundle ID.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface
   *   The bundle's inventory provider instance.
   */
  protected function getProvider($bundle) {
    if (!array_key_exists($bundle, $this->inventoryProviders)) {
      $this->inventoryProviders[$bundle] = $this->inventoryProviderManager->createInstance($bundle);
    }
    return $this->inventoryProviders[$bundle];
  }

  /**
   * Check that the id is a valid entity id.
   *
   * @param mixed $id
   *   The ID to validate.
   *
   * @return bool
   *   Whether the id is valid or not.
   */
  protected static function isValidId($id) {
    return (is_int($id) || is_string($id));
  }

  /**
   * {@inheritdoc}
   */
  public function getItemQuery($location_id = NULL, $purchasable_entity_type_id = NULL, $purchasable_entity_id = NULL, $status = NULL, array $properties = []) {
    // Add location if valid.
    if (self::isValidId($location_id)) {
      $properties['location_id'] = $location_id;
    }

    // Add purchasable entity type and id if valid.
    if (self::isValidId($purchasable_entity_type_id)) {
      $properties['purchasable_entity__target_type'] = $purchasable_entity_type_id;

      if (self::isValidId($purchasable_entity_id) || is_array($purchasable_entity_id)) {
        $properties['purchasable_entity__target_id'] = $purchasable_entity_id;
      }
    }

    // Filter by status if not NULL.
    if (is_bool($status)) {
      $properties['status'] = $status;

      // Only filter by location status if items need to be active.
      if ($status) {
        $properties['location_id.entity.status'] = TRUE;
      }
    }

    // Build entity query.
    $entity_query = $this->getQuery();
    $this->buildPropertyQuery($entity_query, $properties);

    return $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId($location_id, $purchasable_entity_type_id, $purchasable_entity_id) {
    $return = $this->getItemQuery($location_id, $purchasable_entity_type_id, $purchasable_entity_id)->execute();
    return empty($return) ? NULL : current($return);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIds(array $location_ids, $purchasable_entity_type_id, $purchasable_entity_id) {
    return $this->getItemQuery($location_ids, $purchasable_entity_type_id, $purchasable_entity_id)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIdsByLocation($location_id, $status = NULL) {
    return $this->getItemQuery($location_id, NULL, NULL, $status)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIdsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id = NULL, $status = NULL) {
    return $this->getItemQuery(NULL, $purchasable_entity_type_id, $purchasable_entity_id, $status)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIdsByRemoteIds($bundle, $location_remote_id, $remote_ids) {
    if (!empty($remote_ids) && $location_id = $this->getLocationStorage()->getIdByRemoteId($bundle, $location_remote_id)) {
      $remote_ids = (!is_array($remote_ids)) ? [$remote_ids] : $remote_ids;

      $data_table = $this->getDataTable() ? $this->getDataTable() : $this->getBaseTable();
      $item_remote_table = $this->getTableMapping()->getFieldTableName('remote_id');
      $item_remote_column_names = $this->getTableMapping()->getColumnNames('remote_id');

      $query = $this->database->select($item_remote_table);
      $query->join($data_table, NULL, "{$data_table}.id = {$item_remote_table}.entity_id");
      $query->addField($item_remote_table, $item_remote_column_names['remote_id']);
      $query->addField($item_remote_table, 'entity_id');
      $query->condition($item_remote_column_names['provider'], $bundle);
      $query->condition($item_remote_column_names['remote_id'], $remote_ids, 'IN');
      $query->condition('location_id', $location_id);

      return $query->execute()->fetchAllKeyed(0, 1);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationIds(array $item_ids) {
    // Exit early if no item ids are present.
    if (empty($item_ids)) {
      return [];
    }

    $table = $this->getDataTable()?:$this->getBaseTable();

    $query = $this->database->select($table, 'ii');
    $query->addField('ii', 'id', 'id');
    $query->addField('ii', 'location_id', 'location');
    $query->condition('id', $item_ids, 'IN');
    $return = $query->execute()->fetchAllKeyed(0, 1);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationIdsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id, $status = NULL) {
    $table = $this->getDataTable()?:$this->getBaseTable();

    $query = $this->database->select($table, 'ii');
    $query->addField('ii', 'id', 'id');
    $query->addField('ii', 'location_id', 'location');
    $query->condition('purchasable_entity__target_id', $purchasable_entity_id);
    $query->condition('purchasable_entity__target_type', $purchasable_entity_type_id);
    if (is_bool($status)) {
      $query->condition('status', $status);
    }
    $return = $query->execute()->fetchAllKeyed(0, 1);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityIds($location_id, $purchasable_entity_type_id) {
    $table = $this->getDataTable()?:$this->getBaseTable();

    $query = $this->database->select($table, 'ii');
    $query->addField('ii', 'id', 'id');
    $query->addField('ii', 'purchasable_entity__target_id', 'peid');
    $query->condition('location_id', $location_id);
    $query->condition('purchasable_entity__target_type', $purchasable_entity_type_id);
    $return = $query->execute()->fetchAllKeyed(0, 1);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function loadItem($location_id, $purchasable_entity_type_id, $purchasable_entity_id) {
    $result = $this->getItemId($location_id, $purchasable_entity_type_id, $purchasable_entity_id);
    return $result ? $this->load($result) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadItemsByLocation($location_id, $status = NULL) {
    $result = $this->getItemIdsByLocation($location_id, $status);
    return $result ? $this->loadMultiple($result) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadItemsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id = NULL, $status = NULL) {
    $result = $this->getItemIdsByPurchasableEntity($purchasable_entity_type_id, $purchasable_entity_id, $status);
    return $result ? $this->loadMultiple($result) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function createMultiple($location, $purchasable_entity_type_id, array $purchasable_entity_ids, array $values = []) {
    // Loads location if not entity and is valid ID.
    if (self::isValidId($location)) {
      $location = $this->getLocationStorage()->load($location);
    }

    $entities = [];

    // Only create entities if locations exist.
    if ($location instanceof InventoryLocationInterface && count($purchasable_entity_ids) > 0) {
      // Add default properties to set initial field values.
      $values['commerce_inventory_provider'] = $location->bundle();
      $values['location_id'] = $location->id();
      $values['purchasable_entity']['target_type'] = $purchasable_entity_type_id;

      // Gets current purchasable entities of this type at the location.
      $existing_ids = $this->getPurchasableEntityIds($location->id(), $purchasable_entity_type_id);
      // Create items that don't already exist.
      foreach (array_diff($purchasable_entity_ids, $existing_ids) as $purchasable_entity_id) {
        // Set new purchasable entity id.
        $values['purchasable_entity']['target_id'] = $purchasable_entity_id;
        // Create new entity.
        $entities[] = $this->create($values);
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $entity = parent::create($values);
    $this->getProvider($entity->bundle())->onItemPostCreate($entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    $id = parent::doPreSave($entity);

    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $entity */
    $location = $entity->getLocation();
    $purchasable_entity = $entity->getPurchasableEntity();
    if (is_null($location) || is_null($purchasable_entity)) {
      throw new EntityStorageException("Purchasable item and location required for '{$this->getEntityType()->getLowercaseLabel()}'.");
    }

    $entity_id = $this->getItemId($location->id(), $purchasable_entity->getEntityTypeId(), $purchasable_entity->id());
    if (!is_null($entity_id) && $entity_id !== $id) {
      throw new EntityStorageException("This purchasable item already exists at this location.");
    }


    // Inactivate if location or purchasable entity are missing.
    if (is_null($entity->getLocation()) || is_null($entity->getPurchasableEntity())) {
      $entity->inactivate();
    }

    // Revalidate on save.
    $entity->isValid(TRUE);

    $this->getProvider($entity->bundle())->onItemPreSave($entity);

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $entity */
    $this->getProvider($entity->bundle())->onItemPostSave($entity, $update);

    // Add status to logs.
    if ($entity->isValid() == FALSE) {
      $this->getLogStorage()->generate($entity, 'inventory_item_config_invalid')->save();
    }

    parent::doPostSave($entity, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }

    // Delete the Logs related to the Inventory Items.
    /** @var \Drupal\commerce_log\LogStorageInterface $log_storage */
    $log_storage = $this->entityManager->getStorage('commerce_log');
    $logs = $log_storage->loadByProperties([
      'source_entity_id' => array_keys($entities),
      'source_entity_type' => $this->getEntityTypeId(),
    ]);
    $log_storage->delete($logs);

    return parent::delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function syncQuantityFromProvider(array $inventory_items) {
    foreach ($inventory_items as $inventory_item) {
      $this->getProvider($inventory_item->bundle())->syncProviderQuantity($inventory_item, FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncQuantityToProvider(array $inventory_items) {
    foreach ($inventory_items as $inventory_item) {
      $this->getProvider($inventory_item->bundle())->syncProviderQuantity($inventory_item, TRUE);
    }
  }

}
