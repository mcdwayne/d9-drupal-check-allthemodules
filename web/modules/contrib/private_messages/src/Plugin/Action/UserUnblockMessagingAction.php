<?php

namespace Drupal\private_messages\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'UserUnblockMessagingAction' action.
 *
 * @Action(
 *  id = "user_unblock_messaging_action",
 *  label = @Translation("Unblock user from messaging"),
 *  type = "user",
 * )
 */
class UserUnblockMessagingAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    // Insert code here.
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
