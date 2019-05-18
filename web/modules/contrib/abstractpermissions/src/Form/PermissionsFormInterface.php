<?php

namespace Drupal\abstractpermissions\Form;

interface PermissionsFormInterface {

  /**
   * Get a permisisons form.
   *
   * @param $roleNames
   *   The role names, keyed by id.
   * @param $permissions
   *   The permissions info objects, must at least contain "title" key.
   * @param $permissionsPerRole
   * @return array
   */
  public function form($roleNames, $permissions, $permissionsPerRole);

  /**
   * Extract permissions by role.
   *
   * @param $values
   *   The form values.
   * @return array
   *  The permissions by role.
   */
  public function extractPermissionsByRole($values);

}