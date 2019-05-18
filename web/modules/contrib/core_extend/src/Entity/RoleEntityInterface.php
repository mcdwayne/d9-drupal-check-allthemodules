<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides an interface for defining Organization Role entities.
 */
interface RoleEntityInterface {

  /**
   * Indicates that a role has all available permissions.
   *
   * @return bool
   *   TRUE if the role has all permissions.
   */
  public function isAdmin();

  /**
   * Sets the role to be an admin role.
   *
   * @param bool $is_admin
   *   TRUE if the role should be an admin role.
   *
   * @return $this
   */
  public function setIsAdmin($is_admin);

  /**
   * Determines whether the node type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Returns a list of permissions assigned to the role.
   *
   * @return array
   *   The permissions assigned to the role.
   */
  public function getPermissions();

  /**
   * Checks if the role has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   TRUE if the role has the permission, FALSE if not.
   */
  public function hasPermission($permission);

  /**
   * Grants multiple permission to the role.
   *
   * @param string[] $permissions
   *   The permissions to grant.
   *
   * @return \Drupal\core_extend\Entity\RoleEntityInterface
   *   The organization role this was called on.
   */
  public function grantPermissions(array $permissions);

  /**
   * Grant permissions to the role.
   *
   * @param string $permission
   *   The permission to grant.
   *
   * @return $this
   */
  public function grantPermission($permission);

  /**
   * Revokes multiple permissions from the role.
   *
   * @param string[] $permissions
   *   The permissions to revoke.
   *
   * @return \Drupal\core_extend\Entity\RoleEntityInterface
   *   The organization role this was called on.
   */
  public function revokePermissions(array $permissions);

  /**
   * Revokes a permissions from the user role.
   *
   * @param string $permission
   *   The permission to revoke.
   *
   * @return $this
   */
  public function revokePermission($permission);

  /**
   * Changes permissions for the role.
   *
   * This function may be used to grant and revoke multiple permissions at once.
   * For example, when a form exposes checkboxes to configure permissions for a
   * role, the form submit handler may directly pass the submitted values for
   * the checkboxes form element to this function.
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
   *       'administer $entity_type_id' => 0,         // Revoke 'administer $entity_type_id'
   *       'edit $entity_type_id' => FALSE,           // Revoke 'edit $entity_type_id'
   *       'administer $entity_type_id' => 1,       // Grant 'administer $entity_type_id'
   *       'leave $entity_type_id' => TRUE,           // Grant 'leave $entity_type_id'
   *       'join $entity_type_id' => 'join $entity_type_id',    // Grant 'join $entity_type_id'
   *     ]
   * @endcode
   *   Existing permissions are not changed, unless specified in $permissions.
   *
   * @return \Drupal\core_extend\Entity\RoleEntityInterface
   *   The organization role this was called on.
   */
  public function changePermissions(array $permissions = []);

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight of this role.
   */
  public function getWeight();

  /**
   * Sets the weight to the given value.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
