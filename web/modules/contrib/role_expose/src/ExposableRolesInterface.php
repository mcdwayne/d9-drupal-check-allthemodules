<?php

namespace Drupal\role_expose;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface ExposableRolesInterface.
 *
 * @package Drupal\role_expose
 */
interface ExposableRolesInterface {

  /**
   * Role exposing; do not expose this role (default value)
   */
  const EXPOSE_NEVER = 0;

  /**
   * Role exposing; Expose if user has the role.
   */
  const EXPOSE_WITH = 1;

  /**
   * Role exposing; Expose if user does not have the role.
   */
  const EXPOSE_WITHOUT = 2;

  /**
   * Role exposing; Expose regardless if user has this role or not.
   */
  const EXPOSE_ALWAYS = 3;

  /**
   * Gets all roles apart from anonymous and authenticated.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  public function getSystemRoles();

  /**
   * Return which roles to expose if user does not have them.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   User account.
   *
   * @return array
   *   Array of roles that should be shown and but user has.
   */
  public function getVisibleRolesUserHas(EntityInterface $entity);

  /**
   * Return which roles to expose if user does not have them.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   User account.
   *
   * @return array
   *   Array of roles that should be shown but user does not have.
   */
  public function getVisibleRolesUserDoesNotHave(EntityInterface $entity);

}
