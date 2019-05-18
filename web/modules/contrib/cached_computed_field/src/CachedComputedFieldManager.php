<?php

namespace Drupal\cached_computed_field;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEvent;
use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for managing cached computed fields.
 */
class CachedComputedFieldManager implements CachedComputedFieldManagerInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a CachedComputedFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypePluginManager
   *   The field type plugin manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, FieldTypePluginManagerInterface $fieldTypePluginManager, TimeInterface $time, ConfigFactoryInterface $configFactory, QueueFactory $queueFactory, EventDispatcherInterface $eventDispatcher) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldTypePluginManager = $fieldTypePluginManager;
    $this->time = $time;
    $this->configFactory = $configFactory;
    $this->queueFactory = $queueFactory;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function populateQueue() {
    foreach ($this->getExpiredItems() as $item) {
      $this->queue->createItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processQueue() {
    $end = time() + $this->getTimeLimit();
    while ((time() < $end) && $items = $this->claimItems()) {
      $event = new RefreshExpiredFieldsEvent($items);
      $this->eventDispatcher->dispatch(RefreshExpiredFieldsEventInterface::EVENT_NAME, $event);
    }
  }

  /**
   * Claims a batch of expired items from the queue and returns them.
   *
   * @return \Drupal\cached_computed_field\ExpiredItemCollectionInterface|null
   *   A collection of expired items, or NULL if there are no more items in the
   *   queue.
   */
  protected function claimItems() {
    $item_count = $this->getBatchSize();
    $queue = $this->getQueue();
    $items = [];

    for ($i = 0; $i < $item_count; $i++) {
      if (!$item = $queue->claimItem()) {
        break;
      }
      $data = $item->data;
      $items[] = new ExpiredItem($data['entity_type'], $data['entity_id'], $data['field_name']);
      $queue->deleteItem($item);
    }

    return $items ? new ExpiredItemCollection($items) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiredItems() {
    $request_time = $this->time->getRequestTime();
    $items = [];

    foreach ($this->getFieldMap() as $entity_type_id => $fields) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      foreach ($fields as $field_name => $field_info) {
        $query = $storage->getQuery();

        // Disable access checks. Cron is usually run as an anonymous user, and
        // we also want to make sure any content that is not accessible by
        // anonymous users is updated.
        $query->accessCheck(FALSE);

        // Fetch all entities that have expired fields, as well as the ones that
        // have never been processed yet.
        $condition = $query->orConditionGroup()
          ->condition("$field_name.expire", $request_time, '<')
          ->notExists("$field_name.expire")
          ->notExists($field_name);
        $query->condition($condition);
        if ($definition->hasKey('bundle')) {
          $query->condition($definition->getKey('bundle'), $field_info['bundles'], 'IN');
        }

        foreach ($query->execute() as $entity_id) {
          $items[] = [
            'entity_type' => $entity_type_id,
            'entity_id' => $entity_id,
            'field_name' => $field_name,
            // This is set to 0 for backwards compatibility. Normally, the
            // expire date would be added here but it would require that all
            // entities are loaded first, which would slow down the process a
            // lot.
            'expire' => 0,
          ];
        }
      }
    }

    return $items;
  }

  /**
   * Returns a lightweight map of cached computed fields across bundles.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: An associative array of the bundles in which the field
   *     appears, where the keys and values are both the bundle's machine name.
   */
  public function getFieldMap() {
    $map = [];
    foreach ($this->getFieldTypes() as $field_type) {
      $map = array_merge_recursive($this->entityFieldManager->getFieldMapByFieldType($field_type), $map);
    }

    return $map;
  }

  /**
   * Returns a list of field types provided by the Cached Computed Field module.
   *
   * @return array
   *   The list of field types.
   */
  public function getFieldTypes() {
    return array_keys(array_filter($this->fieldTypePluginManager->getDefinitions(), function ($definition) {
      return $definition['provider'] === 'cached_computed_field';
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function getQueue() {
    if (empty($this->queue)) {
      $this->queue = $this->queueFactory->get('cached_computed_field_expired_fields', FALSE);
    }
    return $this->queue;
  }

  /**
   * Returns the time limit.
   *
   * @return int
   *   The time limit, in seconds, during which new batches will be picked up
   *   for processing.
   */
  protected function getTimeLimit() {
    return $this->configFactory->get('cached_computed_field.settings')->get('time_limit');
  }

  /**
   * Returns the batch size.
   *
   * @return int
   *   The maximum number of items to process in a single batch.
   */
  protected function getBatchSize() {
    return $this->configFactory->get('cached_computed_field.settings')->get('batch_size');
  }

}
