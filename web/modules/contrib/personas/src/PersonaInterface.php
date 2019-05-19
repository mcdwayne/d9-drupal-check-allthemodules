<?php

namespace Drupal\personas;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Persona entities.
 */
interface PersonaInterface extends ConfigEntityInterface {

  /**
   * Returns a list of roles assigned to the persona.
   *
   * @return array
   *   The roles assigned to the persona.
   */
  public function getRoles();

  /**
   * Checks if the persona has a role.
   *
   * @param string $role
   *   The role to check for.
   *
   * @return bool
   *   TRUE if the persona has the role, FALSE if not.
   */
  public function hasRole($role);

  /**
   * Add a role to the persona.
   *
   * @param string $role
   *   The role to add.
   *
   * @return $this
   */
  public function addRole($role);

  /**
   * Removes a role from the persona.
   *
   * @param string $role
   *   The role to remove.
   *
   * @return $this
   */
  public function removeRole($role);

}
