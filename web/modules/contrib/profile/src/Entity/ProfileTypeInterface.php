<?php

namespace Drupal\profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a profile type entity.
 */
interface ProfileTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Gets whether a user can have multiple profiles of this type.
   *
   * @return bool
   *   TRUE if a user can have multiple profiles of this type, FALSE otherwise.
   */
  public function allowsMultiple();

  /**
   * Sets whether a user can have multiple profiles of this type.
   *
   * @param bool $multiple
   *   Whether a user can have multiple profiles of this type.
   *
   * @return $this
   */
  public function setMultiple($multiple);

  /**
   * Gets whether a profile of this type should be created during registration.
   *
   * @return bool
   *   TRUE a profile of this type should be created during registration,
   *   FALSE otherwise.
   */
  public function getRegistration();

  /**
   * Sets whether a profile of this type should be created during registration.
   *
   * @param bool $registration
   *   Whether a profile of this type should be created during registration.
   *
   * @return $this
   */
  public function setRegistration($registration);

  /**
   * Gets the user roles allowed to have profiles of this type.
   *
   * @return string[]
   *   The role IDs. If empty, all roles are allowed.
   */
  public function getRoles();

  /**
   * Sets the user roles allowed to have profiles of this type.
   *
   * @param string[] $rids
   *   The role IDs.
   *
   * @return $this
   */
  public function setRoles(array $rids);

}
