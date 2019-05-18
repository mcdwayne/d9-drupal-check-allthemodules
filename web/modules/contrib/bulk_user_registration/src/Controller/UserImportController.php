<?php

namespace Drupal\bulk_user_registration\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Implements Class UserImportController Controller.
 */
class UserImportController extends ControllerBase {

  /**
   * Get All available roles.
   */
  public static function getAllUserRoleTypes() {
    return user_role_names();
  }

}
