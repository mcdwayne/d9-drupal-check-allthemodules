<?php

namespace Drupal\janrain_connect\Service;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * JanrainConnect Login Class.
 */
class JanrainConnectLogin {

  use StringTranslationTrait;

  /**
   * Symfony session handler.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * JanrainConnectValidate constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Session handler.
   */
  public function __construct(Session $session) {
    $this->session = $session;
  }

  /**
   * Do merge login.
   *
   * @param string $token
   *   Engage social token.
   * @param string $email
   *   Registered email.
   *
   * @return bool
   *   True if successfully logged in. False otherwise.
   */
  public function mergeLogin($token, $email) {
    $this->session->set('janrain_connect_access_token', $token);

    // Load by e-mail because we don't have the UUID.
    $user = user_load_by_mail($email);

    if ($user) {
      // Login user.
      user_login_finalize($user);

      // Message success should be translatable @codingStandardsIgnoreLine.
      drupal_set_message('You are successfully logged in', 'status');

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Do simple login.
   *
   * @param string $token
   *   Janrain access token.
   * @param \Drupal\user\UserInterface|object|bool $user
   *   User object or false.
   *
   * @return bool
   *   True if successfully logged in. False otherwise.
   */
  public function login($token, $user) {
    if (empty($user)) {
      return FALSE;
    }

    $this->session->set('janrain_connect_access_token', $token);

    // Set Janrain Role.
    if (empty($user->hasRole('janrain'))) {
      $user->addRole('janrain');
      try {
        $user->save();
      }
      catch (EntityStorageException $e) {
        return FALSE;
      }

    }

    // Login user.
    user_login_finalize($user);

    return TRUE;
  }

}
