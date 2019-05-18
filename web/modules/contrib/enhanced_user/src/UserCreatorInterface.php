<?php

namespace Drupal\enhanced_user;

/**
 * Interface UserCreatorInterface.
 */
interface UserCreatorInterface {
  public function createUser($name, $email);
}
