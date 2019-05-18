<?php

namespace Drupal\radioactivity;

use Drupal\field\FieldStorageConfigInterface;

/**
 * Interface RadioactivityProcessorInterface.
 *
 * @package Drupal\radioactivity
 */
interface RadioactivityProcessorInterface {

  /**
   * The number of entities to be processed in one queue job.
   */
  const QUEUE_CHUNK_SIZE = 10;

  /**
   * The state key to store the last processed timestamp.
   */
  const LAST_PROCESSED_STATE_KEY = 'radioactivity_last_processed_timestamp';

  /**
   * The ID of the decay queue worker.
   */
  const QUEUE_WORKER_DECAY = 'radioactivity_decay';

  /**
   * The ID of the incidents queue worker.
   */
  const QUEUE_WORKER_INCIDENTS = 'radioactivity_incidents';

  /**
   * The logger channel ID.
   */
  const LOGGER_CHANNEL = 'radioactivity';

  /**
   * Apply decay to entities.
   *
   * @return int
   *   The number of decays processed.
   */
  public function processDecay();

  /**
   * Queue processing of Radioactivity decays.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldConfig
   *   Configuration of the field to be processed.
   * @param array $entityIds
   *   Entity IDs to be processed.
   */
  public function queueProcessDecay(FieldStorageConfigInterface $fieldConfig, array $entityIds);

  /**
   * Process emits from the queue.
   *
   * @return int
   *   The number of emits processed.
   */
  public function processIncidents();

  /**
   * Queue processing of Radioactivity emission incidents.
   *
   * @param string $entityType
   *   Incident entity type.
   * @param \Drupal\radioactivity\Incident[][] $entityIncidents
   *   Radioactivity incidents grouped per entity ID (1st) and incident ID
   *   (2nd).
   */
  public function queueProcessIncidents($entityType, array $entityIncidents);

}
