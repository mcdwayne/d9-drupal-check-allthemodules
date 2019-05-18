<?php

namespace Drupal\drd\Agent\Auth\V8;

/**
 * Implements the UsernamePassword authentication method.
 */
class UsernamePassword extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      return TRUE;
    }
    return \Drupal::service('user.auth')->authenticate($settings['username'], $settings['password']);
  }

}
