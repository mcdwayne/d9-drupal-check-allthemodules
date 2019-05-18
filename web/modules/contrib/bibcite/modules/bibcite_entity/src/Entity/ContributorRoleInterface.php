<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Contributor role entities.
 */
interface ContributorRoleInterface extends ConfigEntityInterface {

  /**
   * Get weight of the Contributor role.
   *
   * @return int
   *   Weight of the Contributor role.
   */
  public function getWeight();

  /**
   * Set weight of the Contributor role.
   *
   * @param int $weight
   *   New weight of the Contributor role.
   *
   * @return \Drupal\bibcite_entity\Entity\ContributorRoleInterface
   *   The called Contributor role object.
   */
  public function setWeight($weight);

}
