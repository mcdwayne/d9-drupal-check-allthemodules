<?php

namespace Drupal\abstractpermissions;

use Drupal\user\RoleInterface;

interface AbstractPermissionsServiceInterface {

  /**
   * Get Permissions.
   *
   * @return array
   */
  public function permissionCallback();

  /**
   * Get permission abstractions.
   *
   * @return \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface[]
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function getPermissionAbstractions();

    /**
   * Get permission graph.
   *
   * @return \Drupal\abstractpermissions\PermissionGraph
   */
  public function getPermissionGraph();

    /**
   * Invalidate.
   *
   * @return void
   */
  public function invalidate();

  /**
   * Denormalize a role.
   *
   * @param \Drupal\user\RoleInterface $role
   * @return void
   */
  public function denormalizeRole(RoleInterface $role);

}
