<?php

namespace Drupal\key_auth;

use Symfony\Component\HttpFoundation\Request;
use Drupal\user\UserInterface;

/**
 * Interface KeyAuthInterface.
 */
interface KeyAuthInterface {

  /**
   * Get the key provided in the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string|false
   *   The API key provided in the request, or FALSE if there was not one.
   */
  public function getKey(Request $request);

  /**
   * Load the user associated with a given key.
   *
   * @param string $key
   *   The API key to match to a user.
   *
   * @return \Drupal\user\Entity\User|null
   *   The matching user entity, or NULL if there was no match.
   */
  public function getUserByKey($key);

  /**
   * Determine if a user has access to use key authentication.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being authenticated.
   *
   * @return bool
   *   TRUE if the user has access, otherwise FALSE.
   */
  public function access(UserInterface $user);

  /**
   * Generate a new unique key.
   *
   * @return string
   *   An API key.
   */
  public function generateKey();

}
