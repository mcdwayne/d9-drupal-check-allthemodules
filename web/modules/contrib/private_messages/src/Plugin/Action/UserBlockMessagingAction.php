<?php

namespace Drupal\private_messages\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'UserBlockMessagingAction' action.
 *
 * @Action(
 *  id = "user_block_messaging_action",
 *  label = @Translation("Block the selected users from messaging"),
 *  type = "user"
 * )
 */
class UserBlockMessagingAction extends ActionBase {

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
    $access = $object->status->access('view', $account, TRUE);

    return $return_as_object ? $access : $access->isAllowed();
  }
}
