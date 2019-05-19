<?php

namespace Drupal\user_hash\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;

/**
 * Delete user hashes.
 *
 * @Action(
 *   id = "user_delete_user_hash_action",
 *   label = @Translation("Delete hash from the selected user(s)"),
 *   type = "user"
 * )
 */
class DeleteUserHash extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account !== FALSE) {
      \Drupal::service('user.data')->delete('user_hash', $account->id(), 'hash');
      Cache::invalidateTags(['user:' . $account->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
