<?php

namespace Drupal\radioactivity;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Class RadioactivityProcessor.
 *
 * @package Drupal\radioactivity
 */
class RadioactivityProcessor implements RadioactivityProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The state key-value storage.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The radioactivity logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * The radioactivity storage.
   *
   * @var \Drupal\Radioactivity\IncidentStorageInterface
   */
  protected $storage;

  /**
   * The timestamp for the current request.
   *
   * @var int
   */
  protected $requestTime;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Constructs a Radioactivity processor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The key-value storage.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.
   * @param \Drupal\radioactivity\StorageFactory $storage
   *   The storage factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, LoggerChannelFactoryInterface $logger_factory, StorageFactory $storage, TimeInterface $time, QueueFactory $queue) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->log = $logger_factory->get(self::LOGGER_CHANNEL);
    $this->storage = $storage->getConfiguredStorage();
    $this->requestTime = $time->getRequestTime();
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public function processDecay() {
    $resultCount = 0;
    $processed = FALSE;

    /** @var \Drupal\field\Entity\FieldStorageConfig[] $fieldConfigs */
    $fieldConfigs = $this->entityTypeManager->getStorage('field_storage_config')->loadByProperties(['type' => 'radioactivity']);

    if (empty($fieldConfigs)) {
      return 0;
    }

    foreach ($fieldConfigs as $fieldConfig) {
      $profile = $fieldConfig->getSetting('profile');
      if ($fieldConfig->hasData() &&
          ($profile === 'linear' || $profile === 'decay') &&
            $this->hasReachedGranularityThreshold($fieldConfig)
      ) {
        $resultCount += $this->processFieldDecay($fieldConfig);
        $processed = TRUE;
      }
    }

    if ($processed) {
      $this->state->set(self::LAST_PROCESSED_STATE_KEY, $this->requestTime);
    }

    $this->log->notice('Processed @count radioactivity decays.', ['@count' => $resultCount]);
    return $resultCount;
  }

  /**
   * Determines if the field has reached the next granularity threshold.
   *
   * For some profiles profile, we only calculate the decay when x seconds have
   * passed since the last cron run. The number of seconds is stored in
   * 'granularity' field setting.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldConfig
   *   Configuration of the field to be checked.
   *
   * @return bool
   *   True if the threshold was reached.
   */
  private function hasReachedGranularityThreshold(FieldStorageConfigInterface $fieldConfig) {
    $granularity = $fieldConfig->getSetting('granularity');
    if ($granularity == 0) {
      return TRUE;
    }

    $lastCronTimestamp = $this->state->get(self::LAST_PROCESSED_STATE_KEY, 0);
    $threshold = $lastCronTimestamp - ($lastCronTimestamp % $granularity) + $granularity;
    return $this->requestTime >= $threshold;
  }

  /**
   * Update entities attached to given field storage.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldConfig
   *   Configuration of the field to be processed.
   *
   * @return int
   *   The number of processed entities.
   */
  private function processFieldDecay(FieldStorageConfigInterface $fieldConfig) {
    $fieldName = $fieldConfig->get('field_name');
    $entityType = $fieldConfig->getTargetEntityTypeId();

    $query = $this->entityTypeManager->getStorage($entityType)->getQuery()
      ->condition($fieldName . '.timestamp', $this->requestTime, ' <= ')
      ->condition($fieldName . '.energy', NULL, 'IS NOT NULL')
      ->condition($fieldName . '.energy', 0, '>');
    $entityIds = $query->execute();

    // Delegate processing to a queue worker to prevent memory errors when large
    // number of entities are processed.
    $chunks = array_chunk($entityIds, self::QUEUE_CHUNK_SIZE, TRUE);
    foreach ($chunks as $chunk) {
      $queue = $this->queue->get(self::QUEUE_WORKER_DECAY);
      $queue->createItem([
        'field_config' => $fieldConfig,
        'entity_ids' => $chunk,
      ]);
    }

    return count($entityIds);
  }

  /**
   * {@inheritdoc}
   */
  public function queueProcessDecay(FieldStorageConfigInterface $fieldConfig, array $entityIds) {
    $entityType = $fieldConfig->getTargetEntityTypeId();
    $fieldName = $fieldConfig->get('field_name');
    $profile = $fieldConfig->getSetting('profile');
    $halfLife = $fieldConfig->getSetting('halflife');
    $cutoff = $fieldConfig->getSetting('cutoff');

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->entityTypeManager
      ->getStorage($entityType)
      ->loadMultiple($entityIds);

    foreach ($entities as $entity) {
      $values = $entity->get($fieldName)->getValue();
      $timestamp = $values[0]['timestamp'];
      $energy = $values[0]['energy'];
      $elapsed = $timestamp ? $this->requestTime - $timestamp : 0;

      switch ($profile) {
        case 'linear':
          $energy = $energy > $elapsed ? $energy - $elapsed : 0;
          break;

        case 'decay':
          $energy = $energy * pow(2, -$elapsed / $halfLife);
          break;
      }

      if ($energy > $cutoff) {
        // Set the new energy level and update the timestamp.
        $entity->get($fieldName)->setValue([
          'energy' => $energy,
          'timestamp' => $this->requestTime,
        ]);
      }
      else {
        // Reset energy level to 0 if they are below the cutoff value.
        $entity->get($fieldName)->setValue([
          'energy' => 0,
          'timestamp' => $this->requestTime,
        ]);

      }

      if ($entity->getEntityType()->isRevisionable()) {
        $entity->setNewRevision(FALSE);
      }

      // Set flag so we can identify this entity save as one that just updates the radioactivity value.
      $entity->radioactivityUpdate = TRUE;
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processIncidents() {
    $resultCount = 0;

    $incidentsByType = $this->storage->getIncidentsByType();
    $this->storage->clearIncidents();

    foreach ($incidentsByType as $entityType => $incidents) {

      // Delegate processing to a queue worker to prevent memory errors when
      // large number of entities are processed.
      $chunks = array_chunk($incidents, self::QUEUE_CHUNK_SIZE, TRUE);
      foreach ($chunks as $chunk) {
        $queue = $this->queue->get(self::QUEUE_WORKER_INCIDENTS);
        $queue->createItem([
          'entity_type' => $entityType,
          'incidents' => $chunk,
        ]);
      }
      $resultCount += count($incidents);
    }

    $this->log->notice('Processed @count radioactivity incidents.', ['@count' => $resultCount]);
    return $resultCount;
  }

  /**
   * {@inheritdoc}
   */
  public function queueProcessIncidents($entityType, array $entityIncidents) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage($entityType)->loadMultiple(array_keys($entityIncidents));

    foreach ($entities as $entity) {
      // Do not process incidents for unpublished entities.
      if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
        continue;
      }

      /** @var \Drupal\radioactivity\Incident $incident */
      foreach ($entityIncidents[$entity->id()] as $incident) {
        $entity->get($incident->getFieldName())->energy += $incident->getEnergy();
      }
      if ($entity->getEntityType()->isRevisionable()) {
        $entity->setNewRevision(FALSE);
      }
      // Set flag so we can identify this entity save as one that just updates the radioactivity value.
      $entity->radioactivityUpdate = TRUE;
      $entity->save();
    }
  }

}
