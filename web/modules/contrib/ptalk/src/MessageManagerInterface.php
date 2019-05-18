<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\EntityInterface;

/**
 * Message manager contains common functions to manage message data.
 */
interface MessageManagerInterface {

  /**
   * Checks if message is deleted considering state of the field 'deleted' in table ptalk_message_index.
   *
   * @param $mid
   *   The ID of the ptalk_message entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object, for which checking must be done.
   *   If account is NULL then state of the message will be
   *   considered by all recipients of the message.
   *
   * @return bool false/true
   *   Return TRUE if message is deleted, FALSE otherwise.
   */
   public function isMessageDeleted($mid, $account = NULL);

}
