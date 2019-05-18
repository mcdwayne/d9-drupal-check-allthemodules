<?php

namespace Drupal\acquia_contenthub_subscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class SubscriberTracker.
 */
class SubscriberTracker {

  /**
   * The status used to indicate that a tracked item is to be reimported.
   */
  const QUEUED = 'queued';

  /**
   * The status used to indicate that a tracked item has been imported.
   */
  const IMPORTED = 'imported';

  /**
   * The status used to indicate an entity should no longer receive updates.
   */
  const AUTO_UPDATE_DISABLED = 'auto_update_disabled';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * PublisherTracker constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Checks if a particular entity uuid is tracked.
   *
   * @param string $uuid
   *   The uuid of an entity.
   *
   * @return bool
   *   Whether or not the entity is tracked in the subscriber tables.
   */
  public function isTracked(string $uuid) {
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t', ['entity_type', 'entity_id']);
    $query->condition('entity_uuid', $uuid);

    return (bool) $query->execute()->fetchObject();
  }

  /**
   * Gets a list of uuids that are not yet tracked.
   *
   * @param string[] $uuids
   *   List of UUID strings.
   *
   * @return array
   *   The list of UUIDs of untracked entities.
   */
  public function getUntracked(array $uuids) {
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t');
    $query->condition('t.entity_uuid', $uuids, 'IN');
    $query->condition('t.status', SubscriberTracker::QUEUED, '<>');
    $results = $query->execute();
    $uuids = array_combine($uuids, $uuids);
    foreach ($results as $result) {
      // @todo this should be compared against a known hash.
      if ($result->hash) {
        unset($uuids[$result->entity_uuid]);
      }
    }
    return array_values($uuids);
  }

  /**
   * Add tracking for an entity in a self::EXPORTED state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to add tracking.
   * @param string $hash
   *   A sha1 hash of the data attribute for change management.
   * @param string|null $remote_uuid
   *   A remote uuid if relevant.
   *
   * @throws \Exception
   */
  public function track(EntityInterface $entity, $hash, $remote_uuid = NULL) {
    $values = [
      'entity_uuid' => $remote_uuid ? $remote_uuid : $entity->uuid(),
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'last_imported' => date('c'),
    ];
    $this->insertOrUpdate($values, self::IMPORTED, $hash);
  }

  /**
   * Add tracking for an entity in a self::QUEUED state.
   *
   * @var string $uuid
   *   The entity uuid to enqueue.
   *
   * @throws \Exception
   */
  public function queue($uuid) {
    $values = [
      'entity_uuid' => $uuid,
    ];
    $this->insertOrUpdate($values, self::QUEUED);
  }

  /**
   * Determines if an entity will be inserted or updated with a status.
   *
   * @param array $values
   *   The array of values to insert.
   * @param string $status
   *   The status of the tracking.
   * @param string $hash
   *   The hash string.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Database statement.
   *
   * @throws \Exception
   */
  protected function insertOrUpdate(array $values, $status, $hash = "") {
    if (empty($values['entity_uuid'])) {
      throw new \Exception("Cannot track a subscription without an entity uuid.");
    }
    $values['status'] = $status;
    if ($hash) {
      $values['hash'] = $hash;
    }
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t', ['first_imported']);
    $query->condition('entity_uuid', $values['entity_uuid']);
    $results = $query->execute()->fetchObject();
    // If we've previously tracked this thing, set its created date.
    if ($results) {
      $query = $this->database->update('acquia_contenthub_subscriber_import_tracking')
        ->fields($values);
      $query->condition('entity_uuid', $values['entity_uuid']);
      return $query->execute();
    }
    $values['first_imported'] = date('c');
    return $this->database->insert('acquia_contenthub_subscriber_import_tracking')
      ->fields($values)
      ->execute();
  }

  /**
   * Get a local entity by its remote uuid if hashes match.
   *
   * @param string $uuid
   *   The remote uuid of the entity to load.
   * @param string|null $hash
   *   The expected hash of the entity (Currently unused).
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded entity if found
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityByRemoteIdAndHash($uuid, $hash = NULL) {
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t', ['entity_type', 'entity_id']);
    $query->condition('entity_uuid', $uuid);

    if (NULL !== $hash) {
      $query->condition('hash', $hash);
    }

    $result = $query->execute()->fetchObject();

    if ($result && $result->entity_type && $result->entity_id) {
      return \Drupal::entityTypeManager()
        ->getStorage($result->entity_type)
        ->load($result->entity_id);
    }
  }

  /**
   * Delete an entry by its uuid.
   *
   * @param string $uuid
   *   UUID of the entity.
   */
  public function delete(string $uuid): void {
    $query = $this->database->delete('acquia_contenthub_subscriber_import_tracking');
    $query->condition('entity_uuid', $uuid);

    $query->execute();
  }

  /**
   * Get the current status of an entity by type/id.
   *
   * @param string $type
   *   The type of an entity.
   * @param string $id
   *   The id of an entity.
   *
   * @return string
   */
  public function getStatusByTypeId(string $type, string $id) {
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t', ['status']);
    $query->condition('entity_type', $type);
    $query->condition('entity_id', $id);
    $result = $query->execute()->fetchObject();
    if ($result && $result->status) {
      return $result->status;
    }
  }

  /**
   * Get the current status of a particular uuid.
   *
   * @param string $uuid
   *   The uuid of an entity.
   *
   * @return string
   */
  public function getStatusByUuid(string $uuid) {
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t', ['status']);
    $query->condition('entity_uuid', $uuid);
    $result = $query->execute()->fetchObject();
    if ($result && $result->status) {
      return $result->status;
    }
  }

  /**
   * Set the status of a particular item by its uuid.
   *
   * @param string $uuid
   *   The uuid of an entity.
   * @param $status
   *   The status to set.
   *
   * @throws \Exception
   */
  public function setStatusByUuid(string $uuid, $status) {
    $acceptable_statuses = [
      $this::AUTO_UPDATE_DISABLED,
      $this::IMPORTED,
      $this::QUEUED,
    ];
    if (!in_array($status, $acceptable_statuses)) {
      throw new \Exception(sprintf("The '%s' status is not valid. Please pass one of the following options: '%s'", $status, implode("', '", $acceptable_statuses)));
    }

    if (!$this->isTracked($uuid)) {
      return;
    }
    $query = $this->database->update('acquia_contenthub_subscriber_import_tracking');
    $query->fields(['status' => $status]);
    $query->condition('entity_uuid', $uuid);
    $query->execute();
  }

  /**
   * Set the status of a particular item by its entity type and id.
   *
   * @param string $type
   *   The type of an entity.
   * @param string $id
   *   The id of an entity.
   * @param string $status
   *   The status to set.
   *
   * @throws \Exception
   */
  public function setStatusByTypeId(string $type, string $id, string $status) {
    $query = $this->database->select('acquia_contenthub_subscriber_import_tracking', 't')
      ->fields('t', ['entity_uuid']);
    $query->condition('entity_type', $type);
    $query->condition('entity_id', $id);
    $result = $query->execute()->fetchObject();
    if (!(bool) $result) {
      return;
    }
    $this->setStatusByUuid($result->entity_uuid, $status);
  }

}
