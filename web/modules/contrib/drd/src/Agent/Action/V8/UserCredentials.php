<?php

namespace Drupal\drd\Agent\Action\V8;

use Drupal\user\Entity\User;

/**
 * Provides a 'UserCredentials' code.
 */
class UserCredentials extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    $account = User::load($args['uid']);
    if (!$account) {
      drupal_set_message('User does not exist.', 'error');
    }
    else {
      $this->setUsername($account, $args);
      $this->setPassword($account, $args);
      $this->setStatus($account, $args);
      try {
        $account->save();
      }
      catch (\Exception $ex) {
        drupal_set_message('Changing user credentials failed.', 'error');
      }
    }
    return [];
  }

  /**
   * Callback to set the new username if it is not taken yet.
   *
   * @param \Drupal\user\Entity\User $account
   *   User account which should be changed.
   * @param array $args
   *   Array of arguments.
   */
  private function setUsername(User $account, array $args) {
    if (empty($args['username'])) {
      return;
    }
    $check = user_validate_name($args['username']);
    if (!empty($check)) {
      drupal_set_message($check, 'error');
      return;
    }
    $user = user_load_by_name($args['username']);
    if (!empty($user) && $user->uid !== $args['uid']) {
      drupal_set_message('Username already taken.', 'error');
      return;
    }
    $account->setUsername($args['username']);
  }

  /**
   * Callback to set the new password.
   *
   * @param \Drupal\user\Entity\User $account
   *   User account which should be changed.
   * @param array $args
   *   Array of arguments.
   */
  private function setPassword(User $account, array $args) {
    if (empty($args['password'])) {
      return;
    }
    $account->setPassword($args['password']);
  }

  /**
   * Callback to set the status of the user account.
   *
   * @param \Drupal\user\Entity\User $account
   *   User account which should be changed.
   * @param array $args
   *   Array of arguments.
   */
  private function setStatus(User $account, array $args) {
    if (!isset($args['status'])) {
      return;
    }
    if ($args['status']) {
      $account->activate();
    }
    else {
      $account->block();
    }
  }

}
