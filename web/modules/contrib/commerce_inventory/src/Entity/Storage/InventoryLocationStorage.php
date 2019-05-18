<?php

namespace Drupal\commerce_inventory\Entity\Storage;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_inventory\InventoryProviderManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the storage handler class for Commerce Inventory Location entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce Inventory Location entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryLocationStorage extends CommerceContentEntityStorage implements InventoryLocationStorageInterface {

  /**
   * The Inventory Item entity storage.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   */
  protected $inventoryItemStorage;

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
   * Gets the Inventory Item entity storage.
   *
   * @return \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   *   The Inventory Item storage instance.
   */
  protected function getItemStorage() {
    if (is_null($this->inventoryItemStorage)) {
      $this->inventoryItemStorage = $this->entityManager->getStorage('commerce_inventory_item');;
    }
    return $this->inventoryItemStorage;
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
   * {@inheritdoc}
   */
  public function getIdByRemoteId($bundle, $remote_id) {
    $results = $this->getQuery()
      ->condition('remote_id.provider', $bundle)
      ->condition('remote_id.remote_id', $remote_id)
      ->execute();
    return (!empty($results)) ? current($results) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdsByRemoteIds($bundle, $remote_ids) {
    if (!empty($remote_ids)) {
      $remote_ids = (!is_array($remote_ids)) ? [$remote_ids] : $remote_ids;

      $table = $this->getTableMapping()->getFieldTableName('remote_id');
      $column_names = $this->getTableMapping()->getColumnNames('remote_id');

      $query = $this->database->select($table);
      $query->addField($table, $column_names['remote_id']);
      $query->addField($table, 'entity_id');
      $query->condition($column_names['provider'], $bundle);
      $query->condition($column_names['remote_id'], $remote_ids, 'IN');

      return $query->execute()->fetchAllKeyed(0, 1);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasId($id) {
    return ($this->getQuery()->condition('id', $id)->count()->execute() > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    $entity = parent::create($values);
    $this->getProvider($entity->bundle())->onLocationPostCreate($entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }

    // Delete the Locations' child Inventory Items beforehand.
    $query = $this->getItemStorage()->getQuery()->condition('location_id', array_keys($entities), 'IN');
    $this->getItemStorage()->delete($this->getItemStorage()->loadMultiple($query->execute()));

    return parent::delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    $id = parent::doPreSave($entity);
    $this->getProvider($entity->bundle())->onLocationPreSave($entity);
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    $this->getProvider($entity->bundle())->onLocationPostSave($entity, $update);
    parent::doPostSave($entity, $update);
  }

}
