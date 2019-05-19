<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Subscription type entities.
 */
interface SubscriptionTypeInterface extends ConfigEntityInterface {

  /**
   * Gets us the site's roles.
   *
   * @return array
   *   A list of roles
   */
  public function getSiteRoles();

  /**
   * Sets the role against the subscription type.
   *
   * @param string $role
   *   The name of the role.
   */
  public function setRole($role);

  /**
   * Getter method for the role.
   *
   * @return string
   *   The role name.
   */
  public function getRole();

}
