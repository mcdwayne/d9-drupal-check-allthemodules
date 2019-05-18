<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\Cache;

/**
 * Thread manager contains common functions to manage threads data.
 */
class ThreadManager implements ThreadManagerInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * Construct the ThreadManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $database
   *   Active database connection.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user, Connection $database) {
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function isThreadDeleted($tid, $account = NULL) {
    $query = $this->database->select('ptalk_thread_index', 'pti');
    $query->addField('pti', 'tid');
    $query->condition('pti.tid', $tid);
    $query->condition('pti.deleted', 0);

    if ($account) {
      $query
        ->condition('pti.participant', $account->id());
    }

    $deleted = $query
      ->execute()
      ->fetchField();

    return (bool) $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function createIndex($thread) {
    $query = $this->database->insert('ptalk_thread_index')
      ->fields([
        'tid',
        'participant',
        'status',
        'deleted',
      ]);

    foreach ($thread->getParticipantsIds() as $participant) {
      $query->values([
        'tid' => (int) $thread->id(),
        'participant' => (int) $participant,
        'status' => 1,
        'deleted' => 0,
      ]);
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function countMessages($thread, $account_id = NULL, $count_deleted = FALSE) {
    if (is_null($account_id)) {
      $account_id = $this->currentUser->id();
    }

    $count_query = $this->database->select('ptalk_message', 'pm');
    $count_query->addExpression('COUNT(DISTINCT pm.mid)');
    $count_query->join('ptalk_message_index', 'pmi', 'pm.mid = pmi.mid');
    $count_query->condition('pm.tid', $thread->id());

    // Count messages for particular participant.
    $count_query->condition('pmi.recipient', $account_id);

    // Do not count deleted messages.
    if ($count_deleted == FALSE) {
      $count_query->condition('pmi.deleted', 0);
    }

    return $count_query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function countNewMessages($thread, $account_id = NULL) {
    if (is_null($account_id)) {
      $account_id = $this->currentUser->id();
    }

    $count_query = $this->database->select('ptalk_message', 'pm');
    $count_query->addExpression('COUNT(DISTINCT pm.mid)');
    $count_query->join('ptalk_message_index', 'pmi', 'pm.mid = pmi.mid');
    $count_query->condition('pm.tid', $thread->id());

    // Count messages for particular participant.
    $count_query->condition('pmi.recipient', $account_id);

    // Do not count deleted messages.
    $count_query->condition('pmi.deleted', 0);
    $count_query->condition('pmi.status', 1);

    $count = $count_query->execute()->fetchField();

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function updateNewCount($thread, $account = NULL) {
    if (is_null($account)) {
      $account = $this->currentUser;
    }

    $new_count = $this->countNewMessages($thread);

    $this->database->update('ptalk_thread_index')
      ->fields(['new_count' => $new_count])
      ->condition('tid', $thread->id())
      ->condition('participant', $account->id())
      ->execute();
  
    Cache::invalidateTags(array('ptalk_participant:' . $account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteIndex($thread, $delete, $account_id = NULL) {
    $update = db_update('ptalk_thread_index')
      ->fields(['deleted' => $delete])
      ->condition('tid', $thread->id());

    if ($account_id) {
      $update->condition('participant', $account_id);
    }

    $update->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateCounts($thread, $account_id = NULL) {
    $uids = $account_id ? [$account_id] : $thread->getParticipantsIds();

    foreach ($uids as $uid) {
      $count = $this->countMessages($thread, $uid);
      $new = $this->countNewMessages($thread, $uid);

      $this->database->update('ptalk_thread_index')
        ->fields([
          'message_count' => $count,
          'new_count' => $new,
        ])
        ->condition('tid', $thread->id())
        ->condition('participant', (int) $uid)
        ->execute();

      Cache::invalidateTags(array('ptalk_participant:' . $uid));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function increaseCounts($message, $count = 1) {
    // For field message_count update thread index for all participants of the message.
    $pids = array_keys($message->recipients);
    $this->database->update('ptalk_thread_index')
      ->expression('message_count', 'message_count + ' . $count)
      ->expression('deleted', 'IF(deleted <> 0, deleted = 0, deleted + 0)')
      ->condition('tid', $message->getThreadId())
      ->condition('participant', $pids, 'IN')
      ->execute();

    // For field new_count update thread index only for recipients of the message.
    $rids = array_diff($pids, [$message->getOwnerId()]);
    $this->database->update('ptalk_thread_index')
      ->expression('new_count', 'new_count + ' . $count)
      ->condition('tid', $message->getThreadId())
      ->condition('participant', $rids, 'IN')
      ->execute();

    foreach ($pids as $pid) {
      Cache::invalidateTags(array('ptalk_participant:' . $pid));
    }
  }

}
