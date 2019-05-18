<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides an interface defining config entity with permissions capabilities.
 */
interface ConfigPermissionsInterface {

  /**
   * Returns a list of permissions assigned to the config entity.
   *
   * @return array
   *   The permissions assigned to the config entity.
   */
  public function getPermissions();

  /**
   * Checks if the config entity has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   TRUE if the config entity has the permission, FALSE if not.
   */
  public function hasPermission($permission);

  /**
   * Grants multiple permission to the config entity.
   *
   * @param string[] $permissions
   *   The permissions to grant.
   *
   * @return $this
   */
  public function grantPermissions(array $permissions);

  /**
   * Grant permissions to the config entity.
   *
   * @param string $permission
   *   The permission to grant.
   *
   * @return $this
   */
  public function grantPermission($permission);

  /**
   * Revokes multiple permissions from the config entity.
   *
   * @param string[] $permissions
   *   The permissions to revoke.
   *
   * @return $this
   */
  public function revokePermissions(array $permissions);

  /**
   * Revokes a permissions from the user config entity.
   *
   * @param string $permission
   *   The permission to revoke.
   *
   * @return $this
   */
  public function revokePermission($permission);

  /**
   * Changes permissions for the config entity.
   *
   * This function may be used to grant and revoke multiple permissions at once.
   * For example, when a form exposes checkboxes to configure permissions for a
   * config entity, the form submit handler may directly pass the submitted
   * values for the checkboxes form element to this function.
   *
   * @param array $permissions
   *   (optional) An associative array, where the key holds the permission name
   *   and the value determines whether to grant or revoke that permission. Any
   *   value that evaluates to TRUE will cause the permission to be granted.
   *   Any value that evaluates to FALSE will cause the permission to be
   *   revoked.
   *
   * @code
   *     [
   *       'administer organization' => 0,         // Revoke 'administer organization'
   *       'edit organization' => FALSE,           // Revoke 'edit organization'
   *       'administer members' => 1,       // Grant 'administer organization users'
   *       'leave organization' => TRUE,           // Grant 'leave organization'
   *       'join organization' => 'join organization',    // Grant 'join organization'
   *     ]
   * @endcode
   *   Existing permissions are not changed, unless specified in $permissions.
   *
   * @return $this
   */
  public function changePermissions(array $permissions = []);

  /**
   * Indicates that a config entity has all available permissions.
   *
   * @return bool
   *   TRUE if the config entity has all permissions.
   */
  public function isAbsolute();

  /**
   * Sets the config entity to have full access.
   *
   * @param bool $is_absolute
   *   TRUE if the config entity has full access.
   *
   * @return $this
   */
  public function setIsAbsolute($is_absolute);

}
