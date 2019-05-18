<?php

namespace Drupal\abstractpermissions\FormAlter;

class PermissionsFormOnlyGoverned extends PermissionsFormAlterBase {

  protected static function ungovernedModule(array &$row) {
    $row = [];
  }

  protected static function ungovernedPermission(array &$row) {
    $row = [];
  }

}
