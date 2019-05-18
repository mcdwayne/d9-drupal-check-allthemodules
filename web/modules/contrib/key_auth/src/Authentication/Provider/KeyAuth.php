<?php

namespace Drupal\key_auth\Authentication\Provider;

use Drupal\key_auth\KeyAuthInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Key authentication provider.
 */
class KeyAuth implements AuthenticationProviderInterface {

  /**
   * The key auth service.
   *
   * @var \Drupal\key_auth\KeyAuthInterface
   */
  protected $keyAuth;

  /**
   * Constructs a key authentication provider object.
   *
   * @param \Drupal\key_auth\KeyAuthInterface $key_auth
   *   The key auth service.
   */
  public function __construct(KeyAuthInterface $key_auth) {
    $this->keyAuth = $key_auth;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return (bool) $this->keyAuth->getKey($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Get the provided key.
    if ($key = $this->keyAuth->getKey($request)) {
      // Find the linked user.
      if ($user = $this->keyAuth->getUserByKey($key)) {
        // Check access.
        if ($this->keyAuth->access($user)) {
          // Return the user.
          return $user;
        }
      }
    }
    return NULL;
  }

}
