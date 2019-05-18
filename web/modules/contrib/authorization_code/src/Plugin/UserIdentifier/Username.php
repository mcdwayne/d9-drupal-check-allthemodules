<?php

namespace Drupal\authorization_code\Plugin\UserIdentifier;

/**
 * Identifies users by their username.
 *
 * @UserIdentifier(
 *   id = "username",
 *   title = @Translation("User Name")
 * )
 */
class Username extends UserIdentifierBase {

  /**
   * {@inheritdoc}
   */
  public function loadUser($identifier) {
    $maybe_user = $this->userStorage->loadByProperties(['name' => $identifier]);
    return reset($maybe_user) ?: NULL;
  }

}
