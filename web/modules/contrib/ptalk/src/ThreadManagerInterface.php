<?php

namespace Drupal\ptalk;

/**
 * Thread manager contains common functions to manage threads data.
 */
interface ThreadManagerInterface {

  /**
   * Checks if thread is deleted considering state of the field 'deleted' in table ptalk_thread_index.
   *
   * @param $tid
   *   The ID of the ptalk_thread entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object, for which checking must be done.
   *   If account is NULL then status of the thread will be
   *   considered by state of the all partcipants of the thread.
   *
   * @return bool|false/true
   *   Return TRUE if thread is deleted, FALSE otherwise.
   */
  public function isThreadDeleted($tid, $account = NULL);

  /**
   * Create indexed information for all participants of the private conversation.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   The ptalk_thread entity.
   */
  public function createIndex($thread);

  /**
   * Counts of the messages for the particular account.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   The ptalk_thread entity.
   * @param int $account_id
   *   The account ID for which counts must be done.
   * @param bool $count_deleted.
   *   Indicates if counts must be done for deleted messages.
   *
   * @return string
   *   Return counts of the messages.
   */
  public function countMessages($thread, $account_id = NULL, $count_deleted = FALSE);

  /**
   * Counts of the new messages for the particular account.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   The ptalk_thread entity.
   * @param int $account_id
   *   The account ID for which counts must be done.
   *
   * @return string
   *   Return counts of the new messages for the account.
   */
  public function countNewMessages($thread, $account_id = NULL);

  /**
   * Updates the count of the new messages in ptalk_thread_index table.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   The ptalk_thread entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object, for which updating must be done.
   */
  public function updateNewCount($thread, $account = NULL);

  /**
   * Changes status 'deleted' in ptalk_thread_index.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   The ptalk_thread entity.
   * @param string $delete
   *   The PTALK_DELETED or PTALK_UNDELETED constant.
   * @param int $account_id
   *   The account ID for which field 'deleted' must be changed.
   */
  public function deleteIndex($thread, $delete, $account_id = NULL);

  /**
   * Updates counts of the message_count and new_count fields in the table ptalk_thread_index.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   The ptalk_thread entity.
   * @param int $account_id
   *   The account ID for which updating must be done.
   */
  public function updateCounts($thread, $account_id = NULL);

  /**
   * Increases counts of new and undeleted messages after message was saved successfully.
   *
   * @param \Drupal\ptalk\MessageInterface $message
   *   The ptalk_message entity.
   * @param int $count
   *   Increment.
   */
  public function increaseCounts($message, $count = 1);

}
