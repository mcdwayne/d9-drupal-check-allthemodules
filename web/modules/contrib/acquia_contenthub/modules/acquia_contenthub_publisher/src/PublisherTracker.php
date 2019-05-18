<?php

namespace Drupal\acquia_contenthub_publisher;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Class PublisherTracker.
 */
class PublisherTracker {

  const QUEUED = 'queued';

  const EXPORTED = 'exported';

  const CONFIRMED = 'confirmed';

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
   * Get the tracking record for a given uuid.
   *
   * @param string $uuid
   *   The entity uuid.
   */
  public function get($uuid) {
    $query = $this->database->select('acquia_contenthub_publisher_export_tracking', 't')
      ->fields('t', ['entity_uuid']);
    $query->condition('entity_uuid', $uuid);
    return $query->execute()->fetchObject();
  }

  /**
   * Add tracking for an entity in a self::EXPORTED state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to add tracking.
   * @param string $hash
   *   A sha1 hash of the data attribute for change management.
   *
   * @throws \Exception
   */
  public function track(EntityInterface $entity, $hash) {
    $this->insertOrUpdate($entity, self::EXPORTED, $hash);
  }

  /**
   * Add tracking for an entity in a self::QUEUED state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to add tracking.
   *
   * @throws \Exception
   */
  public function queue(EntityInterface $entity) {
    $this->insertOrUpdate($entity, self::QUEUED);
  }

  /**
   * Remove tracking for an entity.
   *
   * @param string $uuid
   *   The uuid for which to remove tracking.
   *
   * @throws \Exception
   */
  public function delete($uuid) {
    $query = $this->database->delete('acquia_contenthub_publisher_export_tracking');
    $query->condition('entity_uuid', $uuid);
    return $query->execute();
  }

  /**
   * Determines if an entity will be inserted or updated with a status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add tracking to.
   * @param string $status
   *   The status of the tracking.
   * @param string $hash
   *   A sha1 hash of the data attribute for change management.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Database statement.
   *
   * @throws \Exception
   */
  protected function insertOrUpdate(EntityInterface $entity, $status, $hash = '') {
    if ($entity instanceof EntityChangedInterface) {
      $modified = date('c', $entity->getChangedTime());
    }
    else {
      $modified = date('c');
    }

    $results = $this->get($entity->uuid());

    // If we've previously tracked this thing, set its created date.
    if ($results) {
      $values = ['modified' => $modified, 'status' => $status];
      if ($hash) {
        $values['hash'] = $hash;
      }
      $query = $this->database->update('acquia_contenthub_publisher_export_tracking')
        ->fields($values);
      $query->condition('entity_uuid', $entity->uuid());
      return $query->execute();
    }
    elseif ($entity instanceof NodeInterface) {
      $created = date('c', $entity->getCreatedTime());
    }
    // Otherwise just mirror the modified date.
    else {
      $created = $modified;
    }
    $values = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'status' => $status,
      'created' => $created,
      'modified' => $modified,
      'hash' => $hash,
    ];
    return $this->database->insert('acquia_contenthub_publisher_export_tracking')
      ->fields($values)
      ->execute();
  }

}
