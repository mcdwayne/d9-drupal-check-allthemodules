<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class SimplenewsStatsAllowedLinks.
 */
class SimplenewsStatsAllowedLinks {

  /**
   * Database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  const TABLE_NAME = 'simplenews_stats_allowedlinks';

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Add link to the database.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity used as simplenews.
   * @param string $link
   *   The link to add.
   */
  public function add(EntityInterface $entity, $link) {
    $this->insert([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id'   => $entity->id(),
      'link'        => $link,
    ]);
  }

  /**
   * Save an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   *
   * @see db_insert()
   */
  public function insert(array $entry) {
    $return_value = NULL;
    try {
      $return_value = $this->connection->insert(static::TABLE_NAME)
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('db_insert failed. Message = %message', [
        '%message' => $e->getMessage(),
        ]), 'error');
    }
    return $return_value;
  }

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      $count = $this->connection->update(static::TABLE_NAME)
        ->fields($entry)
        ->condition('alid', $entry['alid'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('db_update failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query'   => $e->query_string,
          ]
        ), 'error');
    }
    return $count;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'alid' element of the
   *   entry to delete.
   */
  public function delete(array $entry) {
    $this->connection->delete(static::TABLE_NAME)
      ->condition('alid', $entry['alid'])
      ->execute();
  }

  /**
   * Read from the database using a filter array.
   *
   * @param array $conditions
   *   Array of conditions.
   */
  public function load(array $conditions = []) {

    $select = $this->connection
      ->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME);

    // Add each field and value as a condition to this query.
    foreach ($conditions as $field => $value) {
      $select->condition($field, $value);
    }
    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Check if link is allready stored.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity used as simplenews.
   * @param string $link
   *   The link to test.
   *
   * @return bool
   *   Return TRUE if allready allowed or FALSE.
   */
  public function isLinkExist(EntityInterface $entity, $link) {
    $select = $this->connection
      ->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME, ['alid'])
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('link', $link);

    return (bool) $select->countQuery()->execute()->fetchField();
  }

}
