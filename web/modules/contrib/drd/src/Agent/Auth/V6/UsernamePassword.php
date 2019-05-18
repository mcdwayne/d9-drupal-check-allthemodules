<?php

namespace Drupal\drd\Agent\Auth\V6;

class UsernamePassword extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings) {
    if (user_is_anonymous()) {
      $user = user_authenticate(array(
        'name' => $settings['username'],
        'pass' => $settings['password']
      ));
      return (!empty($user));
    }

    return TRUE;
  }

}
