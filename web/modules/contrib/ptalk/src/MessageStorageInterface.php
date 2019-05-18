<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for message entity storage classes.
 */
interface MessageStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets the number of the page of the last message or of the current message,
   * depands from the parameter page.
   *
   * @param \Drupal\ptalk\MessageInterface $message
   *   The ptalk_message entity.
   * @param $per_page
   *   Indicates how many messages will be placed on the page.
   *   Default '1'.
   * @param $page
   *   The page of the current message or the page of the last message.
   * @param $count_deleted
   *   Indicates if calculating should be done for deleted messages to.
   *   Default to FALSE.
   * @param $account
   *   User object, defaults to the current user
   *
   * @return float
   *   The number of the page on which message is placed.
   */
  public function getNumPage($message, $per_page = 1, $page = '', $count_deleted = FALSE, $account = NULL);

  /**
   * Load indexed information for the current user.
   *
   * @param array $mids
   *   Message ids for which indexed information must be load.
   *
   * @return array
   *   The array keyed by ptalk_message entity ID
   *   containing data from ptalk_message_index table.
   *
   * @see hook_ptalk_message_index().
   */
  public function loadIndex($tids);

}
