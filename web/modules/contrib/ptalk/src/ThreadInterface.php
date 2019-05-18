<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a ptalk_thread entity.
 */
interface ThreadInterface extends ContentEntityInterface {

  /**
   * Marks thread and all messages of the thread as deleted
   * for the current participant.
   *
   * @param string $delete
   *   The PTALK_DELETED constant.
   */
  public function deleteThread($delete);

  /**
   * Changes status of the thread and all messages of the thread
   * for the current participant.
   *
   * @param string $status
   *   The PTALK_READ or PTALK_UNREAD constant.
   */
  public function markThread($status);

  /**
   * Checks if the user with gived account is a participant of the thread.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object, for which checking must be done.
   *
   * @return bool
   *   Return TRUE if user is a participant of the thread, FALSE otherwise.
   */
  public function participantOf($account);

  /**
   * Checks if the thread is deleted for the user.
   *
   * @return bool
   *   Return TRUE if thread is deleted, FALSE otherwise.
   */
  public function isDeleted();

  /**
   * Returns ids of the all participants of the thread.
   *
   * @return array
   *   Return array with ids of the participants of the thread.
   */
  public function getParticipantsIds();
}
