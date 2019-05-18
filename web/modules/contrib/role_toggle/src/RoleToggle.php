<?php

namespace Drupal\role_toggle;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Class RoleToggle
 *
 * @internal
 * @package Drupal\role_toggle
 *
 * @todo Add pagelocal mode where toggles are saved in a querystring.
 * - Remove user saving then, instead redirect
 * - Add current-user decorator that picks up querystring
 * - Always add our querystring as cache context
 * - Add outbound link alter
 */
class RoleToggle {

  public static function hookRoleDelete(RoleInterface $role) {
    $permissionIdToToggle = self::permissionIdToToggle($role);
    foreach (user_roles() as $role) {
      if ($role->hasPermission($permissionIdToToggle)) {
        $role->revokePermission($permissionIdToToggle);
        $role->save();
      }
    }
  }

  public static function permissions() {
    $perms = array();
    foreach (self::configurableRoles() as $role) {
      $perms[self::permissionIdToToggle($role)] = array(
        'title' => self::permissionLabel($role),
        'description' => t('Users with this permission have the right to toggle said role. Note that permissions from authenticated and admin role are ignored, as both lead to insane results.'),
      );
    }
    return $perms;
  }

  /**
   * @param \Drupal\user\RoleInterface $role
   * @return bool
   */
  public static function userAccess($role) {
    return \Drupal::currentUser()->hasPermission(self::permissionIdToToggle($role));
  }

  /**
   * @param \Drupal\user\RoleInterface $role
   * @return string
   */
  public static function permissionIdToToggle($role) {
    return 'role_toggle:' . $role->id();
  }

  /**
   * @param \Drupal\user\RoleInterface $role
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public static function permissionLabel($role) {
    return t('Toggle role %role', array('%role' => $role->label()));
  }

  /**
   * Get all applicable user roles.
   *
   * @return \Drupal\user\RoleInterface[]
   */
  public static function configurableRoles() {
    $roles = user_roles();
    uasort($roles, [Role::class, 'sort']);
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    return $roles;
  }

  /**
   * @return \Drupal\user\RoleInterface[]
   */
  public static function togglableRoles() {
    return array_filter(self::configurableRoles(), function(RoleInterface $role) {
      return self::canToggle($role);
    });
  }

  public static function rolesEnabled() {
    return array_map(function (Role $role) {
      return static::isEnabledRole($role);
    }, static::togglableRoles());
  }

  public static function colorCode($dark) {
    $hash = substr(md5(serialize(static::rolesEnabled())), 0, 6);
    $hashValue = hexdec($hash);
    if ($dark) {
      $hashValue &= 0x7f7f7f;
    }
    else {
      $hashValue |= 0x808080;
    }
    $finalHash = dechex($hashValue);
    return $finalHash;
  }

  /**
   * Check if a user can toggle a role.
   *
   * @param \Drupal\user\RoleInterface $role
   * @return bool
   */
  public static function canToggle($role, AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    return self::accountHasExplicitPermission($account, self::permissionIdToToggle($role));
  }

  /**
   * Check if a user has permission, but ignore isAdmin() and auth perms.
   *
   * Having auth permissions to toggle a role is insane, so ignore that.
   * The admin role otoh leads to people being able to toggle away all roles
   * which is insane too.
   *
   * @see \Drupal\user\RoleStorage::isPermissionInRoles
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param string $permissionId
   *
   * @return bool
   */
  protected static function accountHasExplicitPermission(AccountInterface $account, $permissionId) {
    $has_permission = FALSE;
    foreach ($account->getRoles(TRUE) as $roleId) {
      $role = Role::load($roleId);
      if (!$role->isAdmin() && $role->hasPermission($permissionId)) {
        $has_permission = TRUE;
        break;
      }
    }
    return $has_permission;
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface|null $account
   * @return bool
   */
  public static function canToggleAny(AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    foreach (self::configurableRoles() as $role) {
      if (self::canToggle($role, $account)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param \Drupal\user\RoleInterface $role
   * @return bool
   */
  public static function isEnabledRole($role) {
    return in_array($role->id(), \Drupal::currentUser()->getRoles());
  }

  public static function toolbar() {
    $account = \Drupal::currentUser();
    $items['role_toggle'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if (static::canToggleAny($account)) {
      /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
      $form_builder = \Drupal::service('form_builder');
      $form = $form_builder->getForm(RoleToggleForm::class);

      $colorCode = static::colorCode(FALSE);

      $items['role_toggle'] += [
        '#type' => 'toolbar_item',
        '#weight' => 999,
        'tab' => [
          '#type' => 'link',
          '#title' => t('Role toggle'),
          '#url' => Url::fromRoute('role_toggle.form'),
          '#attributes' => [
            'title' => t('Role toggle'),
            'class' => ['toolbar-icon', 'toolbar-icon-role-toggle'],
            'style' => "color: #$colorCode",
          ],
        ],
        'tray' => [
          '#heading' => t('Role toggle'),
          'role_toggle_form' => $form,
        ],
        '#attached' => [
          'library' => 'role_toggle/toolbar',
        ],
      ];
    }

    return $items;
  }

  public static function addCachability(RefinableCacheableDependencyInterface $metadata) {
    $metadata->addCacheContexts(['url.query_args:role-toggle']);
  }

  public static function createQueryCode(UserInterface $user) {
    $queryCodeParts = [];
    foreach (self::togglableRoles() as $rid => $togglableRole) {
      $hasRole = $user->hasRole($rid);
      $queryCodeParts[] = (int) $hasRole;
    }
    $queryCode = implode($queryCodeParts);
    return ['role-toggle' => $queryCode];
  }

  public static function getCachedCreatedQueryCode(AccountInterface $account) {
    // @fixme Cache differently.
    if (!isset($account->role_toggle_query_code)) {
      if ($account->isAuthenticated()) {
        $user = User::load($account->id());
        $account->role_toggle_query_code = self::createQueryCode($user);
      }
      else {
        $account->role_toggle_query_code = [];
      }
    }
    return $account->role_toggle_query_code;
  }

  public static function applyQueryCode(UserInterface $user) {
    $queryCode = self::extractRequestQueryCode();
    if (!is_string($queryCode) ) {
      return;
    }
    $togglableRoles = self::togglableRoles();
    $lengthOk = strlen($queryCode) === count($togglableRoles);
    $charactersOk = str_replace(['0', '1'], '', $queryCode) === '';
    if (!$lengthOk || !$charactersOk) {
      return;
    }
    foreach (array_keys($togglableRoles) as $i => $rid) {
      if ($queryCode[$i]) {
        $user->addRole($rid);
        $needsSave = TRUE;
      }
      else {
        $user->removeRole($rid);
        $needsSave = TRUE;
      }
    }
    if (!empty($needsSave)) {
      $user->save();
    }
  }

  /**
   * @return mixed
   */
  public static function extractRequestQueryCode() {
    $queryCode = \Drupal::request()->query->get('role-toggle');
    return $queryCode;
  }

  public static function hasRequestQueryCode() {
    return (bool)self::extractRequestQueryCode();
  }

}
