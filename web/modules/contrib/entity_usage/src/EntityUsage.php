<?php

namespace Drupal\entity_usage;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_usage\Events\Events;
use Drupal\entity_usage\Events\EntityUsageEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the entity usage base class.
 */
class EntityUsage implements EntityUsageInterface {

  /**
   * The database connection used to store entity usage information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table used to store entity usage information.
   *
   * @var string
   */
  protected $tableName;

  /**
   * An event dispatcher instance.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Construct the EntityUsage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the entity usage
   *   information.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for events.
   * @param string $table
   *   (optional) The table to store the entity usage info. Defaults to
   *   'entity_usage'.
   */
  public function __construct(Connection $connection, EventDispatcherInterface $event_dispatcher, $table = 'entity_usage') {

    $this->connection = $connection;
    $this->tableName = $table;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function add($t_id, $t_type, $re_id, $re_type, $method = 'entity_reference', $count = 1) {

    $this->connection->merge($this->tableName)
      ->keys([
        't_id' => $t_id,
        't_type' => $t_type,
        're_id' => $re_id,
        're_type' => $re_type,
        'method' => $method,
      ])
      ->fields(['count' => $count])
      ->expression('count', 'count + :count', [':count' => $count])
      ->execute();

    $event = new EntityUsageEvent($t_id, $t_type, $re_id, $re_type, $method, $count);
    $this->eventDispatcher->dispatch(Events::USAGE_ADD, $event);

  }

  /**
   * {@inheritdoc}
   */
  public function delete($t_id, $t_type, $re_id = NULL, $re_type = NULL, $count = 1) {

    // Delete rows that have an exact or less value to prevent empty rows.
    $query = $this->connection->delete($this->tableName)
      ->condition('t_type', $t_type)
      ->condition('t_id', $t_id);
    if ($re_type && $re_id) {
      $query
        ->condition('re_type', $re_type)
        ->condition('re_id', $re_id);
    }
    if ($count) {
      $query->condition('count', $count, '<=');
    }
    $result = $query->execute();

    // If the row has more than the specified count decrement it by that number.
    if (!$result && $count > 0) {
      $query = $this->connection->update($this->tableName)
        ->condition('t_type', $t_type)
        ->condition('t_id', $t_id);
      if ($re_type && $re_id) {
        $query
          ->condition('re_type', $re_type)
          ->condition('re_id', $re_id);
      }
      $query->expression('count', 'count - :count', [':count' => $count]);
      $query->execute();
    }
    $event = new EntityUsageEvent($t_id, $t_type, $re_id, $re_type, NULL, $count);
    $this->eventDispatcher->dispatch(Events::USAGE_DELETE, $event);

  }

  /**
   * {@inheritdoc}
   */
  public function bulkDeleteTargets($t_type) {

    // Delete all rows of this given type.
    $query = $this->connection->delete($this->tableName)
      ->condition('t_type', $t_type);
    $query->execute();

    $event = new EntityUsageEvent(NULL, $t_type, NULL, NULL, NULL, NULL);
    $this->eventDispatcher->dispatch(Events::BULK_TARGETS_DELETE, $event);

  }

  /**
   * {@inheritdoc}
   */
  public function bulkDeleteHosts($re_type) {

    // Delete all rows of this given type.
    $query = $this->connection->delete($this->tableName)
      ->condition('re_type', $re_type);
    $query->execute();

    $event = new EntityUsageEvent(NULL, NULL, NULL, $re_type, NULL, NULL);
    $this->eventDispatcher->dispatch(Events::BULK_HOSTS_DELETE, $event);

  }

  /**
   * {@inheritdoc}
   */
  public function listUsage(EntityInterface $entity, $include_method = FALSE) {

    $result = $this->connection->select($this->tableName, 'e')
      ->fields('e', ['re_id', 're_type', 'method', 'count'])
      ->condition('t_id', $entity->id())
      ->condition('t_type', $entity->getEntityTypeId())
      ->condition('count', 0, '>')
      ->execute();
    $references = [];
    foreach ($result as $usage) {
      if ($include_method) {
        $references[$usage->method][$usage->re_type][$usage->re_id] = $usage->count;
      }
      else {
        $count = $usage->count;
        // If there were previous usages recorded for this same pair of entities
        // (with different methods), sum on the top of it.
        if (!empty($references[$usage->re_type][$usage->re_id])) {
          $count += $references[$usage->re_type][$usage->re_id];
        }
        $references[$usage->re_type][$usage->re_id] = $count;
      }
    }
    return $references;

  }

  /**
   * {@inheritdoc}
   */
  public function listReferencedEntities(EntityInterface $entity) {
    $result = $this->connection->select($this->tableName, 'e')
      ->fields('e', ['t_id', 't_type', 'count'])
      ->condition('re_id', $entity->id())
      ->condition('re_type', $entity->getEntityTypeId())
      ->condition('count', 0, '>')
      ->execute();
    $references = [];
    foreach ($result as $usage) {
      $count = $usage->count;
      // If there were previous usages recorded for this same pair of entities
      // (with different methods), sum on the top of it.
      if (!empty($references[$usage->t_type][$usage->t_id])) {
        $count += $references[$usage->t_type][$usage->t_id];
      }
      $references[$usage->t_type][$usage->t_id] = $count;
    }
    return $references;
  }

}
