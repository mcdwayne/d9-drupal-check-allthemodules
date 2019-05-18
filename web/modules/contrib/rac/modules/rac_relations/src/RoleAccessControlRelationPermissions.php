<?php

namespace Drupal\rac_relations;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Permissions generation Role Access grants.
 */
class RoleAccessControlRelationPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of role access permissions.
   *
   * @return array
   *   A list of generated permissions.
   */
  public function permissions() {
    $permissions = [];
    $roles = user_roles();
    foreach ($roles as $role) {
      $permission = 'RAC update ' . $role->id();
      $permissions[$permission] = [
        'title' => $this->t('Update content for role @label', ['@label' => $role->label()]),
        'restrict access' => TRUE,
      ];
    }
    return $permissions;
  }

}
