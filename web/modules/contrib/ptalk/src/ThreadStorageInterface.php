<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for ptalk_thread entity storage classes.
 */
interface ThreadStorageInterface extends ContentEntityStorageInterface {

  /**
   * Retrieves messages for a thread, sorted in an order suitable for display.
   *
   * @param \Drupal\ptalk\ThreadInterface $thread
   *   ptalk_thread entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object for which messages must be loaded.
   * @param bool $load_deleted
   *   Indicates if should be loaded deleted messages to.
   * @param int $messages_per_page
   *   (optional) The amount of messages to display per thread page.
   *   Defaults to 0, which means show all messages.
   * @param int $pager_id
   *   (optional) Pager id to use in case of multiple pagers on the one page.
   *   Defaults to 0; is only used when $messages_per_page is greater than zero.
   *
   * @return array
   *   Ordered array of ptalk_message objects, keyed by message id.
   */
  public function loadThreadMessages($thread, $account, $load_deleted = FALSE, $messages_per_page = 0, $pager_id = 0);

  /**
   * Load participants of the conversation.
   *
   * @param $tid
   *   The id of the ptalk_thread entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object, defaults to the current user
   *
   * @return array
   *   Array keyed by participant id with information for this conversation.
   */
  public function loadThreadParticipants($tid, $account = NULL);

  /**
   * Count unread threads for particular participant.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object, defaults to the current user
   *
   * @return string
   *   The count unread threads.
   */
  public function countUnread($account);

  /**
   * Load indexed information for the current user.
   *
   * @param array $tids
   *   Thread ids for which indexed information must be load.
   *
   * @return array
   *   The array keyed by ptalk_thread entity ID
   *   containing data from ptalk_thread_index table.
   *
   * @see hook_ptalk_thread_index().
   */
  public function loadIndex($tids);

}
