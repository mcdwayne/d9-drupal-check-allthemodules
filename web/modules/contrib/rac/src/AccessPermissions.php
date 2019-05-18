<?php

namespace Drupal\rac;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Permissions generation Role Access grants.
 */
class AccessPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of role access permissions.
   *
   * @return array
   *   A list of generated permissions.
   */
  public function permissions() {
    $ops = ['view', 'update'];
    $permissions = [];
    $roles = user_roles();
    foreach ($ops as $op) {
      foreach ($roles as $role) {
        $permission = "RAC_" . $op . "_" . $role->id();
        $permissions[$permission] = [
          'title' => $this->t("@op Content for Role @label", ['@op' => $op, '@label' => $role->label()]),
          'restrict access' => TRUE,
        ];
      }
    }
    return $permissions;
  }

}
