<?php

namespace Drupal\dbee\Entity;

use Drupal\user\Entity\User;

/**
 * Extends the core User class to ensure email addresses are decrypted.
 */
class DbeeUser extends User {

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return dbee_decrypt($this->get('mail')->value);
  }
}
