<?php

namespace Drupal\null_user;

use Drupal\Core\Session\AnonymousUserSession;

class NullUser extends AnonymousUserSession {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return FALSE;
  }

}
