<?php

namespace Drupal\monster_menus\Session;

use Drupal\user\Entity\Role;

/**
 * Override \Drupal\Core\Session\AccountProxy to include permissions checking
 * which takes roles tied to MM groups into account.
 */
class AccountProxy extends \Drupal\Core\Session\AccountProxy {

  /**
   * @var array Cache for user roles derived from MM groups.
   */
  private $userRoles;

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    if ($this->getAccount()->hasPermission($permission)) {
      return TRUE;
    }
    return \Drupal::entityTypeManager()->getStorage('user_role')->isPermissionInRoles($permission, $this->getMMRoles());
  }

  public function getMMRoles() {
    $account = $this->getAccount();
    if ($account->isAnonymous()) {
      // Save time by avoiding the query.
      return [];
    }
    $uid = $account->id();
    if (!isset($this->userRoles[$uid])) {
      $this->userRoles[$uid] = [];
      $rids = static::getRolesHavingMMGroups();
      if ($rids) {
        /** @var Role $role */
        foreach (\Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple($rids) as $role) {
          $gid = $role->get('mm_gid');
          $exclude = $role->get('mm_exclude');
          if ((bool) mm_content_get_uids_in_group($gid, $uid) !== (bool) $exclude) {
            $this->userRoles[$uid][] = $role->id();
          }
        }
      }
    }
    return $this->userRoles[$uid];
  }

  public static function getRolesHavingMMGroups() {
    return \Drupal::entityQuery('user_role')
      ->condition('mm_gid', 0, '>')
      ->execute();
  }

}
