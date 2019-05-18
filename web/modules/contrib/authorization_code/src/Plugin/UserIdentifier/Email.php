<?php

namespace Drupal\authorization_code\Plugin\UserIdentifier;

/**
 * Identifies users by their email address.
 *
 * @UserIdentifier(
 *   id = "email",
 *   title = @Translation("Email")
 * )
 */
class Email extends UserIdentifierBase {

  /**
   * {@inheritdoc}
   */
  public function loadUser($identifier) {
    $maybe_user = $this->userStorage->loadByProperties(['mail' => $identifier]);
    return reset($maybe_user) ?: NULL;
  }

}
