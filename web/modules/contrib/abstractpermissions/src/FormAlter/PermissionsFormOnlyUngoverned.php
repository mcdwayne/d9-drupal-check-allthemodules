<?php

namespace Drupal\abstractpermissions\FormAlter;

use Drupal\abstractpermissions\PermissionGovernor;

class PermissionsFormOnlyUngoverned extends PermissionsFormAlterBase {

  protected static function governedModule(array &$row) {
    $row = [];
  }

  protected static function governedPermission(array &$row, PermissionGovernor $governor) {
    $row = [];
  }

}
