<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'UserCredentials' code.
 */
class UserCredentials extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    $edit = array();

    if (!empty($args['username'])) {
      $check = user_validate_name($args['username']);
      if (!empty($check)) {
        drupal_set_message($check, 'error');
        return array();
      }
      $user = user_load(array('name' => $args['username']));
      if (!empty($user) && $user->uid !== 1) {
        drupal_set_message(t('Username already taken.'), 'error');
        return array();
      }
      $edit['name'] = $args['username'];
    }

    if (!empty($args['password'])) {
      $edit['pass'] = $args['password'];
    }

    if (isset($args['status'])) {
      $edit['status'] = $args['status'];
    }

    if (!empty($edit)) {
      $account = user_load($args['uid']);
      if (!user_save($account, $edit)) {
        drupal_set_message(t('Changing user credentials failed.'), 'error');
      }
    }
    return array();
  }

}
