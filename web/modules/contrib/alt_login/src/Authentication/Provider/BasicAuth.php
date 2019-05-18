<?php

namespace Drupal\alt_login\Authentication\Provider;

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * HTTP Basic authentication provider which accepts user id & emails as well as username
 */
class BasicAuth extends \Drupal\basic_auth\Authentication\Provider\BasicAuth {

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $aliases = $this->configFactory->get('alt_login.settings')->get('login');
    $user_id = $request->headers->get('PHP_AUTH_USER');
    if (!empty($aliases[ALT_LOGIN_WITH_UID]) and is_numeric($user_id)) {
      $request->headers->set('PHP_AUTH_USER', User::load($user_id)->getAccountName());
    }
    elseif (!empty($aliases[ALT_LOGIN_WITH_UID]) and \Drupal::service('email.validator')->isValid($user_id)) {
      $users = \Drupal::entityManager()->getStorage('user')->loadByProperties(['mail' => $user_id]);
      $request->headers->set('PHP_AUTH_USER', reset($users)->getAccountName());
    }
    return parent::authenticate($request);
  }

}
