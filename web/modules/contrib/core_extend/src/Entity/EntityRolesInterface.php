<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides an interface for defining entities with roles.
 */
interface EntityRolesInterface {

  /**
   * Return the entity's roles.
   *
   * @return string[]
   *   An array of string role IDs.
   */
  public function getRoles();

  /**
   * Add a role to an entity.
   *
   * @param string $rid
   *   The role ID to add.
   */
  public function addRole($rid);

  /**
   * Remove a role from the entity.
   *
   * @param string $rid
   *   The role ID to remove.
   */
  public function removeRole($rid);

  /**
   * Checks whether the entity has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   Whether the entity has the requested permission.
   */
  public function hasPermission($permission);

}
