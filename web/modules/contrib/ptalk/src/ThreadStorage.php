<?php

namespace Drupal\ptalk;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\ptalk\Entity\Message;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for threads.
 *
 * Extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class.
 */
class ThreadStorage extends SqlContentEntityStorage implements ThreadStorageInterface {

  /**
   * Array of loaded participants of the thread keyed by participant id.
   *
   * @var array
   */
  protected $participants = [];

  /**
   * Array of the amount of the unread threads.
   *
   * @var array
   */
  protected $counts = [];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ThreadStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_info, Connection $database, EntityManagerInterface $entity_manager, AccountInterface $current_user, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_info, $database, $entity_manager, $cache, $language_manager);
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $revision_id);
    $query->join('ptalk_thread_index', 'pti', "base.tid = pti.tid");

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    $this->participants = [];
    $this->counts = [];
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadThreadMessages($thread, $account, $load_deleted = FALSE, $messages_per_page = 0, $pager_id = 0) {
    $query = $this->database->select('ptalk_message', 'pm');
    $query->addField('pm', 'mid');
    $query->join('ptalk_message_index', 'pmi', 'pm.mid = pmi.mid');

    if (!$load_deleted) {
      // Do not load deleted messages.
      $query->condition('pmi.deleted', 0, '=');
    }

    $query->condition('pmi.recipient', $account->id(), '=');

    if ($messages_per_page) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($messages_per_page)->range(20, $messages_per_page);
      if ($pager_id) {
        $query->element($pager_id);
      }

      $count_query = $this->database->select('ptalk_message', 'pm');
      $count_query->addExpression('COUNT(DISTINCT pm.mid)');
      $count_query->join('ptalk_message_index', 'pmi', 'pm.mid = pmi.mid');
      if (!$load_deleted) {
        // Do not count deleted messages.
        $count_query->condition('pmi.deleted', 0, '=');
      }
      $count_query->condition('pmi.recipient', $account->id(), '=');
      $count_query->condition('pm.tid', $thread->id(), '=');
      $query->setCountQuery($count_query);
    }

    $query
      ->condition('pm.tid', $thread->id(), '=')
      ->orderBy('pm.mid')
      ->orderBy('pm.created', 'ASC');

    $mids = $query->execute()->fetchCol();

    $messages = [];
    if ($mids) {
      $messages = Message::loadMultiple($mids);
    }

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function loadThreadParticipants($tid, $account = NULL) {
    if (!isset($this->participants[$tid])) {
      $query = $this->database->select('ptalk_thread_index', 'pti');
      $query->leftJoin('users_field_data', 'ufd', "ufd.uid = pti.participant");
      $query
        ->fields('pti', ['participant', 'message_count', 'status', 'deleted'])
        ->fields('ufd', ['name'])
        ->condition('pti.tid', $tid);

      // If an account is provided, limit participants.
      if ($account) {
        $query->condition('pti.participant', $account->id());
      }

      $this->participants[$tid] = $query
        ->execute()
        ->fetchAllAssoc('participant');
    }

    return $this->participants[$tid];
  }

  /**
   * {inheritdoc}
   */
  public function countUnread($account) {
    if (!isset($this->counts[$account->id()])) {
      $query = $this->database->select('ptalk_message_index', 'pmi');
      $query->addExpression('COUNT(DISTINCT tid)');
      $this->counts[$account->id()] = $query
        ->condition('pmi.deleted', 0, '=')
        ->condition('pmi.status', 1, '=')
        ->condition('pmi.recipient', $account->id(), '=')
        ->execute()
        ->fetchField();
    }

    return $this->counts[$account->id()];
  }

  /**
   * {inheritdoc}
   */
  public function loadIndex($tids) {
    $query = $this->database->select('ptalk_thread_index', 'pti');
    $query->fields('pti', ['tid', 'message_count', 'new_count', 'status', 'deleted']);
    $query->addTag('ptalk_thread_index');
    $query->condition('participant', $this->currentUser->id());

    return $query
      ->condition('pti.tid', $tids, 'IN')
      ->orderBy('tid')
      ->execute()
      ->fetchAllAssoc('tid');
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();
    // Do not serialize static cache.
    unset($vars['participants'], $vars['counts']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();
    // Initialize static caches.
    $this->participants = [];
    $this->counts = [];
  }

}
