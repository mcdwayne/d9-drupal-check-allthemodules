<?php

namespace Drupal\message_thread;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\message_thread\Entity\MessageThread;
use Drupal\message\Entity\Message;

/**
 * Drupal\message_thread\MessageStatistics.
 */
class MessageStatistics implements MessageStatisticsInterface {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the MessageStatistics service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(Connection $database, AccountInterface $current_user, EntityManagerInterface $entity_manager, StateInterface $state) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->entityManager = $entity_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function read(array $entities, string $entity_type, $accurate = TRUE) {
    $options = $accurate ? [] : ['target' => 'replica'];
    $stats = $this->database->select('message_thread_statistics', 'mts', $options)
      ->fields('mts')
      ->condition('mts.entity_id', array_keys($entities), 'IN')
      ->condition('mts.entity_type', $entity_type)
      ->execute();

    $statistics_records = [];
    while ($entry = $stats->fetchObject()) {
      $statistics_records[] = $entry;
    }
    return $statistics_records;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityInterface $entity) {
    $this->database->delete('message_thread_statistics')
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function create(FieldableEntityInterface $entity) {
    $query = $this->database->insert('message_thread_statistics')
      ->fields([
        'entity_id',
        'entity_type',
        'mid',
        'last_message_timestamp',
        'last_message_name',
        'last_message_uid',
        'message_count',
      ]);

    // Get the user ID from the entity if it's set, or default to the
    // currently logged in user.
    $last_message_uid = 0;
    if ($entity instanceof EntityOwnerInterface) {
      $last_message_uid = $entity->getOwnerId();
    }
    if (!isset($last_message_uid)) {
      // Default to current user when entity does not implement
      // EntityOwnerInterface or author is not set.
      $last_message_uid = $this->currentUser->id();
    }
    // Default to REQUEST_TIME when entity does not have a changed property.
    $last_message_timestamp = REQUEST_TIME;
    // @todo Make comment statistics language aware and add some tests. See
    //   https://www.drupal.org/node/2318875
    if ($entity instanceof EntityChangedInterface) {
      $last_message_timestamp = $entity->getChangedTimeAcrossTranslations();
    }
    $query->values([
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'mid' => 0,
      'last_message_timestamp' => $last_message_timestamp,
      'last_message_name' => NULL,
      'last_message_uid' => $last_message_uid,
      'message_count' => 0,
    ]);

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getMaximumCount($entity_type) {
    return $this->database->query('SELECT MAX(message_count) FROM {message_thread_statistics} WHERE entity_type = :entity_type', [':entity_type' => $entity_type])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getRankingInfo() {
    return [
      'comments' => [
        'title' => t('Number of comments'),
        'join' => [
          'type' => 'LEFT',
          'table' => 'message_thread_statistics',
          'alias' => 'ces',
          // Default to comment field as this is the most common use case for
          // nodes.
          'on' => "ces.entity_id = i.sid AND ces.entity_type = 'message'",
        ],
        // Inverse law that maps the highest view count on the site to 1 and 0
        // to 0. Note that the ROUND here is necessary for PostgreSQL and SQLite
        // in order to ensure that the :message_scale argument is treated as
        // a numeric type, because the PostgreSQL PDO driver sometimes puts
        // values in as strings instead of numbers in complex expressions like
        // this.
        'score' => '2.0 - 2.0 / (1.0 + ces.message_count * (ROUND(:message_scale, 4)))',
        'arguments' => [':message_scale' => \Drupal::state()->get('message.node_message_statistics_scale') ?: 0],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function update(Message $message) {
    $thread_id = message_thread_relationship($message->id());
    if (!$thread_id) {
      return;
    }
    $message_thread = MessageThread::load($thread_id);
    // Allow bulk updates and inserts to temporarily disable the maintenance of
    // the {message_thread_statistics} table.
    if (!$this->state->get('message.maintain_entity_statistics')) {
      return;
    }
    $query = $this->database->select('message_field_data', 'm');
    $query->join('message_thread_index', 'i', 'm.mid=i.mid');
    $query->addExpression('COUNT(m.mid)');
    $count = $query->condition('i.thread_id', $thread_id)
      ->condition('m.default_langcode', 1)
      ->execute()
      ->fetchField();

    if ($count > 0) {
      // Messages exist.
      $query = $this->database->select('message_field_data', 'm');
      $query->join('message_thread_index', 'i', 'm.mid=i.mid');
      $last_reply = $query->fields('m', ['mid', 'created', 'uid'])
        ->condition('i.thread_id', $thread_id)
        ->condition('m.default_langcode', 1)
        ->orderBy('m.created', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchObject();
      // Use merge here because entity could be created before comment field.
      $this->database->merge('message_thread_statistics')
        ->fields([

          'mid' => $last_reply->mid,
          'message_count' => $count,
          'last_message_timestamp' => $last_reply->created,
          'last_message_name' => $last_reply->uid ? '' : $last_reply->name,
          'last_message_uid' => $last_reply->uid,
        ])
        ->keys([
          'entity_id' => $thread_id,
          'entity_type' => $message_thread->getEntityTypeId(),
        ])
        ->execute();
    }
    else {
      // Messages do not exist.
      $entity = $message_thread->id();
      // Get the user ID from the entity if it's set, or default to the
      // currently logged in user.
      if ($entity instanceof EntityOwnerInterface) {
        $last_message_uid = $entity->getOwnerId();
      }
      if (!isset($last_message_uid)) {
        // Default to current user when entity does not implement
        // EntityOwnerInterface or author is not set.
        $last_message_uid = $this->currentUser->id();
      }
      $this->database->update('message_thread_statistics')
        ->fields([
          'mid' => 0,
          'message_count' => 0,
          // Use the changed date of the entity if it's set, or default to
          // REQUEST_TIME.
          'last_message_timestamp' => ($entity instanceof EntityChangedInterface) ? $entity->getChangedTimeAcrossTranslations() : REQUEST_TIME,
          'last_message_name' => '',
          'last_message_uid' => $last_message_uid,
        ])
        ->condition('entity_id', $thread_id)
        ->condition('entity_type', $message_thread->getEntityTypeId())
        ->execute();
    }

    // Reset the cache of the commented entity so that when the entity is loaded
    // the next time, the statistics will be loaded again.
    $this->entityManager->getStorage($message->getEntityTypeId())->resetCache([$message_thread->id()]);
  }

}
