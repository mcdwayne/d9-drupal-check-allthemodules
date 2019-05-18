<?php

namespace Drupal\commerce_inventory\Entity\Storage;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface;
use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\InventoryAdjustmentTypeManager;
use Drupal\commerce_inventory\InventoryProviderManager;
use Drupal\Core\Cache\Cache;
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
 * Defines the storage handler class for Commerce Inventory Adjustment entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce Inventory Adjustment entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryAdjustmentStorage extends CommerceContentEntityStorage implements InventoryAdjustmentStorageInterface {

  /**
   * The Inventory Adjustment type manager.
   *
   * @var \Drupal\commerce_inventory\InventoryAdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * Loaded Inventory Adjustment type plugins.
   *
   * @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface[]
   */
  protected $adjustmentTypes = [];

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
   * Temporary array of adjustment back-references.
   *
   * The temporary array values are used to bypass adjustments with unsaved
   * back references.
   *
   * @var array
   */
  protected $tempReferences = [];

  /**
   * Constructs a InventoryAdjustmentStorage object.
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
   * @param \Drupal\commerce_inventory\InventoryAdjustmentTypeManager $adjustment_type_manager
   *   The Inventory Adjustment type manager.
   * @param \Drupal\commerce_inventory\InventoryProviderManager $inventory_provider_manager
   *   The inventory provider manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EventDispatcherInterface $event_dispatcher, InventoryAdjustmentTypeManager $adjustment_type_manager, InventoryProviderManager $inventory_provider_manager) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $memory_cache, $event_dispatcher);
    $this->adjustmentTypeManager = $adjustment_type_manager;
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
      $container->get('plugin.manager.commerce_inventory_adjustment_type'),
      $container->get('plugin.manager.commerce_inventory_provider')
    );
  }

  /**
   * Gets an Inventory Adjustment Type plugin.
   *
   * @param string $adjustment_type
   *   The Inventory Adjustment Type ID.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface
   *   The loaded Adjustment Type plugin instance.
   */
  protected function getAdjustmentType($adjustment_type) {
    if (!array_key_exists($adjustment_type, $this->adjustmentTypes)) {
      $this->adjustmentTypes[$adjustment_type] = $this->adjustmentTypeManager->createInstance($adjustment_type);
    }
    return $this->adjustmentTypes[$adjustment_type];
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
   * {@inheritdoc}
   */
  public function createAdjustment($adjustment_type_id, InventoryItemInterface $item, $quantity, array $values = [], InventoryItemInterface $related_item = NULL, $save = TRUE) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $adjustment */
    /** @var \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $related_adjustment */

    // Clean values of dynamic data.
    $values = array_diff_key($values, [
      'commerce_inventory_adjustment_type' => NULL,
      'created' => NULL,
      'quantity' => NULL,
      'related_adjustment' => NULL,
    ]);

    // Create initial adjustment.
    $adjustment_type = $this->getAdjustmentType($adjustment_type_id);
    $adjustment_values = [
      'item_id' => $item->id(),
      'commerce_inventory_adjustment_type' => $adjustment_type_id,
      'quantity' => $quantity,
    ] + $values;
    $adjustment = $this->create($adjustment_values);

    // Related adjustment requirement check.
    if ($adjustment_type->hasRelatedAdjustmentType()) {
      // Related Item required.
      if (is_null($related_item)) {
        throw new EntityStorageException("Related Inventory Item required for {$adjustment_type->getLabel()} adjustment.");
      }

      // Create related adjustment.
      $related_type_id = $adjustment_type->getRelatedAdjustmentTypeId();
      $related_values = [
        'item_id' => $related_item->id(),
        'commerce_inventory_adjustment_type' => $related_type_id,
        'quantity' => $quantity,
      ] + $values;
      $related_adjustment = $this->create($related_values);

      // Relate related adjustment entity to initial adjustment.
      $adjustment->setRelatedAdjustment($related_adjustment);
    }

    // Save adjustment (which saves related adjustment).
    if ($save) {
      $adjustment->save();
    }

    return $adjustment;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateQuantity($item_id) {
    $table = $this->getDataTable()?:$this->getBaseTable();

    $query = $this->database->select($table, 'ia');
    $query->condition('item_id', $item_id);
    $query->addExpression('sum(quantity)', 'total');
    $total = $query->execute()->fetchObject()->total;

    return round($total, 5);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantitySelectQuery() {
    $table = $this->getDataTable() ?: $this->getBaseTable();
    $query = $this->database->select($table, 'ia');
    $query->addField('ia', 'item_id', 'inventory_item_id');
    $query->addField('ia', 'quantity');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $entity */
    $related_entity = $entity->getRelatedAdjustment();

    // Make sure adjustment is valid in case createAdjustment wasn't used.
    if ($entity->getType()->hasRelatedAdjustmentType()) {
      // Skip validation if this entity is a temp reference.
      if (in_array($entity->uuid(), $this->tempReferences)) {
        // Do nothing.
      }
      // Related adjustment isn't an adjustment entity.
      elseif ($related_entity instanceof InventoryAdjustmentInterface == FALSE) {
        throw new EntityStorageException("Related Adjustment required for {$entity->getType()->getLabel()} adjustments.");
      }
      // Related adjustment type doesn't match correct related adjustment type.
      elseif ($related_entity->bundle() !== $entity->getType()->getRelatedAdjustmentTypeId()) {
        throw new EntityStorageException("Invalid Related Adjustment type for {$entity->getType()->getLabel()} adjustment.");
      }
    }
    // An entity is related but isn't supposed to be.
    elseif (!is_null($related_entity)) {
      throw new EntityStorageException("Related Adjustment not allowed for {$entity->getType()->getLabel()} adjustments.");
    }

    // Run provider presave only on initial entity and on entity creation.
    if ($entity->isNew() && !in_array($entity->uuid(), $this->tempReferences) && !$entity->getData('skip_provider_adjustment_pre_save', FALSE)) {
      $this->getProvider($entity->getItem()->bundle())->onAdjustmentPreSave($entity);
    }

    // Add related-adjustment to temporary references array. Remove
    // related-adjustment's back-reference to this entity if it is set.
    if ($related_entity instanceof InventoryAdjustmentInterface && $related_entity->isNew()) {
      $this->tempReferences[$entity->uuid()] = $related_entity->uuid();
      $related_entity->set('related_adjustment', NULL);
    }

    return parent::doPreSave($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $entity */
    // Add entity item to cid array for cache quantity invalidation.
    $cache_tags = ['quantity:commerce_inventory_item:' . $entity->getItemId()];

    if (!in_array($entity->uuid(), $this->tempReferences)) {
      if ($related_entity = $entity->getRelatedAdjustment()) {
        // Relate adjustment entity back to other entity.
        if (array_key_exists($entity->uuid(), $this->tempReferences) && $this->tempReferences[$entity->uuid()] == $related_entity->uuid()) {
          $related_entity->set('related_adjustment', $entity->id());
          $related_entity->save();
          unset($this->tempReferences[$entity->uuid()]);
        }

        // Add related entity item to cid array for cache quantity invalidation.
        $cache_tags[] = 'quantity:commerce_inventory_item:' . $related_entity->getItemId();
      }
    }

    // Run provider adjustment.
    if (!$update && !$entity->getData('skip_provider_adjustment_post_save', FALSE)) {
      $this->getProvider($entity->getItem()->bundle())->onAdjustmentPostSave($entity);
    }

    // Add to logs.
    $this->getLogStorage()->generate($entity->getItem(), 'inventory_item_adjustment', [
      'adjustment' => $entity->getType()->getLabel(),
      'quantity' => $entity->getQuantity(),
      'total' => $this->calculateQuantity($entity->getItemId()),
    ])->save();

    // Invalidate cache.
    Cache::invalidateTags($cache_tags);

    parent::doPostSave($entity, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->adjustmentTypeManager = NULL;
    $this->adjustmentTypes = [];
    $this->inventoryProviderManager = NULL;
    $this->inventoryProviders = [];
    $this->logStorage = NULL;
    // $this->tempReferences = NULL;.
    parent::__sleep();
  }

}
