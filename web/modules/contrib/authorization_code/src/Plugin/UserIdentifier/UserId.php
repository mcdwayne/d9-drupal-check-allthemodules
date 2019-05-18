<?php

namespace Drupal\authorization_code\Plugin\UserIdentifier;

/**
 * Identifies users by their user id.
 *
 * @UserIdentifier(
 *   id = "user_id",
 *   title = @Translation("User ID")
 * )
 */
class UserId extends UserIdentifierBase {

  /**
   * {@inheritdoc}
   */
  public function loadUser($identifier) {
    return $this->userStorage->load($identifier);
  }

}
