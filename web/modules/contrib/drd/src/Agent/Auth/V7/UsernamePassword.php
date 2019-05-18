<?php

namespace Drupal\drd\Agent\Auth\V7;

class UsernamePassword extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings) {
    if (user_is_anonymous()) {
      return user_authenticate($settings['username'], $settings['password']);
    }

    return TRUE;
  }

}
