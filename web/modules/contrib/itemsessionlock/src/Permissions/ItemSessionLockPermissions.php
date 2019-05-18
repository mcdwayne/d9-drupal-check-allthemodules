<?php

namespace Drupal\itemsessionlock\Permissions;

use Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockManager;

/**
 * Defines dynamic permissions.
 */
class ItemSessionLockPermissions {

  /**
   * Gathers plugin permissions definitions.
   * @return array of permission definition arrays.
   */
  public function permissions() {
    $permissions = array();
    $manager = \Drupal::service('plugin.manager.itemsessionlock');
    $locks = $manager->getDefinitions();
    if (!empty($locks)) {
      foreach ($locks as $def) {
        $lock = $manager->createInstance($def['id']);
        $perms = $lock->getPermissions();
        foreach ($perms as $id => $perm) {
          $permissions[$id] = $perm;
        }
      }
    }
    return $permissions;
  }

}
